<article class="stream-card">
    <a href="{{ route('streams.watch', $stream) }}" class="stream-card__link">
        <div class="stream-card__preview">
            @if($stream->thumbnail)
                <img src="{{ asset('storage/' . $stream->thumbnail) }}" class="stream-card__thumb" alt="{{ $stream->title }}">
            @else
                <div class="stream-card__thumb stream-card__thumb--placeholder" aria-hidden="true">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/>
                        <rect x="2" y="6" width="14" height="12" rx="2"/>
                    </svg>
                </div>
            @endif
            <div class="stream-card__badges">
                @if($stream->is_live)
                    <span class="stream-card__badge stream-card__badge--live">
                        <span class="stream-card__live-dot" aria-hidden="true"></span>
                        {{ __('LIVE') }}
                    </span>
                @endif
                @if($stream->requires_subscription)
                    <span class="stream-card__badge stream-card__badge--sub">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        {{ __('Subs') }}
                    </span>
                @endif
                @if($stream->price > 0)
                    <span class="stream-card__badge stream-card__badge--price">${{ number_format($stream->price, 2) }}</span>
                @endif
            </div>
            <div class="stream-card__play" aria-hidden="true">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
            </div>
        </div>
        <div class="stream-card__body">
            <div class="stream-card__creator">
                <img src="{{ $stream->user->avatar ?? asset('img/default-avatar.png') }}" alt="{{ $stream->user->name }}" class="stream-card__avatar" width="36" height="36">
                <div class="stream-card__creator-meta">
                    <span class="stream-card__creator-name">{{ $stream->user->name }}</span>
                    <span class="stream-card__creator-handle">{{ '@' . $stream->user->username }}</span>
                </div>
            </div>
            <h3 class="stream-card__title">{{ $stream->title }}</h3>
            <div class="stream-card__stats">
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    {{ $stream->viewer_count ?? 0 }}
                </span>
                <span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    {{ $stream->started_at ? $stream->started_at->diffForHumans() : __('Not started') }}
                </span>
            </div>
        </div>
    </a>
</article>
