<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\BankDetail;

class BankDetailController extends Controller
{
    /**
     * Show bank details form.
     */
    public function create()
    {
        $user = Auth::user();
        $bankDetail = $user->bankDetail;
        $nigerianBanks = BankDetail::getNigerianBanks();
        
        return view('bank-details.create', compact('user', 'bankDetail', 'nigerianBanks'));
    }

    /**
     * Store or update bank details.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|min:10|max:10',
            'bank_name' => 'required|string|max:255',
        ]);

        // Get bank code from the banks array
        $nigerianBanks = BankDetail::getNigerianBanks();
        $bankCode = $nigerianBanks[$request->bank_name] ?? null;

        $bankDetail = $user->bankDetail;
        
        if ($bankDetail) {
            // Update existing bank details
            $bankDetail->update([
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'bank_code' => $bankCode,
                'is_verified' => false, // Reset verification status
            ]);
            
            $message = 'Bank details updated successfully.';
        } else {
            // Create new bank details
            BankDetail::create([
                'user_id' => $user->id,
                'account_name' => $request->account_name,
                'account_number' => $request->account_number,
                'bank_name' => $request->bank_name,
                'bank_code' => $bankCode,
            ]);
            
            $message = 'Bank details added successfully.';
        }

        return redirect()->route('profile.edit')->with('success', $message);
    }

    /**
     * Delete bank details.
     */
    public function destroy()
    {
        $user = Auth::user();
        $bankDetail = $user->bankDetail;
        
        if ($bankDetail) {
            $bankDetail->delete();
            return redirect()->route('profile.edit')->with('success', 'Bank details deleted successfully.');
        }
        
        return redirect()->route('profile.edit')->with('error', 'No bank details found.');
    }
}

