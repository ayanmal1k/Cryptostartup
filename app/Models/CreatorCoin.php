<?php

namespace App\Models;

use App\Model\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * A creator's non-cashable loyalty-points currency. See the migration for the legal rationale:
 * fixed purchase price, no market/resale/cash-out.
 */
class CreatorCoin extends Model
{
    protected $table = 'creator_coins';

    protected $fillable = [
        'creator_user_id',
        'name',
        'symbol',
        'logo',
        'description',
        'price_per_point',
        'platform_fee_percentage',
        'points_issued',
        'is_active',
    ];

    protected $casts = [
        'price_per_point' => 'decimal:8',
        'platform_fee_percentage' => 'decimal:2',
        'points_issued' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    protected $appends = ['logo_url'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_user_id');
    }

    public function balances(): HasMany
    {
        return $this->hasMany(CreatorCoinBalance::class, 'creator_coin_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CreatorCoinTransaction::class, 'creator_coin_id');
    }

    /** A holder's current points balance for this coin. */
    public function balanceFor(int $userId): float
    {
        $row = $this->balances()->where('user_id', $userId)->first();

        return $row ? (float) $row->balance : 0.0;
    }

    /** Number of holders with a positive balance. */
    public function getHoldersCountAttribute(): int
    {
        return $this->balances()->where('balance', '>', 0)->count();
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        return asset('storage/' . ltrim($this->logo, '/'));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
