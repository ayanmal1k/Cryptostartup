<?php

namespace App\Models;

use App\Model\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class NFT extends Model
{
    use HasFactory;

    protected $table = 'nfts';

    /** Lifecycle states. The chain is the source of truth; these mirror it for fast queries. */
    public const STATUS_PENDING_MINT = 'pending_mint'; // queued, not yet on-chain
    public const STATUS_MINTED = 'minted';             // exists on-chain, owned, not listed
    public const STATUS_LISTED = 'listed';             // listed for sale
    public const STATUS_SOLD = 'sold';                 // sold (ownership moved)
    public const STATUS_TRANSFERRED = 'transferred';   // transferred outside a sale
    public const STATUS_MINT_FAILED = 'mint_failed';   // mint tx failed

    protected $fillable = [
        'user_id',
        'owner_user_id',
        'owner_address',
        'token_id',
        'name',
        'description',
        'token_uri',
        'metadata_uri',
        'mint_tx_hash',
        'image_url',
        'contract_address',
        'chain_id',
        'collection_name',
        'source_type',
        'source_id',
        'media_type',
        'royalty_bps',
        'metadata',
        'status',
    ];

    protected $casts = [
        'metadata' => 'array',
        'chain_id' => 'integer',
        'royalty_bps' => 'integer',
    ];

    /** Find the NFT minted from a given piece of content, if any. */
    public static function mintedFor(string $sourceType, $sourceId): ?self
    {
        return static::where('source_type', $sourceType)
            ->where('source_id', (string) $sourceId)
            ->first();
    }

    /**
     * The platform account that created/minted this NFT. (Creator, not necessarily the
     * current on-chain owner — that's owner_address, which moves on every transfer.)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Alias for clarity in creator-economy contexts. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The account that owns this NFT now (buyers become owners; see owner_user_id). */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    /** Owner account id, falling back to the creator for rows that predate owner_user_id. */
    public function ownerId(): int
    {
        return (int) ($this->owner_user_id ?: $this->user_id);
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->ownerId() === (int) $user->id;
    }

    /** True when sales are priced in dollars and paid with platform credits. */
    public static function settlesInCredits(): bool
    {
        return config('web3.settlement', 'credits') !== 'onchain';
    }

    /**
     * Picture to show for this NFT, or null when the file is gone.
     *
     * Local storage URLs are rebuilt from their /storage/ path for this host: rows carry
     * the absolute URL of wherever they were minted (production has rows pointing at
     * localhost), and uploads can outlive their files. Remote (IPFS/CDN) URLs pass through.
     */
    public function getDisplayImageUrlAttribute(): ?string
    {
        return self::localMediaUrl($this->image_url);
    }

    /** Playable video for video NFTs, with the same host/file checks as the image. */
    public function getVideoUrlAttribute(): ?string
    {
        return $this->media_type === 'video'
            ? self::localMediaUrl($this->metadata['animation_url'] ?? null)
            : null;
    }

    private static function localMediaUrl(?string $url): ?string
    {
        if (!$url) {
            return null;
        }
        $pos = strpos($url, '/storage/');
        if ($pos === false) {
            return $url;
        }
        $relative = substr($url, $pos + strlen('/storage/'));
        $relative = strtok($relative, '?');

        // public/storage is normally a link to storage/app/public; accept either location.
        $exists = is_file(public_path('storage/' . $relative)) || is_file(storage_path('app/public/' . $relative));

        return $exists ? asset('storage/' . $relative) : null;
    }

    /**
     * The platform account whose wallet currently owns the token on-chain, if that wallet
     * is linked to a user. Resolved by matching owner_address to users.wallet_address.
     */
    public function currentOwner(): HasOne
    {
        return $this->hasOne(User::class, 'wallet_address', 'owner_address');
    }

    // NOTE: foreign keys are specified explicitly because Eloquent would otherwise infer them
    // from the class basename "NFT" as "n_f_t_id" (Str::snake adds underscores per capital).
    public function listings(): HasMany
    {
        return $this->hasMany(NFTListing::class, 'nft_id');
    }

    public function activeListing(): HasOne
    {
        return $this->hasOne(NFTListing::class, 'nft_id')->where('status', 'active');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(NFTTransaction::class, 'nft_id');
    }

    /** True once the token exists on-chain (has a real token id + mint tx). */
    public function isMinted(): bool
    {
        return $this->status !== self::STATUS_PENDING_MINT
            && $this->status !== self::STATUS_MINT_FAILED
            && !is_null($this->token_id);
    }

    public function isPendingMint(): bool
    {
        return $this->status === self::STATUS_PENDING_MINT;
    }

    /** Scope: tokens currently owned by a given wallet address (case-insensitive). */
    public function scopeOwnedByAddress(Builder $query, ?string $address): Builder
    {
        return $query->whereRaw('LOWER(owner_address) = ?', [strtolower((string) $address)]);
    }

    /** Scope: NFTs owned by an account (creator counts as owner until one is recorded). */
    public function scopeOwnedByUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($q) use ($userId) {
            $q->where('owner_user_id', $userId)
              ->orWhere(fn ($w) => $w->whereNull('owner_user_id')->where('user_id', $userId));
        });
    }

    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
