<?php

namespace App\Services\Nft;

use App\Services\Nft\Contracts\NftMintingService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Real minting via thirdweb Engine (https://portal.thirdweb.com/engine).
 *
 * Engine is an HTTP API that signs + sends gas-sponsored transactions from a managed backend
 * wallet, which is exactly what a PHP backend needs (thirdweb has no official PHP SDK). Mints
 * are ASYNC: Engine returns a queueId immediately; the real tokenId/txHash are fetched later
 * via resolvePending() (polled by the MintNft job or a webhook).
 *
 * NOTE: untested until thirdweb credentials + a deployed contract are configured. Endpoints
 * follow Engine's documented ERC-721 routes; verify against your Engine version when wiring keys.
 */
class ThirdwebEngineService implements NftMintingService
{
    public function mintTo(string $toAddress, string $metadataUri): MintResult
    {
        $this->assertConfigured();

        $chain = (int) config('web3.chain_id');
        $contract = config('web3.contract_address');

        // POST /contract/{chain}/{contract}/erc721/mint-to  -> { result: { queueId } }
        $response = $this->client()->post(
            $this->endpoint("/contract/{$chain}/{$contract}/erc721/mint-to"),
            [
                'receiver' => $toAddress,
                // Engine accepts either an inline metadata object or a tokenURI string.
                'metadata' => $metadataUri,
            ]
        );

        if ($response->failed()) {
            Log::error('thirdweb mint-to failed', ['status' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('thirdweb Engine mint failed: ' . $response->body());
        }

        $queueId = data_get($response->json(), 'result.queueId');

        return new MintResult(
            tokenId: null,
            txHash: null,
            ownerAddress: $toAddress,
            chainId: $chain,
            contractAddress: (string) $contract,
            status: MintResult::STATUS_PENDING,
            reference: $queueId,
        );
    }

    public function resolvePending(string $reference): ?MintResult
    {
        $this->assertConfigured();

        // GET /transaction/status/{queueId} -> { result: { status, transactionHash, ... } }
        $response = $this->client()->get($this->endpoint("/transaction/status/{$reference}"));
        if ($response->failed()) {
            return null;
        }

        $result = data_get($response->json(), 'result', []);
        $status = data_get($result, 'status');

        if ($status === 'mined' || $status === 'confirmed') {
            return new MintResult(
                tokenId: (string) data_get($result, 'tokenId', ''),
                txHash: (string) data_get($result, 'transactionHash', ''),
                ownerAddress: (string) data_get($result, 'toAddress', ''),
                chainId: (int) config('web3.chain_id'),
                contractAddress: (string) config('web3.contract_address'),
                status: MintResult::STATUS_MINTED,
                reference: $reference,
            );
        }

        if ($status === 'errored' || $status === 'cancelled') {
            return new MintResult(
                tokenId: null,
                txHash: null,
                ownerAddress: '',
                chainId: (int) config('web3.chain_id'),
                contractAddress: (string) config('web3.contract_address'),
                status: MintResult::STATUS_FAILED,
                reference: $reference,
            );
        }

        // still queued / sent — not resolved yet
        return null;
    }

    public function isLive(): bool
    {
        return !empty(config('web3.engine.url'))
            && !empty(config('web3.engine.access_token'))
            && !empty(config('web3.contract_address'))
            && !empty(config('web3.chain_id'));
    }

    private function assertConfigured(): void
    {
        if (!$this->isLive()) {
            throw new RuntimeException(
                'thirdweb Engine is not configured. Set WEB3_ENGINE_URL, WEB3_ENGINE_ACCESS_TOKEN, '
                . 'NFT_MARKETPLACE_CONTRACT_ADDRESS and WEB3_CHAIN_ID in .env.'
            );
        }
    }

    private function client()
    {
        $http = Http::withToken(config('web3.engine.access_token'))->acceptJson();
        if ($wallet = config('web3.engine.backend_wallet')) {
            $http = $http->withHeaders(['x-backend-wallet-address' => $wallet]);
        }
        return $http;
    }

    private function endpoint(string $path): string
    {
        return rtrim((string) config('web3.engine.url'), '/') . $path;
    }
}
