<?php

namespace App\Services;

use App\Models\User;
use App\Models\Investment;
use App\Models\InvestmentPlan;
use App\Models\Withdrawal;
use App\Models\Referral;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinancialService
{
    /**
     * Create a new investment for a user.
     */
    public function createInvestment(User $user, InvestmentPlan $plan, float $amount): Investment
    {
        if ($amount < $plan->minimum_amount) {
            throw new \Exception("Amount is below minimum investment of ₦" . number_format($plan->minimum_amount, 2));
        }

        if ($plan->maximum_amount && $amount > $plan->maximum_amount) {
            throw new \Exception("Amount exceeds maximum investment of ₦" . number_format($plan->maximum_amount, 2));
        }

        if ($user->balance < $amount) {
            throw new \Exception("Insufficient balance. Your current balance is ₦" . number_format($user->balance, 2));
        }

        return DB::transaction(function () use ($user, $plan, $amount) {
            // Deduct amount from user balance
            $balanceBefore = $user->balance;
            $user->decrement('balance', $amount);

            // Calculate profit and total return
            $profitAmount = ($amount * $plan->profit_percentage) / 100;
            $totalReturn = $amount + $profitAmount;

            // Calculate maturity date
            $maturityDate = Carbon::now()->addDays($plan->duration_days);

            // Create investment
            $investment = Investment::create([
                'user_id' => $user->id,
                'investment_plan_id' => $plan->id,
                'amount' => $amount,
                'profit_amount' => $profitAmount,
                'total_return' => $totalReturn,
                'maturity_date' => $maturityDate,
                'status' => 'active',
            ]);

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'investment',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => "Investment in {$plan->name} - ID: {$investment->id}",
                'reference' => 'INV' . strtoupper(uniqid()),
                'related_id' => $investment->id,
                'related_type' => Investment::class,
            ]);

            return $investment;
        });
    }

    /**
     * Process matured investments.
     */
    public function processMaturedInvestments(): array
    {
        $maturedInvestments = Investment::active()
            ->reachedMaturity()
            ->with(['user', 'investmentPlan'])
            ->get();

        $processed = [];
        $errors = [];

        foreach ($maturedInvestments as $investment) {
            try {
                $this->creditInvestmentReturn($investment);
                $processed[] = $investment;
            } catch (\Exception $e) {
                $errors[] = [
                    'investment' => $investment,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }

    /**
     * Credit investment return to user.
     */
    public function creditInvestmentReturn(Investment $investment): void
    {
        if ($investment->status !== 'active') {
            throw new \Exception("Investment is not active");
        }

        DB::transaction(function () use ($investment) {
            $user = $investment->user;
            $totalReturn = $investment->total_return;

            // Update user balance
            $balanceBefore = $user->balance;
            $user->increment('balance', $totalReturn);

            // Update investment status
            $investment->update([
                'status' => 'matured',
                'credited_at' => now(),
            ]);

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'profit',
                'amount' => $totalReturn,
                'balance_before' => $balanceBefore,
                'balance_after' => $user->balance,
                'description' => "Investment matured - {$investment->investmentPlan->name} (ID: {$investment->id})",
                'reference' => 'MAT' . strtoupper(uniqid()),
                'related_id' => $investment->id,
                'related_type' => Investment::class,
            ]);
        });
    }

    /**
     * Process referral bonuses.
     */
    public function processReferralBonuses(): array
    {
        $unpaidBonuses = Referral::where('bonus_paid', false)
            ->whereHas('referred.investments')
            ->with(['referrer', 'referred'])
            ->get();

        $processed = [];
        $errors = [];
        $bonusAmount = 500; // ₦500 referral bonus

        foreach ($unpaidBonuses as $referral) {
            try {
                $this->payReferralBonus($referral, $bonusAmount);
                $processed[] = $referral;
            } catch (\Exception $e) {
                $errors[] = [
                    'referral' => $referral,
                    'error' => $e->getMessage()
                ];
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors
        ];
    }

    /**
     * Pay referral bonus to referrer.
     */
    public function payReferralBonus(Referral $referral, float $bonusAmount): void
    {
        if ($referral->bonus_paid) {
            throw new \Exception("Bonus already paid");
        }

        DB::transaction(function () use ($referral, $bonusAmount) {
            $referrer = $referral->referrer;

            // Update referrer balance and referral bonus
            $balanceBefore = $referrer->balance;
            $referrer->increment('balance', $bonusAmount);
            $referrer->increment('referral_bonus', $bonusAmount);

            // Mark bonus as paid
            $referral->update([
                'bonus_paid' => true,
                'bonus_amount' => $bonusAmount,
                'bonus_paid_at' => now(),
            ]);

            // Create transaction record
            Transaction::create([
                'user_id' => $referrer->id,
                'type' => 'referral_bonus',
                'amount' => $bonusAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $referrer->balance,
                'description' => "Referral bonus for {$referral->referred->name}",
                'reference' => 'REF' . strtoupper(uniqid()),
                'related_id' => $referral->id,
                'related_type' => Referral::class,
            ]);
        });
    }

    /**
     * Calculate withdrawal fee.
     */
    public function calculateWithdrawalFee(float $amount): float
    {
        return $amount * 0.10; // 10% fee
    }

    /**
     * Calculate net withdrawal amount after fee.
     */
    public function calculateNetWithdrawalAmount(float $amount): float
    {
        $fee = $this->calculateWithdrawalFee($amount);
        return $amount - $fee;
    }

    /**
     * Check if user can withdraw (recommit requirement).
     */
    public function canUserWithdraw(User $user): array
    {
        $lastInvestment = $user->investments()->latest()->first();
        
        if (!$lastInvestment) {
            return [
                'can_withdraw' => true,
                'recommit_required' => false,
                'recommit_amount' => null
            ];
        }

        // Check if user has recommitted since last investment
        $hasRecommitted = $user->investments()
            ->where('created_at', '>', $lastInvestment->created_at)
            ->where('amount', '>=', $lastInvestment->amount)
            ->exists();

        return [
            'can_withdraw' => $hasRecommitted,
            'recommit_required' => !$hasRecommitted,
            'recommit_amount' => $lastInvestment->amount
        ];
    }

    /**
     * Get user financial summary.
     */
    public function getUserFinancialSummary(User $user): array
    {
        return [
            'balance' => $user->balance,
            'referral_bonus' => $user->referral_bonus,
            'total_invested' => $user->investments->sum('amount'),
            'total_profits' => $user->investments->where('status', 'matured')->sum('profit_amount'),
            'active_investments' => $user->investments->where('status', 'active')->count(),
            'matured_investments' => $user->investments->where('status', 'matured')->count(),
            'total_withdrawn' => $user->withdrawals->where('status', 'approved')->sum('net_amount'),
            'pending_withdrawals' => $user->withdrawals->where('status', 'pending')->count(),
            'total_referrals' => $user->referrals->count(),
            'referral_bonus_earned' => $user->referralBonuses->where('bonus_paid', true)->sum('bonus_amount'),
        ];
    }

    /**
     * Get platform financial overview.
     */
    public function getPlatformFinancialOverview(): array
    {
        return [
            'total_users' => User::where('is_admin', false)->count(),
            'active_users' => User::where('is_admin', false)->where('is_active', true)->count(),
            'total_investments' => Investment::count(),
            'active_investments' => Investment::where('status', 'active')->count(),
            'total_invested' => Investment::sum('amount'),
            'total_profits_paid' => Investment::where('status', 'matured')->sum('profit_amount'),
            'total_user_balance' => User::where('is_admin', false)->sum('balance'),
            'total_referral_bonuses' => User::where('is_admin', false)->sum('referral_bonus'),
            'pending_withdrawals' => Withdrawal::where('status', 'pending')->count(),
            'total_withdrawn' => Withdrawal::where('status', 'approved')->sum('net_amount'),
        ];
    }
}

