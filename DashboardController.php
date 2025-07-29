<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Investment;
use App\Models\Transaction;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user statistics
        $totalInvestments = $user->investments()->sum('amount');
        $activeInvestments = $user->activeInvestments()->count();
        $totalReferrals = $user->referrals()->count();
        $totalEarnings = $user->transactions()
            ->whereIn('type', ['profit', 'referral_bonus'])
            ->sum('amount');
        
        // Get recent investments
        $recentInvestments = $user->investments()
            ->with('investmentPlan')
            ->latest()
            ->take(5)
            ->get();
        
        // Get recent transactions
        $recentTransactions = $user->transactions()
            ->latest()
            ->take(10)
            ->get();

        return view('dashboard', compact(
            'user',
            'totalInvestments',
            'activeInvestments',
            'totalReferrals',
            'totalEarnings',
            'recentInvestments',
            'recentTransactions'
        ));
    }
}

