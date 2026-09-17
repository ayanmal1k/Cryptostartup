<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'creator_id',
        'requester_id',
        'type',
        'title',
        'description',
        'goal_amount',
        'current_amount',
        'price',
        'upfront_payment',
        'payment_transaction_id',
        'payment_received',
        'payment_received_at',
        'status',
        'message_id',
        'is_marketplace',
        'requires_voting',
        'total_votes',
        'approval_votes',
        'rejection_votes',
        'approval_percentage',
        'funds_released',
        'funds_released_at',
        'release_notes',
        'has_support_ticket',
        'support_status',
        'deadline',
        'delivery_video_path',
        'delivery_thumbnail_path',
        'delivered_at',
        'views_count',
    ];

    protected $casts = [
        'goal_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'price' => 'decimal:2',
        'upfront_payment' => 'decimal:2',
        'is_marketplace' => 'boolean',
        'payment_received' => 'boolean',
        'requires_voting' => 'boolean',
        'funds_released' => 'boolean',
        'has_support_ticket' => 'boolean',
        'approval_percentage' => 'decimal:2',
        'payment_received_at' => 'datetime',
        'funds_released_at' => 'datetime',
        'deadline' => 'datetime',
        'delivered_at' => 'datetime',
        'views_count' => 'integer',
    ];

    // Request types
    const TYPE_PRIVATE = 'private';
    const TYPE_PUBLIC = 'public';
    const TYPE_MARKETPLACE = 'marketplace';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the creator (user who will fulfill the request)
     */
    public function creator()
    {
        return $this->belongsTo(\App\User::class, 'creator_id');
    }

    /**
     * Get the requester (user who made the request)
     */
    public function requester()
    {
        return $this->belongsTo(\App\User::class, 'requester_id');
    }

    /**
     * Get the message (for private requests)
     */
    public function message()
    {
        return $this->belongsTo(\App\Model\UserMessage::class, 'message_id');
    }

    /**
     * Get all contributions for this request
     */
    public function contributions()
    {
        return $this->hasMany(CustomRequestContribution::class);
    }

    /**
     * Get completed contributions
     */
    public function completedContributions()
    {
        return $this->hasMany(CustomRequestContribution::class)->where('status', 'completed');
    }

    /**
     * Get all votes for this request
     */
    public function votes()
    {
        return $this->hasMany(CustomRequestVote::class);
    }

    /**
     * Get approval votes
     */
    public function approvalVotes()
    {
        return $this->hasMany(CustomRequestVote::class)->where('vote_type', CustomRequestVote::VOTE_APPROVE);
    }

    /**
     * Get rejection votes
     */
    public function rejectionVotes()
    {
        return $this->hasMany(CustomRequestVote::class)->where('vote_type', CustomRequestVote::VOTE_REJECT);
    }

    /**
     * Get payment transaction
     */
    public function paymentTransaction()
    {
        return $this->belongsTo(\App\Model\Transaction::class, 'payment_transaction_id');
    }

    /**
     * Get support tickets for this request
     */
    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Check if user has voted
     */
    public function hasUserVoted($userId)
    {
        return $this->votes()->where('voter_id', $userId)->exists();
    }

    /**
     * Get user's vote
     */
    public function getUserVote($userId)
    {
        return $this->votes()->where('voter_id', $userId)->first();
    }

    /**
     * Check if user can vote (must be requester or contributor)
     */
    public function canUserVote($userId)
    {
        // Requester can always vote
        if ($this->requester_id == $userId) {
            return true;
        }

        // Contributors can vote
        return $this->contributions()
            ->where('contributor_id', $userId)
            ->where('status', CustomRequestContribution::STATUS_COMPLETED)
            ->exists();
    }

    /**
     * Calculate if majority approval is reached
     */
    public function hasMajorityApproval()
    {
        if ($this->total_votes == 0) {
            return false;
        }

        // Majority means more than 50% approval
        return $this->approval_percentage > 50;
    }

    /**
     * Update voting statistics
     */
    public function updateVotingStats()
    {
        $this->total_votes = $this->votes()->count();
        $this->approval_votes = $this->approvalVotes()->count();
        $this->rejection_votes = $this->rejectionVotes()->count();

        if ($this->total_votes > 0) {
            $this->approval_percentage = ($this->approval_votes / $this->total_votes) * 100;
        } else {
            $this->approval_percentage = 0;
        }

        $this->save();
    }

    /**
     * Check if payment is required and received
     */
    public function isPaymentRequired()
    {
        return $this->upfront_payment > 0;
    }

    /**
     * Check if payment has been received
     */
    public function hasPaymentBeenReceived()
    {
        return $this->payment_received && $this->payment_received_at !== null;
    }

    /**
     * Check if funds can be released
     */
    public function canReleaseFunds()
    {
        // Must be accepted/completed
        if (!in_array($this->status, [self::STATUS_ACCEPTED, self::STATUS_COMPLETED])) {
            return false;
        }

        // If voting is required, must have majority approval
        if ($this->requires_voting) {
            return $this->hasMajorityApproval();
        }

        // For non-voting requests, creator can mark as completed
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Calculate progress percentage for marketplace requests
     */
    public function getProgressPercentageAttribute()
    {
        if ($this->goal_amount > 0) {
            return min(100, ($this->current_amount / $this->goal_amount) * 100);
        }
        return 0;
    }

    /**
     * Public URL of the delivered video, or null when there is none or the file is not on
     * disk. Rows can outlive their media (production lost its uploads once, and sample rows
     * name files that only exist where they were seeded), and a path that 404s renders as
     * a broken image or a dead player.
     */
    public function getDeliveryVideoUrlAttribute(): ?string
    {
        return $this->storageUrlIfPresent($this->delivery_video_path);
    }

    /** Public URL of the delivery thumbnail, or null when missing — see above. */
    public function getDeliveryThumbnailUrlAttribute(): ?string
    {
        return $this->storageUrlIfPresent($this->delivery_thumbnail_path);
    }

    protected function storageUrlIfPresent(?string $path): ?string
    {
        if (!$path || !is_file(public_path('storage/' . $path))) {
            return null;
        }

        return asset('storage/' . $path);
    }

    /**
     * Check if goal is reached
     */
    public function isGoalReached()
    {
        return $this->current_amount >= $this->goal_amount;
    }

    /**
     * Scope for marketplace requests
     */
    public function scopeMarketplace($query)
    {
        return $query->where('is_marketplace', true)->orWhere('type', self::TYPE_MARKETPLACE);
    }

    /**
     * Scope for public requests
     */
    public function scopePublic($query)
    {
        return $query->where('type', self::TYPE_PUBLIC);
    }

    /**
     * Scope for private requests
     */
    public function scopePrivate($query)
    {
        return $query->where('type', self::TYPE_PRIVATE);
    }
}
