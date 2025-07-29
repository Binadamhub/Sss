<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Referral;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class ProcessReferralBonuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'referrals:process-bonuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process referral bonuses for users whose referrals have made their first investment';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing referral bonuses...');

        // Get all unpaid referral bonuses where the referred user has made at least one investment
        $unpaidBonuses = Referral::where('bonus_paid', false)
            ->whereHas('referred.investments')
            ->with(['referrer', 'referred'])
            ->get();

        if ($unpaidBonuses->isEmpty()) {
            $this->info('No unpaid referral bonuses found.');
            return;
        }

        $processed = 0;
        $errors = 0;
        $bonusAmount = 500; // ₦500 referral bonus

        foreach ($unpaidBonuses as $referral) {
            try {
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

                $this->info("Paid ₦{$bonusAmount} referral bonus to {$referral->referrer->name} for referring {$referral->referred->name}");
                $processed++;

            } catch (\Exception $e) {
                $this->error("Failed to process referral bonus for {$referral->referrer->name}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Processing completed. Processed: {$processed}, Errors: {$errors}");
    }
}

