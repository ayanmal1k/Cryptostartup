<?php

namespace App\Jobs;

use App\Models\NFT;
use App\Models\NFTTransaction;
use App\Services\Nft\Contracts\NftMintingService;
use App\Services\Nft\MintResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Mints a pending NFT to its owner's wallet via the configured NftMintingService, then
 * mirrors the on-chain result (real token id, owner address, tx hash) back into the DB and
 * records a provenance row. Idempotent: it only acts on rows still in pending_mint.
 */
class MintNft implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 10;

    public function __construct(public int $nftId, public string $toAddress)
    {
    }

    public function handle(NftMintingService $minting): void
    {
        $nft = NFT::find($this->nftId);
        if (!$nft || $nft->status !== NFT::STATUS_PENDING_MINT) {
            return; // already minted/failed, or deleted
        }

        $result = $minting->mintTo($this->toAddress, $nft->metadata_uri ?: $nft->token_uri);

        if ($result->isPending()) {
            // Async provider (thirdweb Engine): store the queue reference + provisional chain
            // info. A later sync/poll (added in the live-keys phase) resolves the final values.
            $nft->update([
                'owner_address' => $result->ownerAddress,
                'contract_address' => $result->contractAddress,
                'chain_id' => $result->chainId,
                'metadata' => array_merge($nft->metadata ?? [], ['mint_reference' => $result->reference]),
            ]);
            return;
        }

        if ($result->status === MintResult::STATUS_FAILED) {
            $nft->update(['status' => NFT::STATUS_MINT_FAILED]);
            return;
        }

        $nft->update([
            'token_id' => $result->tokenId,
            'mint_tx_hash' => $result->txHash,
            'owner_address' => $result->ownerAddress,
            'contract_address' => $result->contractAddress,
            'chain_id' => $result->chainId,
            'status' => NFT::STATUS_MINTED,
        ]);

        NFTTransaction::create([
            'nft_id' => $nft->id,
            'token_id' => $result->tokenId,
            'chain_id' => $result->chainId,
            'contract_address' => $result->contractAddress,
            'type' => 'mint',
            'transaction_hash' => $result->txHash,
            'from_address' => '0x0000000000000000000000000000000000000000', // mint = from zero address
            'to_address' => $result->ownerAddress,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error('MintNft job failed', ['nft_id' => $this->nftId, 'error' => $e->getMessage()]);
        $nft = NFT::find($this->nftId);
        if ($nft && $nft->status === NFT::STATUS_PENDING_MINT) {
            $nft->update(['status' => NFT::STATUS_MINT_FAILED]);
        }
    }
}
