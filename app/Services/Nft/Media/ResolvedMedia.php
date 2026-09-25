<?php

namespace App\Services\Nft\Media;

/**
 * A normalized view of an existing piece of content that is about to become an NFT, regardless
 * of whether it came from the videos table or an image attachment.
 *
 * For an image: imagePath is the picture; animationPath is null.
 * For a video:  imagePath is the thumbnail/poster; animationPath is the video file (-> animation_url).
 */
class ResolvedMedia
{
    public function __construct(
        public string $sourceType,       // 'video' | 'attachment'
        public string $sourceId,
        public int $ownerId,
        public string $mediaType,        // 'image' | 'video'
        public string $name,
        public ?string $description,
        public string $imageDisk,
        public string $imagePath,
        public ?string $animationDisk = null,
        public ?string $animationPath = null
    ) {
    }

    public function isVideo(): bool
    {
        return $this->mediaType === 'video';
    }
}
