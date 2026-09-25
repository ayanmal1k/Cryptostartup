<?php

namespace App\Services\Nft;

use App\Model\User;
use App\Models\NFT;
use App\Models\NFTListing;
use App\Models\NFTTransaction;
use App\Services\Nft\Contracts\MarketplaceService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Local-development marketplace. Simulates an on-chain sale entirely in the DB: it transfers
 * ownership (owner_address), flips listing/NFT statuses, and records the sale with the
 * fee/royalty/seller split in the ledger. No real funds move (settlement is on-chain crypto in
 * production); the split is recorded for transparency and marked simulated. Sale tx hashes are
 * prefixed "0xFAKE" so they can never be mistaken for real ones.
 */
class FakeMarketplaceService implements MarketplaceService
{
    public function list(NFT $nft, User $seller, float $price): NFTListing
    {
        $this->assertOwner($nft, $seller);
        if (!$nft->isMinted()) {
            throw new RuntimeException('This NFT is not minted yet.');
        }
        if ($nft->activeListing) {
            throw new RuntimeException('This NFT is already listed.');
        }
        if ($price <= 0) {
            throw new RuntimeException('Price must be greater than zero.');
        }

        return DB::transaction(function () use ($nft, $seller, $price) {
            $listing = NFTListing::create([
                'nft_id' => $nft->id,
                'seller_id' => $seller->id,
                'token_id' => $nft->token_id,
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
        if ($listing->status !== 'active') {
            throw new RuntimeException('This listing is no longer available.');
        }
        if ((int) $listing->seller_id === (int) $buyer->id) {
            throw new RuntimeException('You cannot buy your own listing.');
        }
        if (empty($buyer->wallet_address)) {
            throw new RuntimeException('Connect your wallet before buying.');
        }

        $nft = $listing->nft;
        if (!$nft) {
            throw new RuntimeException('The NFT for this listing no longer exists.');
        }

        return DB::transaction(function () use ($listing, $buyer, $nft) {
            $price = (float) $listing->price;
            $platformFee = round($price * (int) config('web3.marketplace_fee_bps', 250) / 10000, 8);
            $royalty = round($price * (int) ($nft->royalty_bps ?? 0) / 10000, 8);
            $sellerProceeds = round($price - $platformFee - $royalty, 8);

            $fromAddress = $nft->owner_address;
            $isResale = (int) $listing->seller_id !== (int) $nft->user_id;

            // Transfer ownership to the buyer; NFT is now owned (not listed).
            $nft->update([
                'owner_address' => $buyer->wallet_address,
                'status' => NFT::STATUS_MINTED,
            ]);
            $listing->update(['status' => 'sold', 'sold_at' => now()]);

            return NFTTransaction::create([
                'nft_id' => $nft->id,
                'listing_id' => $listing->id,
                'seller_id' => $listing->seller_id,
                'buyer_id' => $buyer->id,
                'token_id' => $nft->token_id,
                'chain_id' => $nft->chain_id,
                'contract_address' => $nft->contract_address,
                'type' => $isResale ? 'resale' : 'sale',
                'price' => $price,
                'fee' => $platformFee,
                'from_address' => $fromAddress,
                'to_address' => $buyer->wallet_address,
                'transaction_hash' => '0xFAKEsale' . bin2hex(random_bytes(24)),
                'status' => 'confirmed',
                'confirmed_at' => now(),
                'metadata' => [
                    'simulated' => true,
                    'platform_fee' => $platformFee,
                    'creator_royalty' => $royalty,
                    'seller_proceeds' => $sellerProceeds,
                ],
            ]);
        });
    }

    public function isLive(): bool
    {
        return false;
    }

    private function assertOwner(NFT $nft, User $seller): void
    {
        if (empty($seller->wallet_address)) {
            throw new RuntimeException('Connect your wallet first.');
        }
        if (strtolower((string) $nft->owner_address) !== strtolower($seller->wallet_address)) {
            throw new RuntimeException('You do not own this NFT.');
        }
    }
}
