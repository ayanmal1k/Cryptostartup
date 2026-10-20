@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/marketplace.css') }}?v=20260817a">
@endsection

@section('content')
<div class="mp-page {{ $isDarkTheme ? 'mp-page--dark' : 'mp-page--light' }}">
<div class="mp-container">

    <header class="mp-header">
        <h1 class="mp-header__title">{{ __('Token Marketplace') }}</h1>
        <p class="mp-header__sub">{{ __('Back the creators you believe in, early') }}</p>
    </header>

    {{-- Live platform figures. Every one is computed from real ledger data. --}}
    <section class="mp-stats">
        <div class="mp-stat">
            <span class="mp-stat__value">{{ number_format($stats['tokens']) }}</span>
            <span class="mp-stat__label">{{ __('Tokens') }}</span>
        </div>
        <div class="mp-stat">
            <span class="mp-stat__value">${{ number_format($stats['volume24h'], 2) }}</span>
            <span class="mp-stat__label">{{ __('24h Volume') }}</span>
        </div>
        <div class="mp-stat">
            <span class="mp-stat__value">{{ number_format($stats['holders']) }}</span>
            <span class="mp-stat__label">{{ __('Holders') }}</span>
        </div>
        <div class="mp-stat">
            <span class="mp-stat__value">
                {{ $stats['marketCap'] > 0 ? '$' . number_format($stats['marketCap'], 0) : '—' }}
            </span>
            <span class="mp-stat__label">{{ __('Market Cap') }}</span>
        </div>
    </section>

    <section class="mp-toolbar">
        <form action="{{ route('cryptocurrency.marketplace') }}" method="GET" class="mp-search" role="search">
            @if($sort !== '')
                <input type="hidden" name="sort" value="{{ $sort }}">
            @endif
            <span class="mp-search__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                    <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
            </span>
            <input
                id="marketplace-token-search"
                class="mp-search__input"
                type="text"
                name="search"
                placeholder="{{ __('Search tokens by name or symbol...') }}"
                value="{{ $search }}"
                autocomplete="off"
                aria-label="{{ __('Search tokens') }}"
            >
            <button type="submit" class="mp-search__btn">{{ __('Search') }}</button>
        </form>

        {{-- Values here MUST match the whitelist in CryptocurrencyController@marketplace.
             They previously used sort/order pairs the controller never read, so every one of
             these chips changed the URL and nothing else. --}}
        <div class="mp-filters" role="list">
            @foreach([
                '' => __('All'),
                'price_high' => __('Price: High'),
                'price_low' => __('Price: Low'),
                'market_cap' => __('Market Cap'),
                'newest' => __('Newest'),
            ] as $key => $label)
                <a href="{{ route('cryptocurrency.marketplace', array_filter(['search' => $search, 'sort' => $key])) }}"
                   class="mp-filter {{ $sort === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>

    {{-- Top movers: only shown when tokens actually have enough history to rank honestly. --}}
    @if($gainers->count() > 0 && $search === '')
    <section class="mp-section">
        <div class="mp-section__head">
            <h2 class="mp-section__title">{{ __('Top Gainers') }}</h2>
            <span class="mp-section__count">{{ __('last 24h') }}</span>
        </div>
        <div class="mp-grid">
            @foreach($gainers as $crypto)
                @include('cryptocurrency.partials.marketplace-token-card', ['crypto' => $crypto])
            @endforeach
        </div>
    </section>
    @endif

    @if($trending->count() > 0 && $search === '')
    <section class="mp-section">
        <div class="mp-section__head">
            <h2 class="mp-section__title">{{ __('Trending Tokens') }}</h2>
            <span class="mp-section__count">{{ __('by 24h volume') }}</span>
        </div>
        <div class="mp-grid">
            @foreach($trending as $crypto)
                @include('cryptocurrency.partials.marketplace-token-card', ['crypto' => $crypto])
            @endforeach
        </div>
    </section>
    @endif

    <section class="mp-section">
        <div class="mp-section__head">
            <h2 class="mp-section__title">{{ __('All Tokens') }}</h2>
            <span class="mp-section__count">{{ $cryptocurrencies->total() }} {{ Str::plural('token', $cryptocurrencies->total()) }}</span>
        </div>

        @if($cryptocurrencies->count() > 0)
            <div class="mp-grid">
                @foreach($cryptocurrencies as $crypto)
                    @include('cryptocurrency.partials.marketplace-token-card', ['crypto' => $crypto])
                @endforeach
            </div>

            @if($cryptocurrencies->hasPages())
                <div class="mp-pagination">
                    {{ $cryptocurrencies->links() }}
                </div>
            @endif
        @else
            <div class="mp-empty">
                <span class="mp-empty__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/>
                        <path d="M12 8v4m0 4h.01" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    </svg>
                </span>
                <h3>{{ __('No tokens found') }}</h3>
                <p>{{ $search !== '' ? __('Try a different search term') : __('Be the first to create a token!') }}</p>
                @if($search !== '')
                    <a href="{{ route('cryptocurrency.marketplace') }}" class="mp-btn mp-btn--ghost">{{ __('Clear search') }}</a>
                @endif
            </div>
        @endif
    </section>

    <section class="mp-cta">
        <div class="mp-cta__text">
            <h2>{{ __('Create Your Own Token') }}</h2>
            <p>{{ __('Launch your cryptocurrency and let your fans invest in your success') }}</p>
        </div>
        <a href="{{ route('cryptocurrency.create') }}" class="mp-btn mp-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
            {{ __('Get Started') }}
        </a>
    </section>

</div>
</div>
@endsection
