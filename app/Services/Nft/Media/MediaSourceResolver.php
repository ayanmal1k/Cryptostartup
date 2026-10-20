<?php

namespace App\Services\Nft\Media;

use App\Model\Attachment;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use RuntimeException;

/**
 * Turns a (source type, id) pair into a normalized ResolvedMedia, resolving where the underlying
 * file actually lives on a Laravel disk. Supports the videos library and locally-stored image
 * attachments (the two sources chosen for the first release).
 */
class MediaSourceResolver
{
    public const TYPE_VIDEO = 'video';
    public const TYPE_ATTACHMENT = 'attachment';

    public function resolve(string $type, string $id): ResolvedMedia
    {
        return match ($type) {
            self::TYPE_VIDEO => $this->fromVideo($id),
            self::TYPE_ATTACHMENT => $this->fromAttachment($id),
            default => throw new InvalidArgumentException("Unsupported media source type: {$type}"),
        };
    }

    private function fromVideo(string $id): ResolvedMedia
    {
        $video = Video::findOrFail($id);

        [$videoDisk, $videoPath] = $this->locate($video->video_path);

        // Use the thumbnail as the display image; fall back to the video itself if none.
        if ($video->thumbnail_path) {
            [$imageDisk, $imagePath] = $this->locate($video->thumbnail_path);
        } else {
            [$imageDisk, $imagePath] = [$videoDisk, $videoPath];
        }

        return new ResolvedMedia(
            sourceType: self::TYPE_VIDEO,
            sourceId: (string) $video->id,
            ownerId: (int) $video->user_id,
            mediaType: 'video',
            name: $video->title ?: ('Video #' . $video->id),
            description: $video->description,
            imageDisk: $imageDisk,
            imagePath: $imagePath,
            animationDisk: $videoDisk,
            animationPath: $videoPath,
        );
    }

    private function fromAttachment(string $id): ResolvedMedia
    {
        $attachment = Attachment::findOrFail($id);

        if ($attachment->attachmentType !== 'image') {
            throw new RuntimeException('Only image attachments can be minted here; use the video library for videos.');
        }
        if ((int) $attachment->driver !== Attachment::PUBLIC_DRIVER) {
            throw new RuntimeException('This image is stored remotely; minting remote-stored media is not supported yet.');
        }

        [$disk, $path] = $this->publicPathFromUrl($attachment->path);

        return new ResolvedMedia(
            sourceType: self::TYPE_ATTACHMENT,
            sourceId: (string) $attachment->id,
            ownerId: (int) $attachment->user_id,
            mediaType: 'image',
            name: 'Photo ' . substr((string) $attachment->id, 0, 8),
            description: null,
            imageDisk: $disk,
            imagePath: $path,
        );
    }

    /**
     * Find which disk a stored relative path lives on. Defaults to 'public' (in fake/dev the file
     * need not exist, since the fake storage driver only builds a URL).
     *
     * @return array{0:string,1:string} [disk, relativePath]
     */
    private function locate(?string $path): array
    {
        $path = ltrim((string) $path, '/');
        foreach (['public', 'local'] as $disk) {
            if ($path !== '' && Storage::disk($disk)->exists($path)) {
                return [$disk, $path];
            }
        }

        return ['public', $path];
    }

    /**
     * Convert a public attachment URL (…/storage/<relative>) into [public, <relative>].
     *
     * @return array{0:string,1:string}
     */
    private function publicPathFromUrl(string $url): array
    {
        $urlPath = parse_url($url, PHP_URL_PATH) ?: $url;
        $marker = '/storage/';
        $pos = strpos($urlPath, $marker);
        $relative = $pos !== false ? substr($urlPath, $pos + strlen($marker)) : ltrim($urlPath, '/');

        return ['public', ltrim($relative, '/')];
    }
}
