@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $change = (float) ($cryptocurrency->price_change_percentage ?? $cryptocurrency->change_24h ?? 0);
    $isUp = $change >= 0;
    $priceDecimals = $cryptocurrency->current_price < 1 ? 6 : 2;
    $hasWallet = $wallet && $wallet->balance > 0;
    $recentTxns = $cryptocurrency->relationLoaded('transactions')
        ? $cryptocurrency->transactions
        : $cryptocurrency->transactions()->latest()->take(10)->get();
    $todayTxns = $marketData['today_transactions'] ?? 0;
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/token-show.css') }}?v=20260713b">
@endsection

@section('content')
<div class="ts-page {{ $isDarkTheme ? 'ts-page--dark' : 'ts-page--light' }}">
<div class="ts-container">

    {{-- Hero --}}
    <section class="ts-hero">
        <div class="ts-hero__id">
            @if($cryptocurrency->logo)
                <img src="{{ asset('storage/' . $cryptocurrency->logo) }}" alt="{{ $cryptocurrency->name }}" class="ts-hero__logo">
            @else
                <span class="ts-hero__logo ts-hero__logo--fallback">{{ strtoupper(substr($cryptocurrency->symbol, 0, 2)) }}</span>
            @endif
            <div class="ts-hero__meta">
                <div class="ts-hero__name-row">
                    <h1 class="ts-hero__name">{{ $cryptocurrency->name }}</h1>
                    <span class="ts-hero__symbol">{{ $cryptocurrency->symbol }}</span>
                    @if($cryptocurrency->is_verified)
                        <span class="ts-hero__verified" title="{{ __('Verified') }}" aria-label="{{ __('Verified') }}">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/></svg>
                        </span>
                    @endif
                </div>
                <div class="ts-hero__price-row">
                    <span class="ts-hero__price">${{ number_format($cryptocurrency->current_price, $priceDecimals) }}</span>
                    <span class="ts-hero__change ts-hero__change--{{ $isUp ? 'up' : 'down' }}">
                        {{ $isUp ? '+' : '' }}{{ number_format($change, 2) }}%
                        <span class="ts-hero__change-label">24h</span>
                    </span>
                </div>
            </div>
        </div>

        <div class="ts-hero__actions">
            <a href="{{ route('cryptocurrency.buy.form', $cryptocurrency->id) }}" class="ts-btn ts-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Buy') }}
            </a>
            @if($hasWallet)
                <a href="{{ route('cryptocurrency.sell.form', $cryptocurrency->id) }}" class="ts-btn ts-btn--ghost">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ __('Sell') }}
                </a>
            @endif
        </div>
    </section>

    {{-- Portfolio --}}
    @if($hasWallet)
    <section class="ts-section">
        <div class="ts-section__head">
            <h2 class="ts-section__title">{{ __('Your Portfolio') }}</h2>
        </div>
        <div class="ts-stats ts-stats--3">
            <div class="ts-stat">
                <span class="ts-stat__label">{{ $cryptocurrency->symbol }} {{ __('Balance') }}</span>
                <span class="ts-stat__value">{{ rtrim(rtrim(number_format($wallet->balance, 8), '0'), '.') }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('USD Value') }}</span>
                <span class="ts-stat__value">${{ number_format($wallet->balance * $cryptocurrency->current_price, 2) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Of Total Supply') }}</span>
                <span class="ts-stat__value">
                    {{ $cryptocurrency->total_supply > 0 ? number_format(($wallet->balance / $cryptocurrency->total_supply) * 100, 2) : '0.00' }}%
                </span>
            </div>
        </div>
    </section>
    @endif

    {{-- Market stats --}}
    <section class="ts-section">
        <div class="ts-section__head">
            <h2 class="ts-section__title">{{ __('Market Statistics') }}</h2>
        </div>
        <div class="ts-stats">
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Market Cap') }}</span>
                <span class="ts-stat__value">${{ number_format($cryptocurrency->market_cap ?? 0, 2) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('24h Volume') }}</span>
                <span class="ts-stat__value">${{ number_format($cryptocurrency->volume_24h ?? 0, 2) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('24h Transactions') }}</span>
                <span class="ts-stat__value">{{ number_format($todayTxns) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Total Supply') }}</span>
                <span class="ts-stat__value">{{ number_format($cryptocurrency->total_supply ?? 0, 0) }}</span>
                <span class="ts-stat__sub">{{ $cryptocurrency->symbol }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Available') }}</span>
                <span class="ts-stat__value">{{ number_format($cryptocurrency->available_supply ?? 0, 0) }}</span>
                <span class="ts-stat__sub">{{ $cryptocurrency->symbol }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Circulating') }}</span>
                <span class="ts-stat__value">{{ number_format($cryptocurrency->circulating_supply ?? 0, 0) }}</span>
                <span class="ts-stat__sub">{{ $cryptocurrency->symbol }}</span>
            </div>
        </div>
    </section>

    {{-- Details + Fees --}}
    <div class="ts-split">
        <section class="ts-card">
            <h2 class="ts-card__title">{{ __('Token Details') }}</h2>
            <dl class="ts-dl">
                <div class="ts-dl__row">
                    <dt>{{ __('Network') }}</dt>
                    <dd>{{ ucfirst($cryptocurrency->blockchain_network ?? '—') }}</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Type') }}</dt>
                    <dd>{{ ucfirst($cryptocurrency->token_type ?? '—') }}</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Initial Price') }}</dt>
                    <dd>${{ number_format($cryptocurrency->initial_price ?? 0, 8) }}</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Created') }}</dt>
                    <dd>{{ $cryptocurrency->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>
            @if($cryptocurrency->transferable || $cryptocurrency->enable_burning || $cryptocurrency->enable_minting)
                <div class="ts-features">
                    @if($cryptocurrency->transferable)
                        <span class="ts-chip">{{ __('Transferable') }}</span>
                    @endif
                    @if($cryptocurrency->enable_burning)
                        <span class="ts-chip">{{ __('Burnable') }}</span>
                    @endif
                    @if($cryptocurrency->enable_minting)
                        <span class="ts-chip">{{ __('Mintable') }}</span>
                    @endif
                </div>
            @endif
        </section>

        <section class="ts-card">
            <h2 class="ts-card__title">{{ __('Fee Structure') }}</h2>
            <dl class="ts-dl">
                <div class="ts-dl__row">
                    <dt>{{ __('Creator Fee') }}</dt>
                    <dd>{{ number_format($cryptocurrency->creator_fee_percentage ?? 0, 2) }}%</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Platform Fee') }}</dt>
                    <dd>{{ number_format($cryptocurrency->platform_fee_percentage ?? 0, 2) }}%</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Liquidity Pool') }}</dt>
                    <dd>{{ number_format($cryptocurrency->liquidity_pool_percentage ?? 0, 2) }}%</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Creator') }}</dt>
                    <dd>{{ $cryptocurrency->creator->name ?? __('Unknown') }}</dd>
                </div>
                @if($cryptocurrency->contract_address)
                <div class="ts-dl__row">
                    <dt>{{ __('Contract') }}</dt>
                    <dd class="ts-mono" title="{{ $cryptocurrency->contract_address }}">
                        {{ substr($cryptocurrency->contract_address, 0, 8) }}…{{ substr($cryptocurrency->contract_address, -6) }}
                    </dd>
                </div>
                @endif
            </dl>
        </section>
    </div>

    {{-- About --}}
    @if($cryptocurrency->description || $cryptocurrency->website || $cryptocurrency->whitepaper)
    <section class="ts-card">
        <h2 class="ts-card__title">{{ __('About') }} {{ $cryptocurrency->name }}</h2>
        @if($cryptocurrency->description)
            <p class="ts-about">{{ $cryptocurrency->description }}</p>
        @endif
        @if($cryptocurrency->website || $cryptocurrency->whitepaper)
            <div class="ts-links">
                @if($cryptocurrency->website)
                    <a href="{{ $cryptocurrency->website }}" target="_blank" rel="noopener noreferrer" class="ts-btn ts-btn--ghost ts-btn--sm">{{ __('Visit Website') }}</a>
                @endif
                @if($cryptocurrency->whitepaper)
                    <a href="{{ $cryptocurrency->whitepaper }}" target="_blank" rel="noopener noreferrer" class="ts-btn ts-btn--ghost ts-btn--sm">{{ __('Read Whitepaper') }}</a>
                @endif
            </div>
        @endif
    </section>
    @endif

    {{-- Recent transactions --}}
    <section class="ts-section">
        <div class="ts-section__head">
            <h2 class="ts-section__title">{{ __('Recent Transactions') }}</h2>
        </div>

        <div class="ts-txn-list" id="transactions-table">
            @forelse($recentTxns as $transaction)
                @php $txnType = $transaction->type ?? 'other'; @endphp
                <article class="ts-txn">
                    <span class="ts-txn__icon ts-txn__icon--{{ $txnType }}" aria-hidden="true">
                        @if($txnType === 'buy')
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @elseif($txnType === 'sell')
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10l-3-3M17 17H7l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <div class="ts-txn__main">
                        <div class="ts-txn__top">
                            <span class="ts-txn__title">{{ ucfirst($txnType) }}</span>
                            <span class="ts-txn__total">${{ number_format($transaction->total_price ?? 0, 2) }}</span>
                        </div>
                        <div class="ts-txn__meta">
                            <span>{{ rtrim(rtrim(number_format($transaction->amount ?? 0, 8), '0'), '.') }} {{ $cryptocurrency->symbol }}</span>
                            <span class="ts-txn__dot" aria-hidden="true"></span>
                            <span>${{ number_format($transaction->price_per_token ?? 0, 6) }}</span>
                            <span class="ts-txn__dot" aria-hidden="true"></span>
                            <span>{{ $transaction->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="ts-empty">
                    <h4>{{ __('No transactions yet') }}</h4>
                    <p>{{ __('Be the first to trade this token') }}</p>
                    <a href="{{ route('cryptocurrency.buy.form', $cryptocurrency->id) }}" class="ts-btn ts-btn--primary ts-btn--sm">{{ __('Buy Now') }}</a>
                </div>
            @endforelse
        </div>
    </section>

</div>
</div>
@endsection
