<?php

namespace App\Services\Nft;

use App\Jobs\MintNft;
use App\Model\User;
use App\Models\NFT;
use App\Services\Nft\Contracts\MetadataStorageService;
use App\Services\Nft\Media\MediaSourceResolver;
use App\Services\Nft\Media\ResolvedMedia;
use RuntimeException;

/**
 * Converts existing content (a video or an image) into a 1-of-1 NFT owned by its creator.
 * Reuses the storage + MintNft pipeline already built for fresh-upload minting — the only new
 * work is sourcing the file, producing the right ERC-721 metadata (animation_url for video,
 * royalty hints), and linking the NFT back to its source so it can't be minted twice.
 */
class MediaNftService
{
    public function __construct(
        protected MetadataStorageService $storage,
        protected MediaSourceResolver $resolver
    ) {
    }

    public function resolve(string $type, string $id): ResolvedMedia
    {
        return $this->resolver->resolve($type, $id);
    }

    /**
     * @param array{name?:string,description?:string,royalty_bps?:int,price_hint?:float|null} $opts
     */
    public function mintFromMedia(User $user, ResolvedMedia $media, array $opts = []): NFT
    {
        if ((int) $media->ownerId !== (int) $user->id) {
            throw new RuntimeException('You can only mint your own content.');
        }
        // With dollar pricing the account owns the NFT; a linked wallet is optional and the
        // token is held for the user when they have none.
        $mintTo = $user->wallet_address ?: (NFT::settlesInCredits() ? $this->custodialAddress() : null);
        if (!$mintTo) {
            throw new RuntimeException('Connect your wallet before minting — an NFT must be owned by a wallet address.');
        }
        if (NFT::mintedFor($media->sourceType, $media->sourceId)) {
            throw new RuntimeException('This item has already been minted as an NFT.');
        }

        $name = (isset($opts['name']) && trim($opts['name']) !== '') ? trim($opts['name']) : $media->name;
        $description = $opts['description'] ?? $media->description;
        $royaltyBps = isset($opts['royalty_bps'])
            ? (int) $opts['royalty_bps']
            : (int) config('web3.default_royalty_bps', 1000);

        // Pin the display image (thumbnail for video, the picture for an image).
        $imageUri = $this->storage->uploadFile($media->imagePath, $media->imageDisk);

        $metadata = [
            'name' => $name,
            'description' => (string) $description,
            'image' => $imageUri,
            'attributes' => [
                ['trait_type' => 'Media type', 'value' => $media->mediaType],
                ['trait_type' => 'Source', 'value' => $media->sourceType],
            ],
            // Royalty hints honoured by thirdweb / ERC-2981-aware marketplaces.
            'seller_fee_basis_points' => $royaltyBps,
            'fee_recipient' => $user->wallet_address ?: $mintTo,
        ];

        $animationUri = null;
        if ($media->isVideo() && $media->animationPath) {
            $animationUri = $this->storage->uploadFile($media->animationPath, (string) $media->animationDisk);
            $metadata['animation_url'] = $animationUri; // standard field for playable video NFTs
        }

        $metadataUri = $this->storage->uploadMetadata($metadata);

        $nft = NFT::create([
            'user_id' => $user->id,
            'owner_user_id' => $user->id,
            'owner_address' => $mintTo,
            'name' => $name,
            'description' => $description,
            'token_uri' => $metadataUri,
            'metadata_uri' => $metadataUri,
            'image_url' => $imageUri,
            'chain_id' => config('web3.chain_id'),
            'contract_address' => config('web3.contract_address') ?: null,
            'status' => NFT::STATUS_PENDING_MINT,
            'source_type' => $media->sourceType,
            'source_id' => $media->sourceId,
            'media_type' => $media->mediaType,
            'royalty_bps' => $royaltyBps,
            'metadata' => [
                'price_hint' => $opts['price_hint'] ?? null,
                'animation_url' => $animationUri,
            ],
        ]);

        MintNft::dispatch($nft->id, $mintTo);

        return $nft;
    }

    /**
     * Address that holds tokens for users without a wallet of their own: the configured
     * custodial/backend wallet, or with none configured (local fake minting) a clearly-fake marker.
     */
    private function custodialAddress(): string
    {
        return (string) (config('web3.custodial_address') ?: '0xFAKEcustodial00000000000000000000000000');
    }
}
