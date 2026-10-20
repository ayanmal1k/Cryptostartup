<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BountyCampaign extends Model
{
    use SoftDeletes;

    protected $table = 'bounty_campaigns';

    protected $fillable = [
        'creator_id', 'target_name', 'target_handle', 'target_description', 'target_avatar',
        'goal_amount', 'current_amount', 'deadline', 'status',
        'claimed_by_user_id', 'claim_status', 'claim_message',
        'funds_released', 'funds_released_at', 'moderator_notes',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'deadline' => 'datetime',
        'funds_released' => 'boolean',
        'funds_released_at' => 'datetime',
    ];

    // Campaign status
    const STATUS_OPEN = 'open';
    const STATUS_CLAIM_PENDING = 'claim_pending';
    const STATUS_RELEASED = 'released';
    const STATUS_REFUNDED = 'refunded';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Claim status
    const CLAIM_NONE = 'none';
    const CLAIM_PENDING = 'pending';
    const CLAIM_APPROVED = 'approved';
    const CLAIM_REJECTED = 'rejected';

    public function creator()
    {
        return $this->belongsTo('App\Model\User', 'creator_id');
    }

    public function claimer()
    {
        return $this->belongsTo('App\Model\User', 'claimed_by_user_id');
    }

    public function contributions()
    {
        return $this->hasMany(BountyContribution::class, 'bounty_campaign_id');
    }

    public function getProgressPercentageAttribute()
    {
        if (!$this->goal_amount || $this->goal_amount <= 0) {
            return 0;
        }
        return min(100, round(($this->current_amount / $this->goal_amount) * 100, 1));
    }

    public function isGoalReached()
    {
        return $this->goal_amount > 0 && $this->current_amount >= $this->goal_amount;
    }

    public function isExpired()
    {
        return $this->deadline && $this->deadline->isPast();
    }

    public function isOpenForContributions()
    {
        return $this->status === self::STATUS_OPEN && !$this->isExpired();
    }
}
