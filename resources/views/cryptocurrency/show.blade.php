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
    $recentTxns = $coin->relationLoaded('transactions') ? $coin->transactions : $coin->transactions()->latest()->take(10)->get();

    // Dollars only: the viewer's position and how much it moved today.
    $position = $hasWallet ? \App\Support\TokenMoney::toUsd((float) $wallet->balance, $price) : 0;
    $positionMove = \App\Support\TokenMoney::changeUsd($position, $change);

    $total = (float) ($coin->total_supply ?? 0);
    $available = (float) ($coin->available_supply ?? 0);
    $claimedPct = $total > 0 ? max(0, min(100, ($total - $available) / $total * 100)) : 0;
    $myShare = $hasWallet && $total > 0 ? (float) $wallet->balance / $total * 100 : 0;
    $marketCap = (float) $coin->market_cap;

    $creator = $coin->creator;
    $creatorFee = (float) ($coin->creator_fee_percentage ?? 0);
    $platformFee = (float) ($coin->platform_fee_percentage ?? 0);
    $txnLabels = ['buy' => __('Bought'), 'sell' => __('Sold'), 'transfer' => __('Sent')];
    $chev = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>';
@endphp

@section('page_title', $coin->name)

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/fx.css') }}?v=20260925a">
@endsection

@section('content')
<div class="fx {{ $isDarkTheme ? '' : 'fx--light' }}" data-fx-live="{{ route('cryptocurrency.live', $coin->id) }}" data-fx-every="20000">
<div class="fx-wrap fx-wrap--wide">

    @foreach(['success', 'error'] as $flash)
        @if(session($flash))
            <div class="fx-flash fx-flash--{{ $flash }}" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach

    {{-- ── Identity ─────────────────────────────────────────────────────────────── --}}
    <header class="fx-top fx-in">
        <span class="fx-coin fx-coin--lg">
            @if($coin->logo)<img src="{{ asset('storage/' . $coin->logo) }}" alt="">@else{{ strtoupper(substr($coin->name, 0, 1)) }}@endif
        </span>
        <div class="fx-top__hello">
            <strong style="font-family: var(--fx-num); font-size: 22px; display: flex; align-items: center; gap: 6px">
                {{ $coin->name }}
                @if($coin->is_verified)
                    <svg width="18" height="18" viewBox="0 0 24 24" role="img" aria-label="{{ __('Verified') }}" style="color: var(--fx-cyan)"><path fill="currentColor" d="M12 2l2.4 2.1 3.2-.3.8 3.1 2.8 1.6-1.2 3 1.2 3-2.8 1.6-.8 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.8-3.1-2.8-1.6 1.2-3-1.2-3 2.8-1.6.8-3.1 3.2.3z"/><path d="m8.5 12.2 2.3 2.3 4.7-4.8" fill="none" stroke="#06070b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @endif
            </strong>
            @if($creator)
                <small>{{ __('by') }} <a href="{{ route('profile', ['username' => $creator->username]) }}" style="color: var(--fx-ink); font-weight: 600">{{ '@' . $creator->username }}</a></small>
            @endif
        </div>
        <button type="button" class="fx-icon-btn" aria-label="{{ __('Share') }}"
                onclick="navigator.share ? navigator.share({ title: @js($coin->name), url: location.href }).catch(function(){}) : navigator.clipboard && navigator.clipboard.writeText(location.href)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v7a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-7M16 6l-4-4-4 4M12 2v13"/></svg>
        </button>
    </header>

    <div class="fx-grid">
        <div class="fx-col">
            {{-- ── Headline figure + chart ──────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 1" data-fx-chart>
                <script type="application/json">{!! json_encode(['series' => $series, 'mode' => 'change', 'flatText' => __('Steady')]) !!}</script>
                <div class="fx-chart__head">
                    <div>
                        <span class="fx-label">{{ $hasWallet ? __('Your position') : ($marketCap > 0 ? __('Market value') : __('Traded today')) }}</span>
                        @if($hasWallet)
                            <div class="fx-chart__value" style="font-size: 40px" data-fx-bind="position" data-fx-count="{{ number_format($position, 2, '.', '') }}">{{ \App\Support\TokenMoney::format($position) }}</div>
                        @elseif($marketCap > 0)
                            <div class="fx-chart__value" style="font-size: 40px" data-fx-bind="market_cap" data-fx-count="{{ number_format($marketCap, 2, '.', '') }}">{{ \App\Support\TokenMoney::format($marketCap) }}</div>
                        @else
                            <div class="fx-chart__value" style="font-size: 40px" data-fx-bind="volume" data-fx-value="{{ $marketData['volume_24h'] }}">{{ \App\Support\TokenMoney::format($marketData['volume_24h']) }}</div>
                        @endif
                        <div style="display: flex; gap: 8px; align-items: center; margin-top: 8px">
                            @if($dir === 'flat')
                                <span class="fx-pill">{{ __('Steady today') }}</span>
                            @else
                                <span class="fx-pill fx-pill--{{ $dir }}">{{ $dir === 'up' ? '▲' : '▼' }} {{ number_format(abs($change), 2) }}%</span>
                                @if($hasWallet)<span class="fx-label {{ 'fx-' . $dir }}">{{ $positionMove >= 0 ? '+' : '−' }}{{ \App\Support\TokenMoney::format(abs($positionMove)) }} {{ __('today') }}</span>@endif
                            @endif
                        </div>
                    </div>
                    <span class="fx-live">{{ __('Live') }}</span>
                </div>
                <div class="fx-chart__box" style="margin-top: 14px">
                    <div class="fx-chart__tip" aria-hidden="true"></div>
                    <div class="fx-chart__empty" hidden>
                        <b>{{ __('No price moves yet') }}</b>
                        <span>{{ __('The line fills in as the price changes.') }}</span>
                    </div>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px">
                    <span class="fx-label">{{ __('Price move') }}</span>
                    <span class="fx-chart__delta fx-flat" data-fx-chart-delta>&nbsp;</span>
                </div>
                <div class="fx-seg" role="group" aria-label="{{ __('Time range') }}">
                    <span class="fx-seg__thumb" aria-hidden="true"></span>
                    <button type="button" value="1d" aria-pressed="false">1D</button>
                    <button type="button" value="1w" aria-pressed="false">1W</button>
                    <button type="button" value="1m" aria-pressed="false">1M</button>
                    <button type="button" value="all" class="is-on" aria-pressed="true">{{ __('All') }}</button>
                </div>
            </section>

            {{-- ── Stats bento ───────────────────────────────────────────────────── --}}
            <section class="fx-bento fx-in" style="--i: 2" aria-label="{{ __('Coin stats') }}">
                <div class="fx-tile"><small>{{ __('Owners') }}</small><b data-fx-plain data-fx-bind="holders" data-fx-value="{{ $marketData['holders'] }}">{{ number_format($marketData['holders']) }}</b></div>
                <div class="fx-tile"><small>{{ __('Traded today') }}</small><b data-fx-bind="volume" data-fx-value="{{ $marketData['volume_24h'] }}">{{ \App\Support\TokenMoney::format($marketData['volume_24h']) }}</b></div>
                <div class="fx-tile"><small>{{ __('Trades today') }}</small><b data-fx-plain data-fx-bind="trades" data-fx-value="{{ $marketData['today_transactions'] ?? 0 }}">{{ number_format($marketData['today_transactions'] ?? 0) }}</b></div>
                <div class="fx-tile"><small>{{ __('Left to buy') }}</small><b data-fx-bind="available" data-fx-value="{{ \App\Support\TokenMoney::toUsd($available, $price) }}">{{ \App\Support\TokenMoney::format(\App\Support\TokenMoney::toUsd($available, $price)) }}</b></div>
                <div class="fx-tile fx-tile--wide">
                    <div style="display: flex; justify-content: space-between; align-items: baseline">
                        <small>{{ __('Supply claimed') }}</small>
                        <span class="fx-num" style="font-weight: 600">{{ number_format($claimedPct, $claimedPct > 0 && $claimedPct < 1 ? 2 : 0) }}%</span>
                    </div>
                    <div class="fx-meter"><span style="width: {{ $claimedPct }}%"></span></div>
                    @if($hasWallet)
                        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-top: 14px">
                            <small>{{ __('You own') }}</small>
                            <span class="fx-num" style="font-weight: 600">{{ number_format($myShare, $myShare < 1 ? 2 : 1) }}% {{ __('of all') }}</span>
                        </div>
                        <div class="fx-meter"><span style="width: {{ min(100, $myShare) }}%; background: linear-gradient(90deg, var(--fx-up), var(--fx-cyan))"></span></div>
                    @endif
                </div>
            </section>

            {{-- ── Activity ─────────────────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 3">
                <div class="fx-head"><h2 class="fx-head__title">{{ __('Recent trades') }}</h2></div>
                @if($recentTxns->count())
                    <div class="fx-list">
                        @foreach($recentTxns as $t)
                            @php $type = $t->type ?? 'other'; $in = $type === 'buy'; @endphp
                            <div class="fx-row">
                                <span class="fx-ico {{ $in ? 'fx-ico--in' : '' }}" aria-hidden="true">
                                    @if($in)
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 7 7 17M16 17H7V8"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
                                    @endif
                                </span>
                                <span class="fx-row__main">
                                    <span class="fx-row__title" style="font-size: 14.5px">{{ $txnLabels[$type] ?? ucfirst($type) }}</span>
                                    <span class="fx-row__sub">{{ $t->created_at->diffForHumans() }}</span>
                                </span>
                                <span class="fx-row__end"><span class="fx-row__value">{{ \App\Support\TokenMoney::format((float) ($t->total_price ?? 0)) }}</span></span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="fx-empty">
                        <span class="fx-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg></span>
                        <h3>{{ __('No trades yet') }}</h3>
                        <p>{{ __('Be the first to back :name.', ['name' => $coin->name]) }}</p>
                    </div>
                @endif
            </section>
        </div>

        <div class="fx-col">
            {{-- ── About + creator ──────────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 2">
                <div class="fx-head"><h2 class="fx-head__title">{{ __('About') }}</h2></div>
                @if($creator)
                    <a href="{{ route('profile', ['username' => $creator->username]) }}" class="fx-row" style="margin: -4px 0 10px; padding-top: 0">
                        <span class="fx-coin" style="background: none"><img src="{{ $creator->avatar }}" alt=""></span>
                        <span class="fx-row__main">
                            <span class="fx-row__title">{{ $creator->name }}</span>
                            <span class="fx-row__sub">{{ '@' . $creator->username }} · {{ __('Creator') }}</span>
                        </span>
                        <span class="fx-head__link">{{ __('Profile') }} {!! $chev !!}</span>
                    </a>
                @endif
                <p style="font-size: 14.5px; line-height: 1.6; color: var(--fx-dim)">
                    {{ $coin->description ?: __(':name is backed by its creator. Buy in to support them.', ['name' => $coin->name]) }}
                </p>
                <div class="fx-kv" style="margin-top: 14px">
                    <div class="fx-kv__row"><span>{{ __('Launched') }}</span><b>{{ $coin->created_at->format('M j, Y') }}</b></div>
                    <div class="fx-kv__row"><span>{{ __('All-time trades') }}</span><b>{{ number_format($marketData['total_transactions'] ?? 0) }}</b></div>
                </div>
                @if($coin->website || $coin->whitepaper)
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 14px">
                        @if($coin->website)<a href="{{ $coin->website }}" target="_blank" rel="noopener noreferrer" class="fx-btn fx-btn--ghost fx-btn--sm">{{ __('Website') }} ↗</a>@endif
                        @if($coin->whitepaper)<a href="{{ $coin->whitepaper }}" target="_blank" rel="noopener noreferrer" class="fx-btn fx-btn--ghost fx-btn--sm">{{ __('Learn more') }} ↗</a>@endif
                    </div>
                @endif
            </section>

            {{-- ── Fees ─────────────────────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 3">
                <div class="fx-head"><h2 class="fx-head__title">{{ __('Fees') }}</h2><span class="fx-label">{{ __('Buying is free') }}</span></div>
                <div class="fx-kv">
                    <div class="fx-kv__row"><span>{{ __('To the creator, when you sell') }}</span><b>{{ number_format($creatorFee, 2) }}%</b></div>
                    <div class="fx-kv__row"><span>{{ __('Platform, when you sell') }}</span><b>{{ number_format($platformFee, 2) }}%</b></div>
                    <div class="fx-kv__row"><span>{{ __('Sell $100, you get') }}</span><b class="fx-up">{{ \App\Support\TokenMoney::format(max(0, 100 * (1 - ($creatorFee + $platformFee) / 100))) }}</b></div>
                </div>
            </section>

            {{-- Buy / Sell: a fixed bar on phones, inline on desktop. --}}
            <div class="fx-bar">
                <a href="{{ route('cryptocurrency.buy.form', $coin->id) }}" class="fx-btn fx-btn--primary">{{ $hasWallet ? __('Buy more') : __('Buy from $1') }}</a>
                @if($hasWallet)
                    <a href="{{ route('cryptocurrency.sell.form', $coin->id) }}" class="fx-btn fx-btn--ghost">{{ __('Sell') }}</a>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
<script src="{{ asset('js/pages/fx.js') }}?v=20260925a" defer></script>
@endsection
