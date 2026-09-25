@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $coin = $cryptocurrency;
    $price = (float) $coin->current_price;
    $change = (float) ($coin->price_change_percentage ?? $coin->change_24h ?? 0);
    $dir = abs($change) < 0.005 ? 'flat' : ($change > 0 ? 'up' : 'down');
    $hasWallet = $wallet && $wallet->balance > 0;
    $recentTxns = $coin->relationLoaded('transactions')
        ? $coin->transactions
        : $coin->transactions()->latest()->take(10)->get();

    // Dollars only: what the viewer holds, and how much that moved today.
    $holdingUsd = $hasWallet ? \App\Support\TokenMoney::toUsd((float) $wallet->balance, $price) : 0;
    $holdingMove = \App\Support\TokenMoney::changeUsd($holdingUsd, $change);

    // Supply: how much of the coin has been claimed, as a share. Units never shown.
    $total = (float) ($coin->total_supply ?? 0);
    $available = (float) ($coin->available_supply ?? 0);
    $claimedPct = $total > 0 ? max(0, min(100, ($total - $available) / $total * 100)) : 0;
    $myShare = $hasWallet && $total > 0 ? (float) $wallet->balance / $total * 100 : 0;

    // Chart from REAL recorded history only. No axis prices: nobody sees a per-coin
    // price, so the shape and the % move carry it.
    $chartPrices = \App\Support\Sparkline::prices($coin, 48);
    $chartLine = \App\Support\Sparkline::line($chartPrices, 300, 150, 16);
    $chartArea = \App\Support\Sparkline::area($chartPrices, 300, 150, 16);
    $chartMove = \App\Support\Sparkline::change($chartPrices);
    $chartDir = $chartMove === null ? 'flat' : ($chartMove >= 0 ? 'up' : 'down');

    $creator = $coin->creator;
    $txnLabels = ['buy' => __('Bought'), 'sell' => __('Sold'), 'transfer' => __('Sent')];
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

    @foreach(['success', 'error'] as $flash)
        @if(session($flash))
            <div class="lh-flash lh-flash--{{ $flash }}" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach

    {{-- ── Item card ─────────────────────────────────────────────────────────────── --}}
    <section class="lh-panel lh-brk lh-sheen ch-hero lh-in">
        <span class="lh-sheen__bar" aria-hidden="true"></span>

        <div class="ch-id">
            <span class="lh-hex lh-hex--lg">
                @if($coin->logo)
                    <img src="{{ asset('storage/' . $coin->logo) }}" alt="">
                @else
                    <span class="lh-hex__glyph">{{ strtoupper(substr($coin->name, 0, 1)) }}</span>
                @endif
            </span>
            <div class="ch-id__meta">
                <h1 class="ch-id__name">
                    <span>{{ $coin->name }}</span>
                    @if($coin->is_verified)
                        <svg class="ch-id__verified" viewBox="0 0 24 24" fill="none" role="img" aria-label="{{ __('Verified') }}"><path d="M12 2l2.4 2.1 3.2-.3.8 3.1 2.8 1.6-1.2 3 1.2 3-2.8 1.6-.8 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.8-3.1-2.8-1.6 1.2-3-1.2-3 2.8-1.6.8-3.1 3.2.3z" fill="currentColor" opacity=".18"/><path d="m8.5 12.2 2.3 2.3 4.7-4.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </h1>
                @if($creator)
                    <span class="ch-id__by">{{ __('by') }} <a href="{{ route('profile', ['username' => $creator->username]) }}">{{ '@' . $creator->username }}</a></span>
                @endif
                <div class="ch-id__tags">
                    @if($dir === 'flat')
                        <span class="lh-chip">{{ __('Steady today') }}</span>
                    @else
                        <span class="lh-chip lh-chip--{{ $dir }}">{{ $dir === 'up' ? '▲' : '▼' }} {{ number_format(abs($change), 2) }}% {{ __('today') }}</span>
                    @endif
                    <span class="lh-chip">{{ trans_choice('{0} No owners yet|{1} :n owner|[2,*] :n owners', $marketData['holders'], ['n' => number_format($marketData['holders'])]) }}</span>
                </div>
            </div>
        </div>

        <div class="ch-stake">
            <div>
                <span class="lh-cap">{{ $hasWallet ? __('Your stake') : __('Get in from') }}</span>
                <div class="ch-stake__value" @if($hasWallet) data-count-to="{{ number_format($holdingUsd, 2, '.', '') }}" @endif>
                    {{ $hasWallet ? \App\Support\TokenMoney::format($holdingUsd) : '$1.00' }}
                </div>
            </div>
            @if($hasWallet)
                <span class="ch-stake__move lh-{{ $dir }}">
                    @if($dir === 'flat')
                        {{ __('No change today') }}
                    @else
                        {{ $holdingMove >= 0 ? '+' : '−' }}{{ \App\Support\TokenMoney::format(abs($holdingMove)) }} {{ __('today') }}
                    @endif
                </span>
            @endif
        </div>

        {{-- Chart --}}
        <div class="ch-chart">
            @if($chartLine !== null)
                <svg class="is-draw" viewBox="0 0 300 150" preserveAspectRatio="none" aria-hidden="true">
                    <defs>
                        <linearGradient id="chFill" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0" stop-color="{{ $chartDir === 'down' ? '#ff4d6a' : '#22e0a0' }}" stop-opacity=".32"/>
                            <stop offset="1" stop-color="{{ $chartDir === 'down' ? '#ff4d6a' : '#22e0a0' }}" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path d="{{ $chartArea }}" fill="url(#chFill)"/>
                    <path class="ch-chart__line" d="{{ $chartLine }}" stroke="{{ $chartDir === 'down' ? 'var(--lh-red)' : 'var(--lh-green)' }}"/>
                </svg>
                <div class="ch-chart__label">
                    <span class="lh-chip lh-chip--{{ $chartDir === 'down' ? 'down' : 'up' }}">
                        {{ $chartMove >= 0 ? '+' : '' }}{{ number_format($chartMove, 2) }}%
                    </span>
                    <span class="lh-cap">{{ __('Recent trend') }}</span>
                </div>
                <span class="lh-sr">{{ __('Price trend: :p% over its recent history', ['p' => number_format($chartMove, 2)]) }}</span>
            @else
                <svg viewBox="0 0 300 150" preserveAspectRatio="none" aria-hidden="true">
                    <path d="M0 110 L300 110" stroke="var(--lh-line-2)" stroke-width="2" stroke-dasharray="6 6" vector-effect="non-scaling-stroke"/>
                </svg>
                <div class="ch-chart__empty">
                    <b>{{ __('Chart unlocks after the first trades') }}</b>
                    <span>{{ __('The line starts once people trade this coin') }}</span>
                </div>
            @endif
        </div>

        <div class="ch-actions {{ $hasWallet ? '' : 'ch-actions--one' }}">
            <a href="{{ route('cryptocurrency.buy.form', $coin->id) }}" class="lh-btn lh-btn--gold">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ $hasWallet ? __('Buy more') : __('Buy') }}
            </a>
            @if($hasWallet)
                <a href="{{ route('cryptocurrency.sell.form', $coin->id) }}" class="lh-btn lh-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ __('Sell') }}
                </a>
            @endif
        </div>
    </section>

    {{-- ── Readouts ──────────────────────────────────────────────────────────────── --}}
    <section class="lh-in" style="--i: 1">
        <div class="lh-head">
            <h2 class="lh-head__title">{{ __('Readout') }}</h2>
            <span class="lh-head__rule" aria-hidden="true"></span>
            <span class="lh-chip lh-chip--up" style="padding: 3px 8px; font-size: 10px"><span class="lh-live" aria-hidden="true"></span>{{ __('Live') }}</span>
        </div>
        <div class="lh-reads lh-reads--4">
            <div class="lh-read">
                <span class="lh-cap">{{ __('Traded today') }}</span>
                <span class="lh-read__value">{{ \App\Support\TokenMoney::format((float) ($coin->volume_24h ?? 0)) }}</span>
            </div>
            <div class="lh-read">
                <span class="lh-cap">{{ __('Trades today') }}</span>
                <span class="lh-read__value">{{ number_format($marketData['today_transactions'] ?? 0) }}</span>
            </div>
            <div class="lh-read">
                <span class="lh-cap">{{ __('Total value') }}</span>
                <span class="lh-read__value">{{ (float) $coin->market_cap > 0 ? \App\Support\TokenMoney::format((float) $coin->market_cap) : '—' }}</span>
            </div>
            <div class="lh-read">
                <span class="lh-cap">{{ __('Left to buy') }}</span>
                <span class="lh-read__value lh-gold">{{ \App\Support\TokenMoney::format(\App\Support\TokenMoney::toUsd($available, $price)) }}</span>
            </div>
        </div>
    </section>

    {{-- ── Stats: supply claimed, your share ─────────────────────────────────────── --}}
    <div class="ch-grid ch-grid--2 lh-in" style="--i: 2">
        <section class="lh-panel lh-brk">
            <div class="lh-head" style="margin-top: 0">
                <h2 class="lh-head__title">{{ __('Stats') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
            </div>
            <div class="ch-spec">
                <div class="ch-spec__row">
                    <div class="ch-spec__top">
                        <span class="lh-cap">{{ __('Supply claimed') }}</span>
                        <span class="ch-spec__val">{{ number_format($claimedPct, $claimedPct > 0 && $claimedPct < 1 ? 2 : 0) }}%</span>
                    </div>
                    <div class="lh-meter lh-meter--ticks lh-meter--gold" aria-hidden="true">
                        <span class="lh-meter__fill" style="width: {{ $claimedPct }}%"></span>
                    </div>
                </div>
                @if($hasWallet)
                    <div class="ch-spec__row">
                        <div class="ch-spec__top">
                            <span class="lh-cap">{{ __('Your share of the coin') }}</span>
                            <span class="ch-spec__val">{{ number_format($myShare, $myShare < 1 ? 2 : 1) }}%</span>
                        </div>
                        <div class="lh-meter lh-meter--ticks" aria-hidden="true">
                            <span class="lh-meter__fill" style="width: {{ min(100, $myShare) }}%"></span>
                        </div>
                    </div>
                @endif
                <div class="ch-kv">
                    <div class="ch-kv__row"><span>{{ __('Owners') }}</span><b>{{ number_format($marketData['holders']) }}</b></div>
                    <div class="ch-kv__row"><span>{{ __('All-time trades') }}</span><b>{{ number_format($marketData['total_transactions'] ?? 0) }}</b></div>
                    <div class="ch-kv__row"><span>{{ __('Launched') }}</span><b>{{ $coin->created_at->format('M j, Y') }}</b></div>
                </div>
            </div>
        </section>

        <section class="lh-panel lh-brk">
            <div class="lh-head" style="margin-top: 0">
                <h2 class="lh-head__title">{{ __('Sell fees') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
            </div>
            @php
                $creatorFee = (float) ($coin->creator_fee_percentage ?? 0);
                $platformFee = (float) ($coin->platform_fee_percentage ?? 0);
            @endphp
            <div class="ch-kv">
                <div class="ch-kv__row"><span>{{ __('To the creator') }}</span><b>{{ number_format($creatorFee, 2) }}%</b></div>
                <div class="ch-kv__row"><span>{{ __('Platform') }}</span><b>{{ number_format($platformFee, 2) }}%</b></div>
                <div class="ch-kv__row"><span>{{ __('Sell $100, you get') }}</span><b class="lh-gold">{{ \App\Support\TokenMoney::format(max(0, 100 * (1 - ($creatorFee + $platformFee) / 100))) }}</b></div>
            </div>
            <p class="lh-note" style="margin-top: 10px">{{ __('Buying is free. Fees only come off when you sell.') }}</p>
        </section>
    </div>

    {{-- ── Creator + about ───────────────────────────────────────────────────────── --}}
    <section class="lh-panel lh-brk lh-in" style="--i: 3">
        <div class="lh-head" style="margin-top: 0">
            <h2 class="lh-head__title">{{ __('Intel') }}</h2>
            <span class="lh-head__rule" aria-hidden="true"></span>
        </div>
        @if($creator)
            <a href="{{ route('profile', ['username' => $creator->username]) }}" class="ch-creator" style="margin-bottom: 14px">
                <img src="{{ $creator->avatar }}" alt="">
                <span class="lh-row__main">
                    <span class="lh-row__title">{{ $creator->name }}</span>
                    <span class="lh-row__sub">{{ '@' . $creator->username }} · {{ __('Creator') }}</span>
                </span>
                <span class="lh-head__link">{{ __('Profile') }} →</span>
            </a>
        @endif
        @if($coin->description)
            <p class="ch-about">{{ $coin->description }}</p>
        @else
            <p class="ch-about">{{ __(':name is backed by its creator. Buy in to support them early.', ['name' => $coin->name]) }}</p>
        @endif
        @if($coin->website || $coin->whitepaper)
            <div class="ch-links">
                @if($coin->website)
                    <a href="{{ $coin->website }}" target="_blank" rel="noopener noreferrer" class="lh-btn lh-btn--ghost lh-btn--sm">{{ __('Website') }} ↗</a>
                @endif
                @if($coin->whitepaper)
                    <a href="{{ $coin->whitepaper }}" target="_blank" rel="noopener noreferrer" class="lh-btn lh-btn--ghost lh-btn--sm">{{ __('Learn more') }} ↗</a>
                @endif
            </div>
        @endif
    </section>

    {{-- ── Trade log ─────────────────────────────────────────────────────────────── --}}
    <section class="lh-in" style="--i: 4">
        <div class="lh-head">
            <h2 class="lh-head__title">{{ __('Trade log') }}</h2>
            <span class="lh-head__rule" aria-hidden="true"></span>
            <span class="lh-head__code">{{ $recentTxns->count() }}</span>
        </div>

        <div class="lh-list" id="transactions-table">
            @forelse($recentTxns as $transaction)
                @php
                    $type = $transaction->type ?? 'other';
                    $isBuy = $type === 'buy';
                @endphp
                <div class="lh-row">
                    <span class="lh-ico {{ $isBuy ? 'lh-ico--in' : 'lh-ico--out' }}" aria-hidden="true">
                        @if($isBuy)
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @elseif($type === 'sell')
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10l-3-3M17 17H7l3 3" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <span class="lh-row__main">
                        <span class="lh-row__title" style="font-size: 15px">{{ $txnLabels[$type] ?? ucfirst($type) }}</span>
                        <span class="lh-row__sub">{{ $transaction->created_at->diffForHumans() }}</span>
                    </span>
                    <span class="lh-row__end">
                        <span class="lh-row__value">{{ \App\Support\TokenMoney::format((float) ($transaction->total_price ?? 0)) }}</span>
                    </span>
                </div>
            @empty
                <div class="lh-empty">
                    <span class="lh-empty__icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg></span>
                    <h3>{{ __('No trades yet') }}</h3>
                    <p>{{ __('Be the first to back :name.', ['name' => $coin->name]) }}</p>
                    <a href="{{ route('cryptocurrency.buy.form', $coin->id) }}" class="lh-btn lh-btn--gold">{{ __('Buy now') }}</a>
                </div>
            @endforelse
        </div>
    </section>

</div>
</div>
<script src="{{ asset('js/pages/wallet-hud.js') }}?v=20260924a" defer></script>
@endsection
