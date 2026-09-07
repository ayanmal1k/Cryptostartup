<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A drop (loot box) a user has earned. Unopened while `opened_at` is null; the
 * reward columns are filled in at the moment it is opened, never before -- the roll
 * happens server-side on open so the contents cannot be read ahead of the animation.
 */
class UserDrop extends Model
{
    protected $table = 'user_drops';

    protected $fillable = [
        'user_id', 'tier', 'source', 'reward_key', 'reward_rarity', 'reward_payload', 'opened_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
    ];

    public function scopeUnopened($query)
    {
        return $query->whereNull('opened_at');
    }

    public function user()
    {
        return $this->belongsTo('App\Model\User', 'user_id');
    }
}
