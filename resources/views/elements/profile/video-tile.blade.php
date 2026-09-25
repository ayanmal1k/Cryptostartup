@php
    /**
     * 9:16 video tile on the profile grid.
     * $video    - App\Models\Video
     * $isOwner  - show draft/processing state to the owner
     * $isRepost - mark reposted clips
     */
    $thumb = $video->thumbnail_url;
    $src = $video->video_url;
    $views = (int) ($video->views_count ?? 0);
    $viewsLabel = $views >= 1000000 ? round($views / 1000000, 1) . 'M' : ($views >= 1000 ? round($views / 1000, 1) . 'K' : $views);
@endphp
<a href="{{ url('/feed?video=' . $video->id) }}" class="pp-tile" aria-label="{{ $video->title ?: __('Video') }}">
    @if($thumb)
        <img src="{{ $thumb }}" alt="" loading="lazy" decoding="async">
    @elseif($src)
        {{-- No thumbnail on file: the first frame stands in for one. --}}
        <video src="{{ $src }}#t=0.5" muted playsinline preload="metadata" aria-hidden="true"></video>
    @endif
    <span class="pp-tile__scrim" aria-hidden="true"></span>
    @if(!empty($isRepost))
        <span class="pp-tile__tag" title="{{ __('Repost') }}">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m17 2 4 4-4 4M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4M21 13v2a3 3 0 0 1-3 3H3"/></svg>
        </span>
    @elseif(!empty($isOwner) && $video->status && $video->status !== 'published')
        <span class="pp-tile__tag pp-tile__tag--text">{{ __($video->status_label) }}</span>
    @endif
    <span class="pp-tile__views">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
        {{ $viewsLabel }}
    </span>
</a>
