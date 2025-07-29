<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'account_name',
        'account_number',
        'bank_name',
        'bank_code',
        'is_verified',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    /**
     * Get the user who owns this bank detail.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get list of Nigerian banks.
     */
    public static function getNigerianBanks()
    {
        return [
            'Access Bank' => '044',
            'Citibank Nigeria' => '023',
            'Diamond Bank' => '063',
            'Ecobank Nigeria' => '050',
            'Fidelity Bank' => '070',
            'First Bank of Nigeria' => '011',
            'First City Monument Bank' => '214',
            'Guaranty Trust Bank' => '058',
            'Heritage Bank' => '030',
            'Keystone Bank' => '082',
            'Polaris Bank' => '076',
            'Providus Bank' => '101',
            'Stanbic IBTC Bank' => '221',
            'Standard Chartered Bank' => '068',
            'Sterling Bank' => '232',
            'Union Bank of Nigeria' => '032',
            'United Bank For Africa' => '033',
            'Unity Bank' => '215',
            'Wema Bank' => '035',
            'Zenith Bank' => '057',
        ];
    }
}

