<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminWithdrawalController extends Controller
{
    /**
     * Display list of withdrawals.
     */
    public function index(Request $request)
    {
        $query = Withdrawal::with(['user', 'user.bankDetail']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search by user
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $withdrawals = $query->paginate(20);

        // Statistics
        $stats = [
            'pending' => Withdrawal::where('status', 'pending')->count(),
            'approved' => Withdrawal::where('status', 'approved')->count(),
            'declined' => Withdrawal::where('status', 'declined')->count(),
            'total_amount' => Withdrawal::where('status', 'approved')->sum('net_amount'),
        ];

        return view('admin.withdrawals.index', compact('withdrawals', 'stats'));
    }

    /**
     * Show withdrawal details.
     */
    public function show(Withdrawal $withdrawal)
    {
        $withdrawal->load(['user.bankDetail', 'processedBy']);
        
        // Check recommit status
        if ($withdrawal->recommit_required && $withdrawal->recommit_amount) {
            $hasRecommitted = $withdrawal->user->investments()
                ->where('created_at', '>', $withdrawal->created_at)
                ->where('amount', '>=', $withdrawal->recommit_amount)
                ->exists();
            
            $withdrawal->recommit_completed = $hasRecommitted;
        }

        return view('admin.withdrawals.show', compact('withdrawal'));
    }

    /**
     * Approve withdrawal.
     */
    public function approve(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be approved.');
        }

        $request->validate([
            'admin_comment' => 'nullable|string|max:500',
        ]);

        // Check recommit requirement
        if ($withdrawal->recommit_required && $withdrawal->recommit_amount) {
            $hasRecommitted = $withdrawal->user->investments()
                ->where('created_at', '>', $withdrawal->created_at)
                ->where('amount', '>=', $withdrawal->recommit_amount)
                ->exists();
            
            if (!$hasRecommitted) {
                return back()->with('error', 'User must recommit before withdrawal can be approved.');
            }
        }

        // Check if user has sufficient balance
        if ($withdrawal->user->balance < $withdrawal->amount) {
            return back()->with('error', 'User has insufficient balance for this withdrawal.');
        }

        DB::transaction(function () use ($withdrawal, $request) {
            $user = $withdrawal->user;
            
            // Deduct amount from user balance
            $balanceBefore = $user->balance;
            $user->decrement('balance', $withdrawal->amount);
            
            // Update withdrawal status
            $withdrawal->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => auth()->id(),
                'admin_comment' => $request->admin_comment,
            ]);

            // Update the existing transaction or create a new one
            $transaction = Transaction::where('related_id', $withdrawal->id)
                ->where('related_type', Withdrawal::class)
                ->first();

            if ($transaction) {
                $transaction->update([
                    'balance_after' => $user->balance,
                    'description' => "Withdrawal approved - ID: {$withdrawal->id}",
                ]);
            } else {
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'withdrawal',
                    'amount' => $withdrawal->amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $user->balance,
                    'description' => "Withdrawal approved - ID: {$withdrawal->id}",
                    'reference' => 'WDA' . strtoupper(uniqid()),
                    'related_id' => $withdrawal->id,
                    'related_type' => Withdrawal::class,
                ]);
            }
        });

        return back()->with('success', 'Withdrawal approved successfully.');
    }

    /**
     * Decline withdrawal.
     */
    public function decline(Request $request, Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'pending') {
            return back()->with('error', 'Only pending withdrawals can be declined.');
        }

        $request->validate([
            'admin_comment' => 'required|string|max:500',
        ]);

        $withdrawal->update([
            'status' => 'declined',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
            'admin_comment' => $request->admin_comment,
        ]);

        return back()->with('success', 'Withdrawal declined successfully.');
    }

    /**
     * Bulk approve withdrawals.
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'withdrawal_ids' => 'required|array',
            'withdrawal_ids.*' => 'exists:withdrawals,id',
        ]);

        $approved = 0;
        $errors = [];

        foreach ($request->withdrawal_ids as $id) {
            $withdrawal = Withdrawal::find($id);
            
            if ($withdrawal->status !== 'pending') {
                $errors[] = "Withdrawal #{$id} is not pending.";
                continue;
            }

            // Check recommit requirement
            if ($withdrawal->recommit_required && $withdrawal->recommit_amount) {
                $hasRecommitted = $withdrawal->user->investments()
                    ->where('created_at', '>', $withdrawal->created_at)
                    ->where('amount', '>=', $withdrawal->recommit_amount)
                    ->exists();
                
                if (!$hasRecommitted) {
                    $errors[] = "Withdrawal #{$id} requires recommit.";
                    continue;
                }
            }

            // Check balance
            if ($withdrawal->user->balance < $withdrawal->amount) {
                $errors[] = "Withdrawal #{$id} - insufficient user balance.";
                continue;
            }

            try {
                DB::transaction(function () use ($withdrawal) {
                    $user = $withdrawal->user;
                    
                    // Deduct amount from user balance
                    $balanceBefore = $user->balance;
                    $user->decrement('balance', $withdrawal->amount);
                    
                    // Update withdrawal status
                    $withdrawal->update([
                        'status' => 'approved',
                        'processed_at' => now(),
                        'processed_by' => auth()->id(),
                        'admin_comment' => 'Bulk approved',
                    ]);

                    // Update transaction
                    $transaction = Transaction::where('related_id', $withdrawal->id)
                        ->where('related_type', Withdrawal::class)
                        ->first();

                    if ($transaction) {
                        $transaction->update([
                            'balance_after' => $user->balance,
                            'description' => "Withdrawal approved - ID: {$withdrawal->id}",
                        ]);
                    }
                });

                $approved++;
            } catch (\Exception $e) {
                $errors[] = "Withdrawal #{$id} failed: " . $e->getMessage();
            }
        }

        $message = "{$approved} withdrawals approved successfully.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', $errors);
        }

        return back()->with($approved > 0 ? 'success' : 'error', $message);
    }
}

