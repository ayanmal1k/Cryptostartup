{{--
    The LIVE entry point in the reels header.

    Sits next to the menu button, the way TikTok puts LIVE at the top-left of the
    feed. Goes to the Live page (streams.index).

    The count is real and uses the same rule as that page (StreamController@index):
    is_live AND is_public, so the badge never claims streams the page won't show.
--}}
@php
    $liveCount = 0;
    try {
        $liveCount = \App\Models\Stream::where('is_live', true)
            ->where('is_public', true)
            ->count();
    } catch (\Throwable $e) {
        // streams table missing or unmigrated -- render the button without a count
        $liveCount = 0;
    }
@endphp

<a href="{{ route('streams.index') }}"
   class="reels-live {{ $liveCount > 0 ? 'is-on-air' : '' }}"
   aria-label="{{ $liveCount > 0
        ? trans_choice(':count creator is live|:count creators are live', $liveCount, ['count' => $liveCount])
        : __('Browse live streams') }}">
    <span class="reels-live__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9"
             stroke-linecap="round" stroke-linejoin="round">
            <rect x="2.5" y="7.5" width="19" height="13" rx="3"/>
            <path d="M8 7.5 5 3.5"/>
            <path d="M16 7.5 19 3.5"/>
        </svg>
        <span class="reels-live__dot"></span>
    </span>
    <span class="reels-live__label">{{ __('LIVE') }}</span>
    @if($liveCount > 0)
        <span class="reels-live__count">{{ $liveCount > 99 ? '99+' : $liveCount }}</span>
    @endif
</a>

<style>
    /* The header is justify-content: space-between, so the menu button and LIVE are
       grouped to keep them together on the left rather than being spread apart. */
    .top-header__left {
        display: flex;
        align-items: center;
        gap: 2px;
    }

    .reels-live {
        position: relative;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 1px;
        /* Matches the 46px header controls either side of it. */
        min-width: 46px;
        height: 46px;
        padding: 0 6px;
        border-radius: 12px;
        color: #fff;
        text-decoration: none;
        -webkit-tap-highlight-color: transparent;
        transition: transform .14s ease, background-color .18s ease;
    }

    .reels-live:hover,
    .reels-live:focus-visible {
        color: #fff;
        text-decoration: none;
        background: rgba(255, 255, 255, .10);
    }

    .reels-live:active { transform: scale(.93); }

    .reels-live:focus-visible {
        outline: 2px solid rgba(255, 77, 157, .8);
        outline-offset: -2px;
    }

    .reels-live__icon {
        position: relative;
        display: block;
        line-height: 0;
    }

    .reels-live__icon svg {
        width: 21px;
        height: 21px;
        /* The header sits over video, so the glyph needs its own separation from
           whatever frame happens to be behind it. */
        filter: drop-shadow(0 1px 3px rgba(0, 0, 0, .6));
    }

    .reels-live__label {
        font-size: 9px;
        font-weight: 800;
        letter-spacing: .06em;
        line-height: 1;
        text-shadow: 0 1px 3px rgba(0, 0, 0, .6);
    }

    /* Off air: no dot, no count, muted -- the button is still a way in, it just is
       not claiming anything is happening. */
    .reels-live__dot { display: none; }

    .reels-live:not(.is-on-air) { color: rgba(255, 255, 255, .82); }

    /* On air ------------------------------------------------------------- */

    .reels-live.is-on-air { color: #fff; }

    .reels-live.is-on-air .reels-live__label {
        color: #ff3b5c;
    }

    .reels-live.is-on-air .reels-live__dot {
        display: block;
        position: absolute;
        top: -1px;
        right: -3px;
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: #ff3b5c;
        box-shadow: 0 0 0 2px rgba(0, 0, 0, .45);
        animation: reels-live-pulse 1.8s ease-in-out infinite;
    }

    @keyframes reels-live-pulse {
        0%, 100% { transform: scale(1);   opacity: 1; }
        50%      { transform: scale(1.35); opacity: .55; }
    }

    .reels-live__count {
        position: absolute;
        top: -2px;
        left: calc(50% + 8px);
        min-width: 16px;
        height: 16px;
        padding: 0 4px;
        border-radius: 99px;
        background: #ff3b5c;
        color: #fff;
        font-size: 10px;
        font-weight: 800;
        line-height: 16px;
        text-align: center;
        box-shadow: 0 1px 4px rgba(0, 0, 0, .45);
    }

    @media (max-width: 480px) {
        .reels-live { min-width: 42px; height: 42px; }
        .reels-live__icon svg { width: 19px; height: 19px; }
        .reels-live__label { font-size: 8.5px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .reels-live.is-on-air .reels-live__dot { animation: none; }
        .reels-live { transition: none; }
    }
</style>
