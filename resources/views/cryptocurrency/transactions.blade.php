@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $filterTypes = [
        'all' => __('All'),
        'buy' => __('Buy'),
        'sell' => __('Sell'),
        'transfer' => __('Transfer'),
        'deposit' => __('Deposit'),
        'withdraw' => __('Withdraw'),
    ];
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/transactions.css') }}?v=20260713a">
@endsection

@section('content')
<div class="txn-page {{ $isDarkTheme ? 'txn-page--dark' : 'txn-page--light' }}">
<div class="txn-container">

    <header class="txn-header">
        <div class="txn-header__text">
            <h1 class="txn-header__title">{{ __('Transaction History') }}</h1>
            <p class="txn-header__sub">{{ __('View all your cryptocurrency transactions') }}</p>
        </div>
        <a href="{{ route('cryptocurrency.wallet') }}" class="txn-back">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ __('Back to Wallet') }}
        </a>
    </header>

    {{-- Stats --}}
    <section class="txn-stats" aria-label="{{ __('Transaction Statistics') }}">
        <div class="txn-stat">
            <span class="txn-stat__label">{{ __('Total') }}</span>
            <span class="txn-stat__value">{{ number_format($stats['total']) }}</span>
        </div>
        <div class="txn-stat">
            <span class="txn-stat__label">{{ __('This Month') }}</span>
            <span class="txn-stat__value">{{ number_format($stats['this_month']) }}</span>
        </div>
        <div class="txn-stat">
            <span class="txn-stat__label">{{ __('Successful') }}</span>
            <span class="txn-stat__value txn-stat__value--ok">{{ number_format($stats['successful']) }}</span>
        </div>
        <div class="txn-stat">
            <span class="txn-stat__label">{{ __('Pending') }}</span>
            <span class="txn-stat__value txn-stat__value--warn">{{ number_format($stats['pending']) }}</span>
        </div>
    </section>

    {{-- Filters + list --}}
    <section class="txn-block">
        <div class="txn-filters" role="tablist" aria-label="{{ __('Filters') }}">
            @foreach($filterTypes as $key => $label)
                <a href="{{ route('cryptocurrency.transactions', ['type' => $key]) }}"
                   class="txn-filter {{ $type === $key ? 'is-active' : '' }}"
                   role="tab"
                   aria-selected="{{ $type === $key ? 'true' : 'false' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="txn-list">
            @forelse($transactions as $transaction)
                @php
                    $txnType = $transaction->type ?? 'other';
                    // Dollars only: the coin's name and the dollar total, never a unit count.
                    $coinName = $transaction->cryptocurrency->name ?? __('Wallet');
                    $verb = ['buy' => __('Bought'), 'sell' => __('Sold'), 'deposit' => __('Added'), 'withdraw' => __('Cashed out'), 'transfer' => __('Sent')][$txnType] ?? ucfirst($txnType);
                @endphp
                <article class="txn-row">
                    <span class="txn-row__icon txn-row__icon--{{ $txnType }}" aria-hidden="true">
                        @if($txnType === 'buy' || $txnType === 'deposit')
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @elseif($txnType === 'sell' || $txnType === 'withdraw')
                            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10l-3-3M17 17H7l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>

                    <div class="txn-row__main">
                        <div class="txn-row__top">
                            <span class="txn-row__title">{{ $verb }} · {{ $coinName }}</span>
                            <span class="txn-row__total">${{ number_format((float) $transaction->total_price, 2) }}</span>
                        </div>
                        <div class="txn-row__meta">
                            <span>{{ $transaction->created_at->format('M d, Y · H:i') }}</span>
                        </div>
                    </div>

                    <span class="txn-row__status txn-row__status--{{ $transaction->status }}">
                        {{ ucfirst($transaction->status) }}
                    </span>
                </article>
            @empty
                <div class="txn-empty">
                    <span class="txn-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 3h6a1 1 0 0 1 1 1v2H8V4a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h4>{{ __('No transactions found') }}</h4>
                    <p>
                        @if($type !== 'all')
                            {{ __('No :type transactions yet. Try another filter.', ['type' => strtolower($filterTypes[$type] ?? $type)]) }}
                        @else
                            {{ __('Everything you buy and sell will appear here') }}
                        @endif
                    </p>
                    @if($type !== 'all')
                        <a href="{{ route('cryptocurrency.transactions', ['type' => 'all']) }}" class="txn-btn txn-btn--primary">{{ __('View All Transactions') }}</a>
                    @else
                        <a href="{{ route('cryptocurrency.marketplace') }}" class="txn-btn txn-btn--primary">{{ __('Browse Marketplace') }}</a>
                    @endif
                </div>
            @endforelse
        </div>

        @if($transactions->hasPages())
            <div class="txn-pagination">
                {{ $transactions->links() }}
            </div>
        @endif
    </section>

    {{-- Help --}}
    <section class="txn-help">
        <h2 class="txn-help__title">{{ __('Need Help?') }}</h2>
        <ul class="txn-help__list">
            <li>{{ __('Pending transactions can take up to 24 hours to process') }}</li>
            <li>{{ __('Failed transactions — check payment details and try again') }}</li>
            <li>{{ __('Withdrawal issues — contact support for assistance') }}</li>
        </ul>
        <a href="{{ route('contact') }}" class="txn-btn txn-btn--ghost">{{ __('Contact Support') }}</a>
    </section>

</div>
</div>
@endsection
