@php
    /**
     * 16:9 stream card. $stream - App\Models\Stream with `user` loaded.
     */
    $u = $stream->user;
    $thumb = $stream->thumbnail ? asset('storage/' . $stream->thumbnail) : null;
    $views = (int) ($stream->viewer_count ?? 0);
@endphp
<a href="{{ route('streams.watch', $stream) }}" class="lv-card">
    <span class="lv-thumb">
        @if($thumb)
            <img class="lv-thumb__img" src="{{ $thumb }}" alt="" loading="lazy" decoding="async">
        @elseif($u)
            <img class="lv-thumb__face" src="{{ $u->avatar }}" alt="" loading="lazy">
        @endif
        <span class="lv-thumb__scrim" aria-hidden="true"></span>
        <span class="lv-thumb__tl">
            @if($stream->is_live)
                <span class="lv-badge"><span class="lv-dot"></span>{{ __('LIVE') }}</span>
            @else
                <span class="lv-badge lv-badge--dim">{{ __('Ended') }} · {{ optional($stream->ended_at)->diffForHumans(null, true) }}</span>
            @endif
            @if($stream->requires_subscription)
                <span class="lv-badge lv-badge--dim">{{ __('Subscribers') }}</span>
            @endif
        </span>
        <span class="lv-thumb__br">
            @if($views)
                <span class="lv-badge lv-badge--dim">{{ number_format($views) }} {{ trans_choice('{1} view|[0,*] views', $views) }}</span>
            @endif
        </span>
    </span>
    <span class="lv-card__row">
        @if($u)
            <img src="{{ $u->avatar }}" alt="" class="dx-avatar" loading="lazy">
        @endif
        <span class="lv-card__text">
            <span class="lv-card__title">{{ $stream->title }}</span>
            <span class="lv-card__by">
                {{ $u->name ?? __('Creator') }}
                @if($stream->is_live && $stream->started_at) · {{ __('started') }} {{ $stream->started_at->diffForHumans() }}@endif
            </span>
        </span>
    </span>
</a>
