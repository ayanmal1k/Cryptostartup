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
    .bottom-nav {
        position: fixed;
        bottom: calc(10px + env(safe-area-inset-bottom));
        left: 10px;
        right: 10px;
        /* Match the video column. The feed is capped at 692px on desktop while this
           bar used to run the full viewport width, so on a wide screen it read as a
           browser chrome bar rather than as part of the app. */
        max-width: 692px;
        margin: 0 auto;

        display: flex;
        align-items: stretch;
        gap: 2px;
        padding: 6px;
        border-radius: 22px;
        z-index: 1001;
        pointer-events: auto;

        background: rgba(16, 16, 24, .72);
        backdrop-filter: blur(20px) saturate(160%);
        -webkit-backdrop-filter: blur(20px) saturate(160%);
        border: 1px solid rgba(255, 255, 255, .09);
        box-shadow:
            0 8px 32px rgba(0, 0, 0, .45),
            inset 0 1px 0 rgba(255, 255, 255, .07);
    }

    .bottom-nav-item {
        position: relative;
        flex: 1 1 0;
        min-width: 0;
        /* 48px clears the 44px minimum touch target with room for the label. */
        min-height: 48px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 3px;
        padding: 7px 4px;
        border-radius: 16px;
        text-decoration: none;
        color: rgba(233, 236, 245, .62);
        background: transparent;
        transition: color .18s ease, background-color .18s ease, transform .12s ease;
        -webkit-tap-highlight-color: transparent;
    }

    .bottom-nav-item:hover,
    .bottom-nav-item:focus-visible {
        color: #fff;
        background: rgba(255, 255, 255, .07);
        text-decoration: none;
    }

    .bottom-nav-item:focus-visible {
        outline: 2px solid rgba(255, 77, 157, .75);
        outline-offset: -2px;
    }

    /* Press feedback. A nav that does not react to touch feels unresponsive on a
       phone, where there is no hover to fall back on. */
    .bottom-nav-item:active { transform: scale(.94); }

    .bottom-nav-item.is-active {
        color: #fff;
        background: rgba(255, 77, 157, .16);
    }

    /* A short bar under the active item, rather than the old full-height gradient
       block: it marks position without competing with the video behind it. */
    .bottom-nav-item.is-active::after {
        content: '';
        position: absolute;
        bottom: 5px;
        left: 50%;
        transform: translateX(-50%);
        width: 16px;
        height: 2.5px;
        border-radius: 99px;
        background: linear-gradient(90deg, #ff4d9d, #ff8a80);
    }

    .bottom-nav-icon {
        display: block;
        font-size: 17px;
        line-height: 1;
    }

    .bottom-nav-item.is-active .bottom-nav-icon {
        color: #ff6bae;
        filter: drop-shadow(0 0 10px rgba(255, 77, 157, .45));
    }

    .bottom-nav-text {
        font-size: 10px;
        font-weight: 600;
        letter-spacing: .01em;
        line-height: 1;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* Sign-up is the one action worth pulling the eye, so it keeps a tint even when
       it is not the current page. */
    .bottom-nav-item--cta { color: #ffd7e8; }
    .bottom-nav-item--cta .bottom-nav-icon { color: #ff6bae; }

    /* --- responsive -------------------------------------------------------- */

    @media (max-width: 480px) {
        .bottom-nav {
            left: 8px;
            right: 8px;
            bottom: calc(8px + env(safe-area-inset-bottom));
            padding: 5px;
            border-radius: 20px;
        }
        .bottom-nav-item { padding: 6px 2px; border-radius: 14px; }
        .bottom-nav-icon { font-size: 16px; }
        .bottom-nav-text { font-size: 9px; }
    }

    /* Very narrow phones: the labels are what break first, so drop them and keep
       the icons at a comfortable size rather than shrinking everything. */
    @media (max-width: 340px) {
        .bottom-nav-text { display: none; }
        .bottom-nav-item { min-height: 44px; }
        .bottom-nav-icon { font-size: 18px; }
        .bottom-nav-item.is-active::after { bottom: 3px; }
    }

    @media (min-width: 769px) {
        .bottom-nav { bottom: 14px; padding: 7px; }
        .bottom-nav-item { min-height: 50px; }
        .bottom-nav-icon { font-size: 18px; }
        .bottom-nav-text { font-size: 11px; }
    }

    @media (prefers-reduced-motion: reduce) {
        .bottom-nav-item { transition: none; }
        .bottom-nav-item:active { transform: none; }
    }

    /* Backdrop-filter is not universal; without it the translucent background
       leaves the labels sitting on raw video. */
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .bottom-nav { background: rgba(12, 12, 18, .93); }
    }
</style>
