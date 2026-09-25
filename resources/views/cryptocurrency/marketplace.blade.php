@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $showRails = $search === '' && $cryptocurrencies->onFirstPage();
    $sorts = [
        '' => __('Top'),
        'newest' => __('New'),
        'market_cap' => __('Biggest'),
        'price_high' => __('Price ↓'),
        'price_low' => __('Price ↑'),
    ];
@endphp

@section('page_title', __('Coins'))

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/fx.css') }}?v=20260925a">
@endsection

@section('content')
<div class="fx {{ $isDarkTheme ? '' : 'fx--light' }}">
<div class="fx-wrap fx-wrap--wide">

    <header class="fx-in" style="display: flex; align-items: flex-end; justify-content: space-between; gap: 12px">
        <div>
            <span class="fx-live">{{ __('Market live') }}</span>
            <h1 style="margin-top: 6px; font-family: var(--fx-num); font-size: 32px; font-weight: 600; letter-spacing: -0.03em">{{ __('Coins') }}</h1>
            <p class="fx-label" style="margin-top: 2px">{{ __('Back the creators you believe in, early') }}</p>
        </div>
        @if($myBag > 0)
            <a href="{{ route('wallet.hub') }}#assets" class="fx-panel" style="padding: 10px 14px; text-align: right; border-radius: 18px">
                <span class="fx-label" style="display: block; font-size: 12px">{{ __('Your coins') }}</span>
                <span class="fx-num" style="font-size: 18px; font-weight: 600" data-fx-count="{{ number_format($myBag, 2, '.', '') }}">{{ \App\Support\TokenMoney::format($myBag) }}</span>
            </a>
        @endif
    </header>

    {{-- Real platform figures, from the ledger. --}}
    <section class="fx-bento fx-in" style="--i: 1; grid-template-columns: repeat(2, minmax(0, 1fr))" aria-label="{{ __('Market figures') }}">
        <div class="fx-tile"><small>{{ __('Traded today') }}</small><b>{{ \App\Support\TokenMoney::format($stats['volume24h']) }}</b></div>
        <div class="fx-tile"><small>{{ __('Total value') }}</small><b>{{ $stats['marketCap'] > 0 ? '$' . number_format($stats['marketCap'], 0) : '—' }}</b></div>
        <div class="fx-tile"><small>{{ __('Coins') }}</small><b>{{ number_format($stats['tokens']) }}</b></div>
        <div class="fx-tile"><small>{{ __('Owners') }}</small><b>{{ number_format($stats['holders']) }}</b></div>
    </section>

    <section class="fx-in" style="--i: 2; display: flex; flex-direction: column; gap: 12px">
        <form action="{{ route('cryptocurrency.marketplace') }}" method="GET" class="fx-search" role="search">
            @if($sort !== '')<input type="hidden" name="sort" value="{{ $sort }}">@endif
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
            <input type="search" name="search" value="{{ $search }}" placeholder="{{ __('Search coins') }}" aria-label="{{ __('Search coins') }}" autocomplete="off" enterkeyhint="search">
            <button type="submit" class="fx-btn fx-btn--primary fx-btn--sm">{{ __('Search') }}</button>
        </form>
        {{-- Values MUST match the whitelist in CryptocurrencyController@marketplace. --}}
        <nav class="fx-chips" aria-label="{{ __('Sort coins') }}">
            @foreach($sorts as $key => $label)
                <a href="{{ route('cryptocurrency.marketplace', array_filter(['search' => $search, 'sort' => $key])) }}"
                   class="fx-chip {{ $sort === $key ? 'is-on' : '' }}" style="display: inline-flex; align-items: center" @if($sort === $key) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </section>

    {{-- ── Trending ────────────────────────────────────────────────────────────── --}}
    @if($showRails && $trending->count())
        <section class="fx-in" style="--i: 3">
            <div class="fx-head">
                <h2 class="fx-head__title">{{ __('Trending') }}</h2>
                <span class="fx-label">{{ __('Most traded today') }}</span>
            </div>
            <div class="fx-trend">
                @foreach($trending as $i => $c)
                    @php
                        $prices = \App\Support\Sparkline::prices($c);
                        $line = \App\Support\Sparkline::line($prices, 220, 56, 4);
                        $area = \App\Support\Sparkline::area($prices, 220, 56, 4);
                        $rc = $c->rollingChange(24);
                        $down = $rc !== null && $rc < 0;
                        $owned = \App\Support\TokenMoney::toUsd((float) ($myUnits[$c->id] ?? 0), (float) $c->current_price);
                    @endphp
                    <a href="{{ route('cryptocurrency.show', $c->id) }}" class="fx-tcard">
                        <div class="fx-tcard__top">
                            <span class="fx-coin">@if($c->logo)<img src="{{ asset('storage/' . $c->logo) }}" alt="">@else{{ strtoupper(substr($c->name, 0, 1)) }}@endif</span>
                            <span class="fx-row__main">
                                <span class="fx-row__title">{{ $c->name }}</span>
                                <span class="fx-row__sub">#{{ $i + 1 }} · {{ $c->creator ? '@' . $c->creator->username : __('Creator coin') }}</span>
                            </span>
                        </div>
                        <div class="fx-tcard__spark" aria-hidden="true">
                            <svg viewBox="0 0 220 56" preserveAspectRatio="none">
                                @if($line)
                                    <defs><linearGradient id="tg{{ $c->id }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="{{ $down ? '#ff5f7e' : '#7c5cff' }}" stop-opacity=".35"/><stop offset="1" stop-color="{{ $down ? '#ff5f7e' : '#28d7ff' }}" stop-opacity="0"/></linearGradient></defs>
                                    <path d="{{ $area }}" fill="url(#tg{{ $c->id }})"/>
                                    <path d="{{ $line }}" fill="none" stroke="{{ $down ? 'var(--fx-down)' : 'var(--fx-up)' }}" stroke-width="2.25" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
                                @else
                                    <path d="M0 40 L220 40" stroke="var(--fx-stroke-2)" stroke-width="2" stroke-dasharray="4 6" vector-effect="non-scaling-stroke"/>
                                @endif
                            </svg>
                        </div>
                        <div class="fx-tcard__foot">
                            <div>
                                <span class="fx-label" style="font-size: 12px">{{ $owned > 0 ? __('You own') : __('Traded today') }}</span>
                                <div class="fx-num" style="font-size: 18px; font-weight: 600">{{ \App\Support\TokenMoney::format($owned > 0 ? $owned : (float) ($volume24h[$c->id] ?? 0)) }}</div>
                            </div>
                            @if($rc !== null)
                                <span class="fx-pill fx-pill--{{ $down ? 'down' : 'up' }}">{{ $down ? '▼' : '▲' }} {{ number_format(abs($rc), 1) }}%</span>
                            @else
                                <span class="fx-pill">{{ __('New') }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── All coins ───────────────────────────────────────────────────────────── --}}
    <section class="fx-panel fx-panel--pad fx-in" style="--i: 4">
        <div class="fx-head">
            <h2 class="fx-head__title">{{ $search !== '' ? __('Results for ":q"', ['q' => $search]) : __('All coins') }}</h2>
            <span class="fx-label">{{ number_format($cryptocurrencies->total()) }}</span>
        </div>
        @if($cryptocurrencies->count())
            <div class="fx-list">
                @foreach($cryptocurrencies as $i => $crypto)
                    @include('cryptocurrency.partials.marketplace-token-card', ['crypto' => $crypto, 'rank' => $cryptocurrencies->firstItem() + $i])
                @endforeach
            </div>
            @if($cryptocurrencies->hasPages())
                <div class="fx-pages" style="margin-top: 16px">{{ $cryptocurrencies->links() }}</div>
            @endif
        @else
            <div class="fx-empty">
                <span class="fx-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg></span>
                <h3>{{ __('No coins found') }}</h3>
                <p>{{ $search !== '' ? __('Try a different search.') : __('No coins have launched yet. Yours could be the first.') }}</p>
                @if($search !== '')<a href="{{ route('cryptocurrency.marketplace') }}" class="fx-btn fx-btn--ghost fx-btn--sm" style="margin-top: 8px">{{ __('Clear search') }}</a>@endif
            </div>
        @endif
    </section>

    <section class="fx-card fx-in" style="--i: 5; padding: 22px">
        <span class="fx-card__label">{{ __('For creators') }}</span>
        <h2 style="margin-top: 8px; font-family: var(--fx-num); font-size: 24px; font-weight: 600; letter-spacing: -0.02em">{{ __('Launch your own coin') }}</h2>
        <p style="margin-top: 6px; color: rgba(255,255,255,.82); font-size: 14.5px">{{ __('Let your fans back you directly with a coin of your own.') }}</p>
        <a href="{{ route('cryptocurrency.create') }}" class="fx-btn fx-btn--sm" style="margin-top: 16px; background: #fff; color: #0c0e16">{{ __('Get started') }}</a>
    </section>
</div>
</div>
<script src="{{ asset('js/pages/fx.js') }}?v=20260925a" defer></script>
@endsection
