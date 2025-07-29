<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Investment;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessMaturedInvestments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'investments:process-matured';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process matured investments and credit profits to users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing matured investments...');

        // Get all active investments that have matured
        $maturedInvestments = Investment::where('status', 'active')
            ->where('maturity_date', '<=', now())
            ->with(['user', 'investmentPlan'])
            ->get();

        if ($maturedInvestments->isEmpty()) {
            $this->info('No matured investments found.');
            return;
        }

        $processed = 0;
        $errors = 0;

        foreach ($maturedInvestments as $investment) {
            try {
                DB::transaction(function () use ($investment) {
                    $user = $investment->user;
                    
                    // Calculate total return (principal + profit)
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
                        'reference' => 'INV' . strtoupper(uniqid()),
                        'related_id' => $investment->id,
                        'related_type' => Investment::class,
                    ]);
                });

                $this->info("Processed investment #{$investment->id} for user {$investment->user->name} - ₦" . number_format($investment->total_return, 2));
                $processed++;

            } catch (\Exception $e) {
                $this->error("Failed to process investment #{$investment->id}: " . $e->getMessage());
                $errors++;
            }
        }

        $this->info("Processing completed. Processed: {$processed}, Errors: {$errors}");
    }
}

