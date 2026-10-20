<?php

namespace App\Models;

use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Immutable ledger row for a points movement. Create rows; never update them.
 */
class CreatorCoinTransaction extends Model
{
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SPEND = 'spend';
    public const TYPE_GRANT = 'grant';
    public const TYPE_REFUND = 'refund';
    public const TYPE_ADJUST = 'adjust';

    protected $table = 'creator_coin_transactions';

    protected $fillable = [
        'creator_coin_id',
        'user_id',
        'type',
        'points',
        'balance_after',
        'credits_amount',
        'platform_fee',
        'counterparty_user_id',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected $casts = [
        'points' => 'decimal:8',
        'balance_after' => 'decimal:8',
        'credits_amount' => 'decimal:8',
        'platform_fee' => 'decimal:8',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(CreatorCoin::class, 'creator_coin_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function counterparty(): BelongsTo
    {
        return $this->belongsTo(User::class, 'counterparty_user_id');
    }
}
