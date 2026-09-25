<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One user's instance of one daily quest, for one date.
 */
class UserQuest extends Model
{
    protected $table = 'user_quests';

    protected $fillable = [
        'user_id', 'quest_key', 'quest_date', 'target', 'progress', 'completed_at', 'claimed_at',
    ];

    protected $casts = [
        'quest_date' => 'date',
        'completed_at' => 'datetime',
        'claimed_at' => 'datetime',
        'target' => 'integer',
        'progress' => 'integer',
    ];

    public function getIsCompleteAttribute(): bool
    {
        return $this->completed_at !== null;
    }

    public function getIsClaimedAttribute(): bool
    {
        return $this->claimed_at !== null;
    }

    /** Progress as a 0-100 percentage, clamped. */
    public function getPercentAttribute(): int
    {
        $target = max(1, (int) $this->target);

        return (int) min(100, round($this->progress / $target * 100));
    }

    public function user()
    {
        return $this->belongsTo('App\Model\User', 'user_id');
    }
}
