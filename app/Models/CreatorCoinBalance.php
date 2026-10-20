<?php

namespace App\Models;

use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorCoinBalance extends Model
{
    protected $table = 'creator_coin_balances';

    protected $fillable = [
        'creator_coin_id',
        'user_id',
        'balance',
    ];

    protected $casts = [
        'balance' => 'decimal:8',
    ];

    public function coin(): BelongsTo
    {
        return $this->belongsTo(CreatorCoin::class, 'creator_coin_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
