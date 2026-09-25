<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One row per XP/spark award. Exists for two reasons: it is the audit trail for a
 * currency users care about, and it is what makes per-day earn caps possible --
 * without it there is nothing stopping someone farming XP by posting comments in a loop.
 */
class XpEvent extends Model
{
    protected $table = 'xp_events';

    protected $fillable = [
        'user_id', 'action', 'xp', 'sparks', 'subject_type', 'subject_id',
    ];

    protected $casts = [
        'xp' => 'integer',
        'sparks' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo('App\Model\User', 'user_id');
    }
}
