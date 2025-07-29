<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Referral;

class ReferralController extends Controller
{
    /**
     * Display referral page with user's referral information.
     */
    public function index()
    {
        $user = Auth::user();
        
        // Get user's referrals
        $referrals = $user->referrals()
            ->with(['investments' => function($query) {
                $query->selectRaw('user_id, COUNT(*) as total_investments, SUM(amount) as total_invested')
                      ->groupBy('user_id');
            }])
            ->paginate(10);
        
        // Get referral bonuses earned
        $referralBonuses = $user->referralBonuses()
            ->with('referred')
            ->latest()
            ->paginate(10);
        
        // Calculate statistics
        $totalReferrals = $user->referrals()->count();
        $totalBonusEarned = $user->referralBonuses()->where('bonus_paid', true)->sum('bonus_amount');
        $pendingBonuses = $user->referralBonuses()->where('bonus_paid', false)->count();
        
        return view('referrals.index', compact(
            'user',
            'referrals',
            'referralBonuses',
            'totalReferrals',
            'totalBonusEarned',
            'pendingBonuses'
        ));
    }

    /**
     * Get referral statistics for API.
     */
    public function stats()
    {
        $user = Auth::user();
        
        return response()->json([
            'referral_code' => $user->referral_code,
            'referral_link' => $user->referral_link,
            'total_referrals' => $user->referrals()->count(),
            'total_bonus_earned' => $user->referralBonuses()->where('bonus_paid', true)->sum('bonus_amount'),
            'pending_bonuses' => $user->referralBonuses()->where('bonus_paid', false)->count(),
        ]);
    }
}

