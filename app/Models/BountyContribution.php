<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BountyContribution extends Model
{
    protected $table = 'bounty_contributions';

    protected $fillable = [
        'bounty_campaign_id', 'contributor_id', 'amount', 'status', 'message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    const STATUS_HELD = 'held';
    const STATUS_RELEASED = 'released';
    const STATUS_REFUNDED = 'refunded';

    public function campaign()
    {
        return $this->belongsTo(BountyCampaign::class, 'bounty_campaign_id');
    }

    public function contributor()
    {
        return $this->belongsTo('App\Model\User', 'contributor_id');
    }
}
