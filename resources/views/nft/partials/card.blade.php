@php
    /**
     * NFT tile — the same shape as a challenge tile: the picture is the product, with the
     * price on it and who made it underneath. No token ids, chains or addresses.
     *
     * $nft      - NFT with `creator` and `activeListing` loaded
     * $note     - optional override for the corner chip (e.g. "Creating…")
     */
    $listing = $nft->activeListing;
    $image = $nft->display_image_url;
    $isVideo = $nft->media_type === 'video';
    $note = $note ?? null;
@endphp
<a href="{{ route('nft.show', $nft->id) }}" class="nx-tile {{ $image ? '' : 'nx-tile--blank' }}">
    <div class="nx-tile__media" @if($isVideo && $nft->video_url) data-dx-preview="{{ $nft->video_url }}" @endif>
        @if($image)
            <img src="{{ $image }}" alt="" loading="lazy" decoding="async">
        @else
            <span class="nx-tile__blank" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            </span>
        @endif
        <span class="nx-tile__scrim"></span>
    </div>

    <div class="nx-tile__top">
        @if($note)
            <span class="nx-tile__chip">{{ $note }}</span>
        @elseif($listing)
            <span class="nx-tile__chip nx-tile__chip--money">${{ number_format((float) $listing->price, 2) }}</span>
        @else
            <span class="nx-tile__chip">{{ __('Not for sale') }}</span>
        @endif
        @if($isVideo)
            <span class="nx-tile__chip nx-tile__chip--icon" aria-label="{{ __('Video') }}">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
            </span>
        @endif
    </div>

    <div class="nx-tile__info">
        <span class="nx-tile__title">{{ $nft->name }}</span>
        <span class="nx-tile__by">
            @if($nft->creator)
                <img src="{{ $nft->creator->avatar }}" alt="" class="nx-tile__avatar" loading="lazy">
            @endif
            <span>{{ $nft->creator->name ?? __('Unknown creator') }}</span>
        </span>
    </div>
</a>
