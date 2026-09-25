@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $showRails = $search === '' && $cryptocurrencies->onFirstPage();
    $featured = $showRails ? $trending->first() : null;
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/loot-hud.css') }}?v=20260924a">
@endsection

@section('content')
<div class="lh {{ $isDarkTheme ? 'lh--dark' : 'lh--light' }}">
<div class="lh-wrap lh-wrap--wide">

    <header class="cm-top lh-in">
        <div>
            <span class="lh-chip lh-chip--up" style="margin-bottom: 10px"><span class="lh-live" aria-hidden="true"></span>{{ __('Market live') }}</span>
            <h1 class="cm-title">{{ __('Coin market') }}</h1>
            <p class="cm-sub">{{ __('Back the creators you believe in, early') }}</p>
        </div>
    </header>

    {{-- Live platform figures, every one computed from real ledger data. --}}
    <section class="cm-ticker lh-in" style="--i: 1" aria-label="{{ __('Market figures') }}">
        <div><span class="lh-cap">{{ __('Coins') }}</span><b>{{ number_format($stats['tokens']) }}</b></div>
        <div><span class="lh-cap">{{ __('Traded today') }}</span><b class="lh-gold">${{ number_format($stats['volume24h'], 2) }}</b></div>
        <div><span class="lh-cap">{{ __('Owners') }}</span><b>{{ number_format($stats['holders']) }}</b></div>
        <div><span class="lh-cap">{{ __('Total value') }}</span><b>{{ $stats['marketCap'] > 0 ? '$' . number_format($stats['marketCap'], 0) : '—' }}</b></div>
    </section>

    {{-- What the viewer already holds, one tap from the wallet. --}}
    @if($myBag > 0)
        <a href="{{ route('wallet.hub') }}#tokens" class="lh-panel lh-brk cm-bag lh-in" style="--i: 2">
            <span class="lh-hex lh-hex--sm lh-hex--gold"><span class="lh-hex__glyph">$</span></span>
            <span class="cm-bag__main">
                <span class="lh-cap">{{ __('Your bag') }}</span>
                <span class="cm-bag__value" data-count-to="{{ number_format($myBag, 2, '.', '') }}">{{ \App\Support\TokenMoney::format($myBag) }}</span>
            </span>
            <span class="lh-head__link">{{ __('Wallet') }} →</span>
        </a>
    @endif

    <section class="lh-in" style="--i: 3">
        <form action="{{ route('cryptocurrency.marketplace') }}" method="GET" class="cm-search" role="search">
            @if($sort !== '')
                <input type="hidden" name="sort" value="{{ $sort }}">
            @endif
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
            </svg>
            <input id="marketplace-token-search" type="search" name="search" placeholder="{{ __('Search coins') }}"
                   value="{{ $search }}" autocomplete="off" aria-label="{{ __('Search coins') }}">
            <button type="submit" class="lh-btn lh-btn--brand lh-btn--sm">{{ __('Search') }}</button>
        </form>

        {{-- Values here MUST match the whitelist in CryptocurrencyController@marketplace. --}}
        <nav class="cm-sorts" aria-label="{{ __('Sort coins') }}">
            @foreach([
                '' => __('Top'),
                'newest' => __('Newest'),
                'market_cap' => __('Total value'),
                'price_high' => __('Price: high'),
                'price_low' => __('Price: low'),
            ] as $key => $label)
                <a href="{{ route('cryptocurrency.marketplace', array_filter(['search' => $search, 'sort' => $key])) }}"
                   class="cm-sort {{ $sort === $key ? 'is-active' : '' }}" @if($sort === $key) aria-current="true" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    </section>

    {{-- ── #1 trending, featured ─────────────────────────────────────────────────── --}}
    @if($featured)
        @php
            $f = $featured;
            $fPrices = \App\Support\Sparkline::prices($f);
            $fLine = \App\Support\Sparkline::line($fPrices, 300, 64, 6);
            $fArea = \App\Support\Sparkline::area($fPrices, 300, 64, 6);
            $fChange = $f->rollingChange(24);
            $fOwned = \App\Support\TokenMoney::toUsd((float) ($myUnits[$f->id] ?? 0), (float) $f->current_price);
        @endphp
        <section class="lh-in" style="--i: 4">
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('Trending') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
                <span class="lh-head__code">{{ __('most traded today') }}</span>
            </div>
            <article class="lh-panel lh-brk cm-feature">
                <div class="cm-feature__top">
                    <span class="lh-hex lh-hex--lg">
                        @if($f->logo)
                            <img src="{{ asset('storage/' . $f->logo) }}" alt="">
                        @else
                            <span class="lh-hex__glyph">{{ strtoupper(substr($f->name, 0, 1)) }}</span>
                        @endif
                    </span>
                    <div class="cm-feature__meta">
                        <span class="cm-rank">#1 {{ __('Trending') }}</span>
                        <h3 class="cm-feature__name"><a href="{{ route('cryptocurrency.show', $f->id) }}" style="color: inherit">{{ $f->name }}</a></h3>
                        @if($f->creator)
                            <span class="lh-row__sub">{{ __('by') }} {{ '@' . $f->creator->username }}</span>
                        @endif
                    </div>
                    @if($fChange !== null)
                        <span class="lh-chip lh-chip--{{ $fChange >= 0 ? 'up' : 'down' }}">{{ $fChange >= 0 ? '▲' : '▼' }} {{ number_format(abs($fChange), 2) }}%</span>
                    @else
                        <span class="lh-chip lh-chip--new">{{ __('New') }}</span>
                    @endif
                </div>

                <div class="cm-feature__spark" aria-hidden="true">
                    <svg viewBox="0 0 300 64" preserveAspectRatio="none">
                        @if($fLine !== null)
                            <path d="{{ $fArea }}" fill="{{ ($fChange ?? 0) < 0 ? 'rgba(255,77,106,.14)' : 'rgba(34,224,160,.14)' }}"/>
                            <path d="{{ $fLine }}" fill="none" stroke="{{ ($fChange ?? 0) < 0 ? 'var(--lh-red)' : 'var(--lh-green)' }}" stroke-width="2" vector-effect="non-scaling-stroke" stroke-linejoin="round"/>
                        @else
                            <path d="M0 40 L300 40" stroke="var(--lh-line-2)" stroke-width="2" stroke-dasharray="6 6" vector-effect="non-scaling-stroke"/>
                        @endif
                    </svg>
                </div>

                <div class="cm-feature__stats">
                    <div><span class="lh-cap">{{ __('Owners') }}</span><b>{{ number_format((int) ($holders[$f->id] ?? 0)) }}</b></div>
                    <div><span class="lh-cap">{{ __('Today') }}</span><b>${{ number_format((float) ($volume24h[$f->id] ?? 0), 2) }}</b></div>
                    <div><span class="lh-cap">{{ $fOwned > 0 ? __('You own') : __('Value') }}</span>
                        <b class="lh-gold">{{ $fOwned > 0 ? \App\Support\TokenMoney::format($fOwned) : ((float) $f->market_cap > 0 ? '$' . number_format($f->market_cap, 0) : '—') }}</b></div>
                </div>

                <div class="cm-feature__actions">
                    <a href="{{ route('cryptocurrency.buy.form', $f->id) }}" class="lh-btn lh-btn--gold">{{ __('Buy') }}</a>
                    <a href="{{ route('cryptocurrency.show', $f->id) }}" class="lh-btn lh-btn--ghost">{{ __('Details') }}</a>
                </div>
            </article>
        </section>
    @endif

    {{-- ── Top gainers: only when coins have enough history to rank honestly ──────── --}}
    @if($showRails && $gainers->count())
        <section class="lh-in" style="--i: 5">
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('Top gainers') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
                <span class="lh-head__code">24h</span>
            </div>
            <div class="lh-list">
                @foreach($gainers as $i => $crypto)
                    @include('cryptocurrency.partials.marketplace-token-card', ['crypto' => $crypto, 'rank' => $i + 1])
                @endforeach
            </div>
        </section>
    @endif

    {{-- ── Leaderboard ───────────────────────────────────────────────────────────── --}}
    <section class="lh-in" style="--i: 6">
        <div class="lh-head">
            <h2 class="lh-head__title">{{ $search !== '' ? __('Results') : __('All coins') }}</h2>
            <span class="lh-head__rule" aria-hidden="true"></span>
            <span class="lh-head__code">{{ number_format($cryptocurrencies->total()) }}</span>
        </div>

        @if($cryptocurrencies->count() > 0)
            <div class="lh-list">
                @foreach($cryptocurrencies as $i => $crypto)
                    @include('cryptocurrency.partials.marketplace-token-card', [
                        'crypto' => $crypto,
                        'rank' => $cryptocurrencies->firstItem() + $i,
                    ])
                @endforeach
            </div>

            @if($cryptocurrencies->hasPages())
                <div class="cm-pages" style="margin-top: 16px">
                    {{ $cryptocurrencies->links() }}
                </div>
            @endif
        @else
            <div class="lh-empty">
                <span class="lh-empty__icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/><path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg></span>
                <h3>{{ __('No coins found') }}</h3>
                <p>{{ $search !== '' ? __('Try a different search') : __('No coins have launched yet. Yours could be the first.') }}</p>
                @if($search !== '')
                    <a href="{{ route('cryptocurrency.marketplace') }}" class="lh-btn lh-btn--ghost">{{ __('Clear search') }}</a>
                @endif
            </div>
        @endif
    </section>

    <section class="lh-panel cm-cta">
        <span class="lh-cap">{{ __('For creators') }}</span>
        <h2>{{ __('Launch your own coin') }}</h2>
        <p>{{ __('Let your fans back you directly with a coin of your own.') }}</p>
        <a href="{{ route('cryptocurrency.create') }}" class="lh-btn lh-btn--brand" style="align-self: flex-start">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
            {{ __('Get started') }}
        </a>
    </section>

</div>
</div>
<script src="{{ asset('js/pages/wallet-hud.js') }}?v=20260924a" defer></script>
@endsection
