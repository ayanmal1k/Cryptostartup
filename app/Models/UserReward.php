<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * A user's inventory: what came out of their drops. One row per reward type, with a
 * quantity, so opening five of the same thing does not create five rows.
 */
class UserReward extends Model
{
    protected $table = 'user_rewards';

    protected $fillable = [
        'user_id', 'reward_key', 'quantity', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'quantity' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('quantity', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            });
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function user()
    {
        return $this->belongsTo('App\Model\User', 'user_id');
    }
}
