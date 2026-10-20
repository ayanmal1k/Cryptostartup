<?php

namespace App\Services\Nft;

use App\Services\Nft\Contracts\NftMintingService;

/**
 * Local-development minting that touches no blockchain. It returns clearly-fake values
 * (tx hashes are prefixed "0xFAKE") so a fake can never be mistaken for a real on-chain tx.
 * This lets the full mint pipeline run and be tested before any thirdweb keys exist.
 */
class FakeMintingService implements NftMintingService
{
    public function mintTo(string $toAddress, string $metadataUri): MintResult
    {
        return new MintResult(
            tokenId: (string) random_int(1, 1000000),
            txHash: '0xFAKE' . bin2hex(random_bytes(30)),
            ownerAddress: $toAddress,
            chainId: (int) config('web3.chain_id', 80002),
            contractAddress: (string) (config('web3.contract_address') ?: '0xFAKEcontract000000000000000000000000000000'),
            status: MintResult::STATUS_MINTED,
        );
    }

    public function resolvePending(string $reference): ?MintResult
    {
        // The fake provider is synchronous; nothing is ever left pending.
        return null;
    }

    public function isLive(): bool
    {
        return false;
    }
}
