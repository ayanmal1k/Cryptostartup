<?php

namespace App\Services\Nft\Contracts;

use App\Model\User;
use App\Models\NFT;
use App\Models\NFTListing;
use App\Models\NFTTransaction;

/**
 * Abstraction over the NFT marketplace (list / cancel / buy). A local Fake provider simulates
 * the flow in the DB for dev; the thirdweb provider performs it on-chain via Marketplace V3.
 * The rest of the app depends only on this interface.
 */
interface MarketplaceService
{
    /** Put an owned, minted NFT up for sale at $price (in the chain's native token). */
    public function list(NFT $nft, User $seller, float $price): NFTListing;

    /** Cancel an active listing (seller only). */
    public function cancel(NFTListing $listing, User $seller): void;

    /**
     * Buy an active listing: transfer ownership to the buyer and record the sale (with the
     * platform-fee / creator-royalty / seller-proceeds split). Returns the sale transaction.
     */
    public function buy(NFTListing $listing, User $buyer): NFTTransaction;

    /** Whether this provider is configured to operate a real on-chain marketplace. */
    public function isLive(): bool;
}
