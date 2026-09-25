<?php

namespace App\Services\Nft;

use App\Model\User;
use App\Models\NFT;
use App\Models\NFTListing;
use App\Models\NFTTransaction;
use App\Services\Nft\Contracts\MarketplaceService;
use RuntimeException;

/**
 * Real marketplace via thirdweb Marketplace V3 (through Engine).
 *
 * SCAFFOLD: the on-chain flow is more involved than minting — it needs token approvals, a
 * deployed Marketplace V3 contract, and a Transfer/sale event sync to mirror ownership back to
 * the DB (not yet built). Until that and credentials are in place, these methods throw a clear
 * message and the app uses FakeMarketplaceService. Documented Engine routes are noted inline so
 * this can be completed when going live.
 */
class ThirdwebMarketplaceService implements MarketplaceService
{
    public function list(NFT $nft, User $seller, float $price): NFTListing
    {
        // POST /marketplace/{chain}/{marketplace}/direct-listings/create
        //   { assetContractAddress, tokenId, pricePerToken, quantity:1, currencyContractAddress }
        // (requires the seller to have approved the marketplace for the token first)
        throw $this->pending();
    }

    public function cancel(NFTListing $listing, User $seller): void
    {
        // POST /marketplace/{chain}/{marketplace}/direct-listings/cancel { listingId }
        throw $this->pending();
    }

    public function buy(NFTListing $listing, User $buyer): NFTTransaction
    {
        // POST /marketplace/{chain}/{marketplace}/direct-listings/buy-from-listing
        //   { listingId, quantity:1, buyer }
        // then mirror the resulting Transfer event into nfts.owner_address + nft_transactions.
        throw $this->pending();
    }

    public function isLive(): bool
    {
        return !empty(config('web3.engine.url'))
            && !empty(config('web3.engine.access_token'))
            && !empty(config('web3.marketplace_address'))
            && !empty(config('web3.chain_id'));
    }

    private function pending(): RuntimeException
    {
        return new RuntimeException(
            'thirdweb Marketplace V3 is not wired yet (needs the deployed contract + on-chain '
            . 'event sync). Configure it to enable real on-chain trading.'
        );
    }
}
