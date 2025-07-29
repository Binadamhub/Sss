<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvestmentPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'profit_percentage',
        'duration_days',
        'minimum_amount',
        'maximum_amount',
        'is_active',
        'description',
    ];

    protected $casts = [
        'profit_percentage' => 'decimal:2',
        'minimum_amount' => 'decimal:2',
        'maximum_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get investments for this plan.
     */
    public function investments()
    {
        return $this->hasMany(Investment::class);
    }

    /**
     * Calculate expected return for given amount.
     */
    public function calculateReturn($amount)
    {
        $profit = ($amount * $this->profit_percentage) / 100;
        return $amount + $profit;
    }

    /**
     * Calculate maturity date from now.
     */
    public function getMaturityDate()
    {
        return now()->addDays($this->duration_days);
    }

    /**
     * Get active plans only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

