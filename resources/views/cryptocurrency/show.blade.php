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
    // Dollars only: what the viewer holds, and how much that moved today, in dollars.
    $holdingUsd = $hasWallet ? \App\Support\TokenMoney::toUsd((float) $wallet->balance, (float) $cryptocurrency->current_price) : 0;
    $holdingMove = \App\Support\TokenMoney::changeUsd($holdingUsd, $change);
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/token-show.css') }}?v=20260917a">
@endsection

@section('content')
<div class="ts-page {{ $isDarkTheme ? 'ts-page--dark' : 'ts-page--light' }}">
<div class="ts-container">

    @foreach(['success', 'error'] as $flash)
        @if(session($flash))
            <div class="ts-flash ts-flash--{{ $flash }}" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach

    {{-- Hero --}}
    <section class="ts-hero">
        <div class="ts-hero__id">
            @if($cryptocurrency->logo)
                <img src="{{ asset('storage/' . $cryptocurrency->logo) }}" alt="{{ $cryptocurrency->name }}" class="ts-hero__logo">
            @else
                <span class="ts-hero__logo ts-hero__logo--fallback">{{ strtoupper(substr($cryptocurrency->name, 0, 1)) }}</span>
            @endif
            <div class="ts-hero__meta">
                <div class="ts-hero__name-row">
                    <h1 class="ts-hero__name">{{ $cryptocurrency->name }}</h1>

                    @if($cryptocurrency->is_verified)
                        <span class="ts-hero__verified" title="{{ __('Verified') }}" aria-label="{{ __('Verified') }}">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/></svg>
                        </span>
                    @endif
                </div>
                <div class="ts-hero__price-row">
                    @if($hasWallet)
                        <span class="ts-hero__price">{{ \App\Support\TokenMoney::format($holdingUsd) }}</span>
                    @endif
                    @if(abs($change) < 0.005)
                        <span class="ts-hero__change ts-hero__change--flat">{{ __('No change today') }}</span>
                    @else
                        <span class="ts-hero__change ts-hero__change--{{ $isUp ? 'up' : 'down' }}">
                            {{ $isUp ? '+' : '' }}{{ number_format($change, 2) }}%
                            <span class="ts-hero__change-label">{{ __('today') }}</span>
                        </span>
                    @endif
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
            <h2 class="ts-section__title">{{ __('What you own') }}</h2>
        </div>
        <div class="ts-stats ts-stats--3">
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Worth now') }}</span>
                <span class="ts-stat__value">{{ \App\Support\TokenMoney::format($holdingUsd) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Change today') }}</span>
                <span class="ts-stat__value">{{ $holdingMove >= 0 ? '+' : '' }}{{ \App\Support\TokenMoney::format($holdingMove) }}</span>
            </div>
        </div>
    </section>
    @endif

    {{-- Market stats --}}
    <section class="ts-section">
        <div class="ts-section__head">
            <h2 class="ts-section__title">{{ __('Activity') }}</h2>
        </div>
        <div class="ts-stats">
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Total value') }}</span>
                <span class="ts-stat__value">
                    @if((float) $cryptocurrency->market_cap > 0)
                        {{ \App\Support\TokenMoney::format((float) $cryptocurrency->market_cap) }}
                    @else
                        &mdash;
                    @endif
                </span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Traded today') }}</span>
                <span class="ts-stat__value">${{ number_format($cryptocurrency->volume_24h ?? 0, 2) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Trades today') }}</span>
                <span class="ts-stat__value">{{ number_format($todayTxns) }}</span>
            </div>
            <div class="ts-stat">
                <span class="ts-stat__label">{{ __('Available to buy') }}</span>
                <span class="ts-stat__value">{{ \App\Support\TokenMoney::format(\App\Support\TokenMoney::toUsd((float) ($cryptocurrency->available_supply ?? 0), (float) $cryptocurrency->current_price)) }}</span>
            </div>
        </div>
    </section>

    {{-- Details + Fees --}}
    <div class="ts-split">
        <section class="ts-card">
            <h2 class="ts-card__title">{{ __('About this coin') }}</h2>
            <dl class="ts-dl">
                <div class="ts-dl__row">
                    <dt>{{ __('Creator') }}</dt>
                    <dd>{{ $cryptocurrency->creator->name ?? __('Unknown') }}</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Created') }}</dt>
                    <dd>{{ $cryptocurrency->created_at->format('M d, Y') }}</dd>
                </div>
            </dl>

        </section>

        <section class="ts-card">
            <h2 class="ts-card__title">{{ __('Fees when you sell') }}</h2>
            <dl class="ts-dl">
                <div class="ts-dl__row">
                    <dt>{{ __('Creator Fee') }}</dt>
                    <dd>{{ number_format($cryptocurrency->creator_fee_percentage ?? 0, 2) }}%</dd>
                </div>
                <div class="ts-dl__row">
                    <dt>{{ __('Platform Fee') }}</dt>
                    <dd>{{ number_format($cryptocurrency->platform_fee_percentage ?? 0, 2) }}%</dd>
                </div>

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
                    <a href="{{ $cryptocurrency->whitepaper }}" target="_blank" rel="noopener noreferrer" class="ts-btn ts-btn--ghost ts-btn--sm">{{ __('Learn more') }}</a>
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
                            <span class="ts-txn__title">{{ ['buy' => __('Bought'), 'sell' => __('Sold'), 'transfer' => __('Sent')][$txnType] ?? ucfirst($txnType) }}</span>
                            <span class="ts-txn__total">${{ number_format($transaction->total_price ?? 0, 2) }}</span>
                        </div>
                        <div class="ts-txn__meta">
                            <span>{{ $transaction->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </article>
            @empty
                <div class="ts-empty">
                    <h4>{{ __('No transactions yet') }}</h4>
                    <p>{{ __('Be the first to buy :name', ['name' => $cryptocurrency->name]) }}</p>
                    <a href="{{ route('cryptocurrency.buy.form', $cryptocurrency->id) }}" class="ts-btn ts-btn--primary ts-btn--sm">{{ __('Buy Now') }}</a>
                </div>
            @endforelse
        </div>
    </section>

</div>
</div>
@endsection
