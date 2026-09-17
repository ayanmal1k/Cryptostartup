{{--
    The reels bottom navigation.

    One copy, included by pages/home and pages/feed. They each carried their own
    markup and their own ~90 lines of near-identical CSS, and had already drifted:
    feed offered Video and Wallet to logged-out visitors who cannot use either, and
    labelled a link to /login as "Profile".

    Active state is derived from the current route rather than hardcoded, so a page
    cannot claim to be Home while sitting somewhere else.
--}}
@php
    $navRoute  = Route::currentRouteName();
    $navIsHome = request()->is('/') || request()->is('feed') || in_array($navRoute, ['home', 'feed'], true);
    $navIsMkt  = str_starts_with((string) $navRoute, 'cryptocurrency.marketplace');
    $navIsWlt  = str_starts_with((string) $navRoute, 'cryptocurrency.wallet');
    $navIsProf = $navRoute === 'profile' && Auth::check()
                 && request()->route('username') === Auth::user()->username;
@endphp

<nav class="bottom-nav" aria-label="{{ __('Primary') }}">
    <a href="{{ url('/') }}" class="bottom-nav-item {{ $navIsHome ? 'is-active' : '' }}"
       @if($navIsHome) aria-current="page" @endif>
        <span class="bottom-nav-icon"><i class="fas fa-house"></i></span>
        <span class="bottom-nav-text">{{ __('Home') }}</span>
    </a>

    @auth
        <a href="{{ route('videos.create') }}" class="bottom-nav-item {{ $navRoute === 'videos.create' ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-plus"></i></span>
            <span class="bottom-nav-text">{{ __('Create') }}</span>
        </a>
        <a href="{{ route('cryptocurrency.wallet') }}" class="bottom-nav-item {{ $navIsWlt ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-wallet"></i></span>
            <span class="bottom-nav-text">{{ __('Wallet') }}</span>
        </a>
        <a href="{{ route('cryptocurrency.marketplace') }}" class="bottom-nav-item {{ $navIsMkt ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-store"></i></span>
            <span class="bottom-nav-text">{{ __('Market') }}</span>
        </a>
        <a href="{{ route('profile', ['username' => Auth::user()->username ?? Auth::user()->id]) }}"
           class="bottom-nav-item {{ $navIsProf ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-user"></i></span>
            <span class="bottom-nav-text">{{ __('Profile') }}</span>
        </a>
    @else
        {{-- Guests get only what they can actually use, plus the two things we want
             them to do. Creating, wallets and profiles all require an account. --}}
        <a href="{{ route('cryptocurrency.marketplace') }}" class="bottom-nav-item {{ $navIsMkt ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-store"></i></span>
            <span class="bottom-nav-text">{{ __('Market') }}</span>
        </a>
        <a href="{{ route('login') }}" class="bottom-nav-item {{ $navRoute === 'login' ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-right-to-bracket"></i></span>
            <span class="bottom-nav-text">{{ __('Log in') }}</span>
        </a>
        <a href="{{ route('register') }}" class="bottom-nav-item bottom-nav-item--cta {{ $navRoute === 'register' ? 'is-active' : '' }}">
            <span class="bottom-nav-icon"><i class="fas fa-user-plus"></i></span>
            <span class="bottom-nav-text">{{ __('Sign up') }}</span>
        </a>
    @endauth
</nav>

<style>
    /* The floating glass bar — the design the client preferred, brought back from before
       the nav was flattened into a full-width strip. It hovers over the bottom of the
       screen with rounded corners and a soft glow, and the active tab is a lit pill.

       The seek bar and the feed height both read the space this bar takes from the
       viewport bottom (its height plus the gap under it), measured in
       elements/reels-scrubber, so floating it cannot put anything behind it. */
    .bottom-nav {
        position: fixed;
        left: 10px;
        right: 10px;
        bottom: calc(8px + env(safe-area-inset-bottom));
        margin: 0 auto;
        max-width: 560px;
        z-index: 1001;
        pointer-events: auto;

        display: flex;
        align-items: stretch;
        justify-content: space-around;
        gap: 4px;
        padding: 6px;
        border-radius: 20px;

        background:
            radial-gradient(circle at 15% 0%, rgba(236, 72, 153, .20) 0%, transparent 38%),
            radial-gradient(circle at 85% 100%, rgba(56, 189, 248, .14) 0%, transparent 42%),
            linear-gradient(180deg, rgba(14, 15, 26, .92) 0%, rgba(7, 8, 15, .94) 100%);
        backdrop-filter: blur(22px) saturate(150%);
        -webkit-backdrop-filter: blur(22px) saturate(150%);
        border: 1px solid rgba(255, 255, 255, .10);
        box-shadow: 0 14px 40px rgba(0, 0, 0, .55), inset 0 1px 0 rgba(255, 255, 255, .12);
    }

    .bottom-nav-item {
        position: relative;
        flex: 1 1 0;
        min-width: 0;
        max-width: 120px;
        /* 48px clears the 44px minimum touch target with room for the label. */
        min-height: 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        padding: 8px 4px 7px;
        border-radius: 14px;
        overflow: hidden;
        text-decoration: none;
        color: rgba(226, 232, 240, .70);
        transition: color .2s ease, background .2s ease, transform .12s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .bottom-nav-item:hover,
    .bottom-nav-item:focus-visible {
        color: #fff;
        text-decoration: none;
    }

    .bottom-nav-item:focus-visible {
        outline: 2px solid rgba(255, 77, 157, .75);
        outline-offset: -2px;
    }

    /* A nav that does not react to a thumb feels dead; there is no hover on a phone. */
    .bottom-nav-item:active { transform: scale(.94); }

    .bottom-nav-item.is-active {
        color: #fff;
        background: linear-gradient(135deg, rgba(244, 114, 182, .26), rgba(56, 189, 248, .14));
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, .26), 0 8px 22px rgba(236, 72, 153, .26);
    }

    .bottom-nav-item.is-active::after {
        content: '';
        position: absolute;
        left: 22%;
        right: 22%;
        bottom: 0;
        height: 2px;
        border-radius: 999px;
        background: linear-gradient(90deg, rgba(244, 114, 182, .95), rgba(56, 189, 248, .95));
    }

    .bottom-nav-icon {
        display: block;
        font-size: 16px;
        line-height: 1;
        filter: drop-shadow(0 0 8px rgba(255, 255, 255, .10));
    }

    .bottom-nav-item.is-active .bottom-nav-icon {
        color: #ff8cc6;
        filter: drop-shadow(0 0 10px rgba(255, 77, 157, .5));
    }

    .bottom-nav-text {
        font-size: 9px;
        font-weight: 700;
        letter-spacing: .05em;
        text-transform: uppercase;
        line-height: 1;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Sign-up is the one action worth pulling a guest's eye. */
    .bottom-nav-item--cta { color: #ffd7e8; }
    .bottom-nav-item--cta .bottom-nav-icon { color: #ff6bae; }

    /* --- responsive -------------------------------------------------------- */

    @media (max-width: 360px) {
        .bottom-nav { left: 6px; right: 6px; padding: 5px; gap: 2px; }
        .bottom-nav-item { padding: 7px 2px 6px; }
    }

    /* Very narrow phones: drop the labels before anything else gets cramped. */
    @media (max-width: 320px) {
        .bottom-nav-text { display: none; }
        .bottom-nav-icon { font-size: 18px; }
    }

    /* Short screens (landscape phones, small browsers): a slimmer bar leaves more
       of the video on screen. */
    @media (max-height: 560px) {
        .bottom-nav { bottom: calc(4px + env(safe-area-inset-bottom)); padding: 4px; }
        .bottom-nav-item { min-height: 42px; padding: 5px 4px; gap: 3px; }
    }

    @media (min-width: 769px) {
        .bottom-nav { max-width: 520px; bottom: 12px; }
        .bottom-nav-icon { font-size: 18px; }
        .bottom-nav-text { font-size: 10px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .bottom-nav-item { transition: none; }
        .bottom-nav-item:active { transform: none; }
    }

    /* Without backdrop-filter the translucent bar would leave labels on raw video. */
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .bottom-nav { background: rgba(10, 11, 20, .97); }
    }
</style>
