<?php

namespace App\Services\Nft;

use App\Services\Nft\Contracts\MetadataStorageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Stand-in for IPFS during local development. Stores the metadata JSON on the public disk and
 * returns ordinary https URLs. Lets the mint pipeline run end-to-end with no external account;
 * swap for ThirdwebStorageService (real IPFS) by setting WEB3_STORAGE_DRIVER=thirdweb.
 */
class LocalFakeStorageService implements MetadataStorageService
{
    public function uploadFile(string $diskPath, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url($diskPath);
    }

    public function uploadMetadata(array $metadata): string
    {
        $path = 'nfts/metadata/' . Str::uuid()->toString() . '.json';
        Storage::disk('public')->put($path, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return Storage::disk('public')->url($path);
    }

    public function isLive(): bool
    {
        return false;
    }
}
