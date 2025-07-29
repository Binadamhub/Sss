<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Investment;
use App\Models\Withdrawal;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    /**
     * Display admin dashboard with overview statistics.
     */
    public function index()
    {
        // User statistics
        $totalUsers = User::where('is_admin', false)->count();
        $activeUsers = User::where('is_admin', false)->where('is_active', true)->count();
        $newUsersToday = User::where('is_admin', false)->whereDate('created_at', today())->count();
        $newUsersThisWeek = User::where('is_admin', false)->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

        // Investment statistics
        $totalInvestments = Investment::count();
        $activeInvestments = Investment::where('status', 'active')->count();
        $maturedInvestments = Investment::where('status', 'matured')->count();
        $totalInvested = Investment::sum('amount');
        $totalProfitsPaid = Investment::where('status', 'matured')->sum('profit_amount');

        // Withdrawal statistics
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();
        $approvedWithdrawals = Withdrawal::where('status', 'approved')->count();
        $totalWithdrawn = Withdrawal::where('status', 'approved')->sum('net_amount');
        $pendingWithdrawalAmount = Withdrawal::where('status', 'pending')->sum('amount');

        // Financial overview
        $totalBalance = User::where('is_admin', false)->sum('balance');
        $totalReferralBonus = User::where('is_admin', false)->sum('referral_bonus');

        // Recent activities
        $recentUsers = User::where('is_admin', false)
            ->latest()
            ->take(5)
            ->get();

        $recentInvestments = Investment::with(['user', 'investmentPlan'])
            ->latest()
            ->take(5)
            ->get();

        $recentWithdrawals = Withdrawal::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Chart data for investments over time (last 30 days)
        $investmentChartData = Investment::selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount) as total')
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Chart data for user registrations (last 30 days)
        $userChartData = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('is_admin', false)
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return view('admin.dashboard', compact(
            'totalUsers',
            'activeUsers',
            'newUsersToday',
            'newUsersThisWeek',
            'totalInvestments',
            'activeInvestments',
            'maturedInvestments',
            'totalInvested',
            'totalProfitsPaid',
            'pendingWithdrawals',
            'approvedWithdrawals',
            'totalWithdrawn',
            'pendingWithdrawalAmount',
            'totalBalance',
            'totalReferralBonus',
            'recentUsers',
            'recentInvestments',
            'recentWithdrawals',
            'investmentChartData',
            'userChartData'
        ));
    }
}

