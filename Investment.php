<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Investment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'investment_plan_id',
        'amount',
        'profit_amount',
        'total_return',
        'maturity_date',
        'status',
        'credited_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'total_return' => 'decimal:2',
        'maturity_date' => 'datetime',
        'credited_at' => 'datetime',
    ];

    /**
     * Get the user that owns the investment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the investment plan.
     */
    public function investmentPlan()
    {
        return $this->belongsTo(InvestmentPlan::class);
    }

    /**
     * Get the transactions for this investment.
     */
    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'related');
    }

    /**
     * Get the number of days remaining until maturity.
     */
    public function getDaysRemainingAttribute()
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $now = Carbon::now();
        $maturity = Carbon::parse($this->maturity_date);

        if ($maturity->isPast()) {
            return 0;
        }

        return $now->diffInDays($maturity);
    }

    /**
     * Check if the investment has matured.
     */
    public function hasMatured()
    {
        return $this->status === 'active' && Carbon::now()->gte($this->maturity_date);
    }

    /**
     * Get the progress percentage of the investment.
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->status !== 'active') {
            return 100;
        }

        $created = Carbon::parse($this->created_at);
        $maturity = Carbon::parse($this->maturity_date);
        $now = Carbon::now();

        $totalDuration = $created->diffInMinutes($maturity);
        $elapsed = $created->diffInMinutes($now);

        if ($elapsed >= $totalDuration) {
            return 100;
        }

        return ($elapsed / $totalDuration) * 100;
    }

    /**
     * Scope for active investments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for matured investments.
     */
    public function scopeMatured($query)
    {
        return $query->where('status', 'matured');
    }

    /**
     * Scope for investments that have reached maturity date.
     */
    public function scopeReachedMaturity($query)
    {
        return $query->where('maturity_date', '<=', now());
    }
}

