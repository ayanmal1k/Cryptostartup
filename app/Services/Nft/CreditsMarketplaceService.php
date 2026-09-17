<?php

namespace App\Services\Nft;

use App\Model\User;
use App\Model\Wallet;
use App\Models\NFT;
use App\Models\NFTListing;
use App\Models\NFTTransaction;
use App\Services\Nft\Contracts\MarketplaceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * NFT marketplace priced in dollars and settled in platform credits — the same money fans
 * already use for tips and unlocks. Ownership is the buyer's account (nfts.owner_user_id); no
 * crypto wallet is involved, so buying an NFT feels like buying anything else on the site.
 *
 * Money safety mirrors CreatorCoinService: one DB transaction per sale, and every wallet row
 * that changes is locked first (in user-id order, so two crossing sales cannot deadlock).
 *
 * Split per sale: the platform keeps web3.marketplace_fee_bps; on a RESALE the creator is paid
 * their royalty_bps; the seller receives the rest. On the first sale the seller is the creator,
 * so there is no separate royalty — they simply receive price minus the fee.
 */
class CreditsMarketplaceService implements MarketplaceService
{
    public const MIN_PRICE = 1.00;
    public const MAX_PRICE = 100000.00;

    public function list(NFT $nft, User $seller, float $price): NFTListing
    {
        if (!$nft->isOwnedBy($seller)) {
            throw new RuntimeException('You do not own this NFT.');
        }
        if ($nft->isPendingMint() || $nft->status === NFT::STATUS_MINT_FAILED) {
            throw new RuntimeException('This NFT is still being created. Try again in a moment.');
        }
        if ($nft->activeListing) {
            throw new RuntimeException('This NFT is already for sale.');
        }

        $price = round($price, 2);
        if ($price < self::MIN_PRICE || $price > self::MAX_PRICE) {
            throw new RuntimeException('Price must be between $' . number_format(self::MIN_PRICE, 2)
                . ' and $' . number_format(self::MAX_PRICE, 0) . '.');
        }

        return DB::transaction(function () use ($nft, $seller, $price) {
            $listing = NFTListing::create([
                'nft_id' => $nft->id,
                'seller_id' => $seller->id,
                'token_id' => (string) ($nft->token_id ?? ''),
                'price' => $price,
                'status' => 'active',
                'listed_at' => now(),
            ]);
            $nft->update(['status' => NFT::STATUS_LISTED]);

            return $listing;
        });
    }

    public function cancel(NFTListing $listing, User $seller): void
    {
        if ((int) $listing->seller_id !== (int) $seller->id) {
            throw new RuntimeException('This is not your listing.');
        }
        if ($listing->status !== 'active') {
            throw new RuntimeException('This listing is no longer active.');
        }

        DB::transaction(function () use ($listing) {
            $listing->update(['status' => 'cancelled']);
            if ($listing->nft) {
                $listing->nft->update(['status' => NFT::STATUS_MINTED]);
            }
        });
    }

    public function buy(NFTListing $listing, User $buyer): NFTTransaction
    {
        return DB::transaction(function () use ($listing, $buyer) {
            // Re-read under lock so two buyers cannot both win the same listing.
            $listing = NFTListing::whereKey($listing->id)->lockForUpdate()->first();
            if (!$listing || $listing->status !== 'active') {
                throw new RuntimeException('Someone got there first — this is no longer for sale.');
            }
            if ((int) $listing->seller_id === (int) $buyer->id) {
                throw new RuntimeException('You cannot buy your own NFT.');
            }

            $nft = NFT::whereKey($listing->nft_id)->lockForUpdate()->first();
            if (!$nft) {
                throw new RuntimeException('This NFT no longer exists.');
            }
            if ($nft->ownerId() !== (int) $listing->seller_id) {
                // Ownership moved since it was listed, so the listing is stale.
                throw new RuntimeException('This NFT is no longer for sale.');
            }

            $price = round((float) $listing->price, 2);
            $fee = round($price * (int) config('web3.marketplace_fee_bps', 250) / 10000, 2);
            $creatorId = (int) $nft->user_id;
            $sellerId = (int) $listing->seller_id;
            $isResale = $sellerId !== $creatorId;
            $royalty = $isResale ? round($price * (int) ($nft->royalty_bps ?? 0) / 10000, 2) : 0.0;
            $sellerProceeds = round($price - $fee - $royalty, 2);

            $ids = array_unique(array_filter([(int) $buyer->id, $sellerId, $isResale ? $creatorId : 0]));
            sort($ids);
            $wallets = [];
            foreach ($ids as $id) {
                $wallets[$id] = $this->lockedWallet($id);
            }

            if ((float) $wallets[(int) $buyer->id]->total < $price) {
                throw new RuntimeException('Not enough credits — this costs $' . number_format($price, 2)
                    . '. Add funds to your wallet and try again.');
            }

            $wallets[(int) $buyer->id]->update(['total' => round((float) $wallets[(int) $buyer->id]->total - $price, 2)]);
            $wallets[$sellerId]->update(['total' => round((float) $wallets[$sellerId]->total + $sellerProceeds, 2)]);
            if ($royalty > 0) {
                $wallets[$creatorId]->update(['total' => round((float) $wallets[$creatorId]->total + $royalty, 2)]);
            }

            $nft->update([
                'owner_user_id' => $buyer->id,
                'status' => NFT::STATUS_MINTED,
            ]);
            $listing->update(['status' => 'sold', 'sold_at' => now()]);

            return NFTTransaction::create([
                'nft_id' => $nft->id,
                'listing_id' => $listing->id,
                'seller_id' => $sellerId,
                'buyer_id' => $buyer->id,
                'token_id' => (string) ($nft->token_id ?? ''),
                'chain_id' => $nft->chain_id,
                'contract_address' => $nft->contract_address,
                'type' => $isResale ? 'resale' : 'sale',
                'price' => $price,
                'fee' => $fee,
                // Settled off-chain in credits; the id marks it as such and stays unique.
                'transaction_hash' => 'credits-' . Str::uuid(),
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'metadata' => [
                    'settlement' => 'credits',
                    'currency' => 'USD',
                    'platform_fee' => $fee,
                    'creator_royalty' => $royalty,
                    'seller_proceeds' => $sellerProceeds,
                ],
            ]);
        });
    }

    public function isLive(): bool
    {
        return true;
    }

    private function lockedWallet(int $userId): Wallet
    {
        $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        if (!$wallet) {
            Wallet::create(['id' => (string) Str::uuid(), 'user_id' => $userId, 'total' => 0]);
            $wallet = Wallet::where('user_id', $userId)->lockForUpdate()->first();
        }

        return $wallet;
    }
}
