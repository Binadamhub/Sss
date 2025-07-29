<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Referral extends Model
{
    use HasFactory;

    protected $fillable = [
        'referrer_id',
        'referred_id',
        'bonus_amount',
        'bonus_paid',
        'bonus_paid_at',
    ];

    protected $casts = [
        'bonus_amount' => 'decimal:2',
        'bonus_paid' => 'boolean',
        'bonus_paid_at' => 'datetime',
    ];

    /**
     * Get the user who made the referral.
     */
    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_id');
    }

    /**
     * Get the user who was referred.
     */
    public function referred()
    {
        return $this->belongsTo(User::class, 'referred_id');
    }

    /**
     * Get unpaid referral bonuses.
     */
    public function scopeUnpaid($query)
    {
        return $query->where('bonus_paid', false);
    }

    /**
     * Get paid referral bonuses.
     */
    public function scopePaid($query)
    {
        return $query->where('bonus_paid', true);
    }
}

