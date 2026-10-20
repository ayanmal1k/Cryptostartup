<?php

namespace App\Services\Nft;

use App\Services\Nft\Contracts\MetadataStorageService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Real IPFS pinning via thirdweb storage (Engine's /ipfs/upload route). Returns ipfs:// URIs.
 *
 * NOTE: untested until thirdweb credentials are configured; the upload route shape follows
 * Engine's documented /ipfs/upload endpoint. Verify against your Engine version when wiring keys.
 */
class ThirdwebStorageService implements MetadataStorageService
{
    public function uploadFile(string $diskPath, string $disk = 'public'): string
    {
        $this->assertConfigured();

        $contents = Storage::disk($disk)->get($diskPath);
        $filename = basename($diskPath);

        $response = Http::withToken(config('web3.engine.access_token'))
            ->attach('file', $contents, $filename)
            ->post($this->endpoint('/ipfs/upload'));

        if ($response->failed()) {
            throw new RuntimeException('thirdweb IPFS file upload failed: ' . $response->body());
        }

        return $this->firstUri($response->json());
    }

    public function uploadMetadata(array $metadata): string
    {
        $this->assertConfigured();

        $response = Http::withToken(config('web3.engine.access_token'))
            ->attach('file', json_encode($metadata, JSON_UNESCAPED_SLASHES), 'metadata.json')
            ->post($this->endpoint('/ipfs/upload'));

        if ($response->failed()) {
            throw new RuntimeException('thirdweb IPFS metadata upload failed: ' . $response->body());
        }

        return $this->firstUri($response->json());
    }

    public function isLive(): bool
    {
        return !empty(config('web3.engine.url')) && !empty(config('web3.engine.access_token'));
    }

    private function firstUri(?array $json): string
    {
        $uri = data_get($json, 'result.IpfsHash')
            ? 'ipfs://' . data_get($json, 'result.IpfsHash')
            : data_get($json, 'result.uri') ?? data_get($json, 'IpfsUri');

        if (!$uri) {
            throw new RuntimeException('thirdweb IPFS upload returned no URI: ' . json_encode($json));
        }

        return (string) $uri;
    }

    private function assertConfigured(): void
    {
        if (!$this->isLive()) {
            throw new RuntimeException('thirdweb storage is not configured (WEB3_ENGINE_URL / WEB3_ENGINE_ACCESS_TOKEN).');
        }
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('web3.engine.url'), '/') . $path;
    }
}
