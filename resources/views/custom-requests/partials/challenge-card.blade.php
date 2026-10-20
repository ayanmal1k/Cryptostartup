@php
    /**
     * $cr    - CustomRequest
     * $wide  - landscape tile (funding cards) instead of portrait (delivered clips)
     */
    $wide = $wide ?? false;
    $hasVideo = !empty($cr->delivery_video_path);
    $goal = (float) ($cr->goal_amount ?? 0);
    $raised = (float) ($cr->current_amount ?? 0);
    $pct = $goal > 0 ? (int) min(100, round($raised / $goal * 100)) : 0;
    $isOpen = in_array($cr->status, ['pending', 'accepted'], true);
    $creator = $cr->creator;
@endphp
<a href="{{ route('custom-requests.show', $cr->id) }}" class="ch-card {{ $wide ? 'ch-card--wide' : '' }}">
    <div class="ch-card__media">
        @if($hasVideo)
            {{-- muted + playsinline so the hover preview can autoplay without a gesture --}}
            <video
                src="{{ asset('storage/' . $cr->delivery_video_path) }}"
                @if($cr->delivery_thumbnail_path) poster="{{ asset('storage/' . $cr->delivery_thumbnail_path) }}" @endif
                muted loop playsinline preload="metadata"></video>
            <span class="ch-card__play" aria-hidden="true">
                <span><svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></span>
            </span>
            <span class="ch-badge ch-badge--paid">{{ __('Delivered') }}</span>
        @else
            @if($cr->delivery_thumbnail_path)
                <img src="{{ asset('storage/' . $cr->delivery_thumbnail_path) }}" alt="" loading="lazy">
            @else
                <span class="ch-card__placeholder" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="m10 8 6 4-6 4V8z"/><rect x="2" y="3" width="20" height="18" rx="2"/></svg>
                </span>
            @endif
            @if($isOpen)
                <span class="ch-badge ch-badge--open">{{ __('Open') }}</span>
            @else
                <span class="ch-badge">{{ ucfirst($cr->status) }}</span>
            @endif
        @endif

        @if($goal > 0)
            <span class="ch-amount">${{ number_format($goal, 0) }}</span>
        @elseif((float) $cr->price > 0)
            <span class="ch-amount">${{ number_format((float) $cr->price, 0) }}</span>
        @endif
    </div>

    <div class="ch-card__body">
        @if($creator)
            <img src="{{ $creator->avatar }}" alt="" class="ch-card__avatar" loading="lazy">
        @endif
        <div class="ch-card__meta">
            <span class="ch-card__title">{{ $cr->title }}</span>
            <span class="ch-card__sub">
                {{ $creator->name ?? __('Unknown') }}
                @if($hasVideo && $cr->delivered_at)
                    · {{ $cr->delivered_at->diffForHumans() }}
                @elseif($cr->created_at)
                    · {{ $cr->created_at->diffForHumans() }}
                @endif
            </span>

            @if($isOpen && $goal > 0)
                <div class="ch-fund">
                    <div class="ch-fund__track" role="progressbar"
                         aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="ch-fund__fill" style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="ch-fund__label">
                        <span><strong>${{ number_format($raised, 0) }}</strong> {{ __('of') }} ${{ number_format($goal, 0) }}</span>
                        <span>{{ $pct }}%</span>
                    </span>
                </div>
            @endif
        </div>
    </div>
</a>
