<?php

namespace App\Services\Nft\Contracts;

/**
 * Abstraction over decentralized storage for NFT assets + metadata (IPFS in production).
 * The local fake implementation uses the public disk so the whole pipeline works without
 * any external account; the thirdweb implementation pins to real IPFS.
 */
interface MetadataStorageService
{
    /**
     * Store a file that already exists on a Laravel filesystem disk and return a public URI
     * to it (ipfs://... in production, an https URL for the local fake).
     */
    public function uploadFile(string $diskPath, string $disk = 'public'): string;

    /**
     * Store an ERC-721 metadata JSON document and return its tokenURI.
     *
     * @param array<string,mixed> $metadata e.g. ['name'=>..., 'description'=>..., 'image'=>...]
     */
    public function uploadMetadata(array $metadata): string;

    public function isLive(): bool;
}
