@php
    /**
     * 9:16 tile for the challenge wall.
     * $cr - CustomRequest with `creator` loaded (and optionally `backers_count`)
     */
    // Both are null when the file is not on disk, so a missing upload becomes a text tile
    // instead of a broken image.
    $videoUrl = $cr->delivery_video_url;
    $thumb = $cr->delivery_thumbnail_url;
    $goal = (float) ($cr->goal_amount ?? 0);
    $raised = (float) ($cr->current_amount ?? 0);
    $amount = $raised > 0 ? $raised : ($goal > 0 ? $goal : (float) $cr->price);
    $pct = $goal > 0 ? (int) min(100, round($raised / $goal * 100)) : 0;
    $isOpen = in_array($cr->status, ['pending', 'accepted'], true);
    $creator = $cr->creator;
    $views = (int) ($cr->views_count ?? 0);
@endphp
<a href="{{ route('custom-requests.show', $cr->id) }}" class="ch-tile {{ $thumb ? '' : 'ch-tile--text' }}">
    <div class="ch-tile__media" @if($videoUrl) data-dx-preview="{{ $videoUrl }}" @endif>
        @if($thumb)
            <img src="{{ $thumb }}" alt="" loading="lazy" decoding="async">
        @endif
        <span class="ch-tile__scrim"></span>
    </div>

    <div class="ch-tile__top">
        @if($amount > 0)
            <span class="ch-chip ch-chip--money">${{ number_format($amount, 0) }}</span>
        @endif
        @if($isOpen)
            <span class="ch-chip">{{ $goal > 0 ? $pct . '%' : __('Open') }}</span>
        @endif
    </div>

    <div class="ch-tile__info">
        <span class="ch-tile__title">{{ $cr->title }}</span>
        <div>
            <span class="ch-tile__by">
                @if($creator)
                    <img src="{{ $creator->avatar }}" alt="" class="dx-avatar" loading="lazy">
                @endif
                <span class="ch-tile__name">{{ $creator->name ?? __('Unknown') }}</span>
                @if($views > 0)
                    <span class="ch-tile__views">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
                        {{ $views >= 1000 ? round($views / 1000, 1) . 'K' : $views }}
                    </span>
                @endif
            </span>
            @if($isOpen && $goal > 0)
                <div class="dx-progress" role="progressbar"
                     aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="dx-progress__fill" style="width: {{ $pct }}%"></div>
                </div>
            @endif
        </div>
    </div>
</a>
