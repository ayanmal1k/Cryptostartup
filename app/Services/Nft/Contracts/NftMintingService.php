<?php

namespace App\Services\Nft\Contracts;

use App\Services\Nft\MintResult;

/**
 * Abstraction over "mint an ERC-721 to a wallet". Implementations decide HOW (fake local
 * stub, thirdweb Engine, raw RPC, ...). The rest of the app depends only on this interface,
 * so swapping providers never touches controllers/jobs.
 */
interface NftMintingService
{
    /**
     * Mint a token whose metadata lives at $metadataUri to $toAddress.
     *
     * @param string $toAddress    destination wallet (the new on-chain owner)
     * @param string $metadataUri  tokenURI (ideally ipfs://...) pointing at the metadata JSON
     */
    public function mintTo(string $toAddress, string $metadataUri): MintResult;

    /**
     * Resolve a still-pending mint (async providers) into a final MintResult, or null if it
     * is not confirmed yet. Synchronous providers may simply return the same result.
     */
    public function resolvePending(string $reference): ?MintResult;

    /** Whether this provider is fully configured to talk to a real chain. */
    public function isLive(): bool;
}
