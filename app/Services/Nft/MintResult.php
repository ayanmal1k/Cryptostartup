<?php

namespace App\Services\Nft;

/**
 * The outcome of a mint request, normalized across providers.
 *
 * Some providers (e.g. thirdweb Engine) are asynchronous: the mint is queued and the real
 * token id / tx hash only exist once the transaction confirms. In that case status is
 * 'pending' and $reference holds the provider's tracking id (Engine queueId) so a later
 * sync/poll can resolve tokenId, txHash and ownerAddress. Synchronous/fake providers return
 * status 'minted' with everything populated.
 */
class MintResult
{
    public const STATUS_MINTED = 'minted';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';

    public function __construct(
        public ?string $tokenId,
        public ?string $txHash,
        public string $ownerAddress,
        public int $chainId,
        public string $contractAddress,
        public string $status = self::STATUS_MINTED,
        public ?string $reference = null
    ) {
    }

    public function isMinted(): bool
    {
        return $this->status === self::STATUS_MINTED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
