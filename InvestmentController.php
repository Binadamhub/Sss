<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\InvestmentPlan;
use App\Models\Investment;
use App\Models\Transaction;
use App\Models\Referral;

class InvestmentController extends Controller
{
    /**
     * Display investment plans and user's investments.
     */
    public function index()
    {
        $user = Auth::user();
        $investmentPlans = InvestmentPlan::active()->get();
        $userInvestments = $user->investments()
            ->with('investmentPlan')
            ->latest()
            ->paginate(10);

        return view('investments.index', compact('investmentPlans', 'userInvestments'));
    }

    /**
     * Show investment form for a specific plan.
     */
    public function create(InvestmentPlan $plan)
    {
        if (!$plan->is_active) {
            return redirect()->route('investments.index')
                ->with('error', 'This investment plan is not available.');
        }

        return view('investments.create', compact('plan'));
    }

    /**
     * Store a new investment.
     */
    public function store(Request $request, InvestmentPlan $plan)
    {
        $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:' . $plan->minimum_amount,
                $plan->maximum_amount ? 'max:' . $plan->maximum_amount : '',
            ],
        ]);

        $user = Auth::user();
        $amount = $request->amount;

        // Check if user has sufficient balance
        if ($user->balance < $amount) {
            return back()->with('error', 'Insufficient balance. Please fund your account.');
        }

        DB::transaction(function () use ($user, $plan, $amount) {
            // Calculate profit and total return
            $profitAmount = ($amount * $plan->profit_percentage) / 100;
            $totalReturn = $amount + $profitAmount;
            $maturityDate = $plan->getMaturityDate();

            // Create investment
            $investment = Investment::create([
                'user_id' => $user->id,
                'investment_plan_id' => $plan->id,
                'amount' => $amount,
                'profit_amount' => $profitAmount,
                'total_return' => $totalReturn,
                'maturity_date' => $maturityDate,
            ]);

            // Deduct amount from user balance and create transaction
            Transaction::createTransaction(
                $user->id,
                'investment',
                $amount,
                "Investment in {$plan->name} - ID: {$investment->id}",
                $investment->id,
                Investment::class
            );

            // Check if this is the user's first investment and they were referred
            $isFirstInvestment = $user->investments()->count() === 1;
            if ($isFirstInvestment && $user->referred_by) {
                $this->processReferralBonus($user);
            }
        });

        return redirect()->route('investments.index')
            ->with('success', 'Investment created successfully! Your returns will be credited on maturity.');
    }

    /**
     * Process referral bonus for first investment.
     */
    private function processReferralBonus($user)
    {
        $referral = Referral::where('referred_id', $user->id)
            ->where('bonus_paid', false)
            ->first();

        if ($referral) {
            // Credit referral bonus to referrer
            Transaction::createTransaction(
                $referral->referrer_id,
                'referral_bonus',
                $referral->bonus_amount,
                "Referral bonus for {$user->name}'s first investment",
                $referral->id,
                Referral::class
            );

            // Update referral record
            $referral->update([
                'bonus_paid' => true,
                'bonus_paid_at' => now(),
            ]);

            // Update referrer's referral bonus balance
            $referrer = $referral->referrer;
            $referrer->increment('referral_bonus', $referral->bonus_amount);
        }
    }

    /**
     * Show investment details.
     */
    public function show(Investment $investment)
    {
        // Ensure user can only view their own investments
        if ($investment->user_id !== Auth::id()) {
            abort(403);
        }

        $investment->load('investmentPlan');
        return view('investments.show', compact('investment'));
    }
}

