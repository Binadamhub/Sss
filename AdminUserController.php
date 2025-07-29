<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUserController extends Controller
{
    /**
     * Display list of users.
     */
    public function index(Request $request)
    {
        $query = User::where('is_admin', false)
            ->with(['investments', 'withdrawals', 'referrals']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Sort
        $sortBy = $request->get('sort', 'created_at');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $users = $query->paginate(20);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show user details.
     */
    public function show(User $user)
    {
        if ($user->is_admin) {
            abort(404);
        }

        $user->load([
            'investments.investmentPlan',
            'withdrawals',
            'referrals',
            'referralBonuses',
            'transactions',
            'bankDetail'
        ]);

        // Calculate statistics
        $stats = [
            'total_invested' => $user->investments->sum('amount'),
            'total_profits' => $user->investments->where('status', 'matured')->sum('profit_amount'),
            'active_investments' => $user->investments->where('status', 'active')->count(),
            'total_withdrawn' => $user->withdrawals->where('status', 'approved')->sum('net_amount'),
            'pending_withdrawals' => $user->withdrawals->where('status', 'pending')->count(),
            'total_referrals' => $user->referrals->count(),
            'referral_bonus_earned' => $user->referralBonuses->where('bonus_paid', true)->sum('bonus_amount'),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    /**
     * Toggle user active status.
     */
    public function toggleStatus(User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Cannot modify admin user.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return back()->with('success', "User has been {$status} successfully.");
    }

    /**
     * Show balance adjustment form.
     */
    public function editBalance(User $user)
    {
        if ($user->is_admin) {
            abort(404);
        }

        return view('admin.users.edit-balance', compact('user'));
    }

    /**
     * Adjust user balance.
     */
    public function updateBalance(Request $request, User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Cannot modify admin user.');
        }

        $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $amount = $request->amount;
        $type = $request->type;
        $reason = $request->reason;

        DB::transaction(function () use ($user, $amount, $type, $reason) {
            $balanceBefore = $user->balance;
            
            if ($type === 'credit') {
                $user->increment('balance', $amount);
                $balanceAfter = $user->balance;
                $transactionType = 'manual_credit';
            } else {
                if ($user->balance < $amount) {
                    throw new \Exception('Insufficient balance for debit.');
                }
                $user->decrement('balance', $amount);
                $balanceAfter = $user->balance;
                $transactionType = 'manual_debit';
            }

            // Create transaction record
            Transaction::create([
                'user_id' => $user->id,
                'type' => $transactionType,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'description' => "Manual {$type} by admin: {$reason}",
                'reference' => 'ADM' . strtoupper(uniqid()),
                'processed_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.users.show', $user)
            ->with('success', "User balance {$type}ed successfully.");
    }

    /**
     * Delete user account.
     */
    public function destroy(User $user)
    {
        if ($user->is_admin) {
            return back()->with('error', 'Cannot delete admin user.');
        }

        // Check if user has active investments
        if ($user->investments()->where('status', 'active')->exists()) {
            return back()->with('error', 'Cannot delete user with active investments.');
        }

        // Check if user has pending withdrawals
        if ($user->withdrawals()->where('status', 'pending')->exists()) {
            return back()->with('error', 'Cannot delete user with pending withdrawals.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }
}

