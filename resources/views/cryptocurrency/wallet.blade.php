@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $portfolioChangeAmount = $totalBalance * ($portfolioChange24h / 100);

    // Deterministic mini-sparkline path generator (visual only, no historical API).
    $walletSpark = function ($seed, $isUp) {
        mt_srand(crc32((string) $seed));
        $count = 18;
        $w = 120;
        $h = 44;
        $val = 50;
        $points = [];
        for ($i = 0; $i < $count; $i++) {
            $val += mt_rand(-13, 13);
            $val = max(12, min(88, $val));
            $points[] = $val;
        }
        // Bias the tail so the trend visually matches the 24h direction.
        $points[$count - 1] = $isUp ? max($points[$count - 1], 70) : min($points[$count - 1], 30);
        $points[$count - 2] = $isUp ? max($points[$count - 2], 58) : min($points[$count - 2], 42);
        mt_srand();

        $step = $w / ($count - 1);
        $path = '';
        foreach ($points as $i => $p) {
            $x = round($i * $step, 2);
            $y = round($h - ($p / 100 * $h), 2);
            $path .= ($i === 0 ? 'M' : 'L') . $x . ' ' . $y . ' ';
        }
        return trim($path);
    };
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/wallet.css') }}?v=20260713g">
@endsection

@section('content')
<div class="wallet-page {{ $isDarkTheme ? 'wallet-page--dark' : 'wallet-page--light' }}">
<div class="wallet-container">

    <!-- Balance hero -->
    <section class="wallet-hero">
        <div class="wallet-hero__top">
            <span class="wallet-hero__label">{{ __('Wallet Balance') }}</span>
            <button type="button" class="wallet-hero__eye" id="walletBalanceToggle" aria-label="{{ __('Toggle balance visibility') }}">
                <svg class="wallet-hero__eye-open" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                <svg class="wallet-hero__eye-closed" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" hidden>
                    <path d="M3 3l18 18M10.6 10.6a3 3 0 004.24 4.24M9.9 5.2A9.7 9.7 0 0112 5c6.5 0 10 7 10 7a17.6 17.6 0 01-3.4 4.3M6.1 6.1A17.7 17.7 0 002 12s3.5 7 10 7c1 0 1.96-.14 2.86-.4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="wallet-hero__balance" data-balance="${{ number_format($totalBalance, 2) }}">${{ number_format($totalBalance, 2) }}</div>

        <div class="wallet-hero__change wallet-hero__change--{{ $portfolioChange24h >= 0 ? 'up' : 'down' }}">
            <span class="wallet-hero__change-amount">{{ $portfolioChange24h >= 0 ? '+' : '-' }}${{ number_format(abs($portfolioChangeAmount), 2) }}</span>
            <span class="wallet-hero__change-divider" aria-hidden="true"></span>
            <span class="wallet-hero__change-pct">{{ $portfolioChange24h >= 0 ? '+' : '' }}{{ number_format($portfolioChange24h, 2) }}%</span>
        </div>

        <div class="wallet-hero__actions">
            <a href="{{ route('cryptocurrency.deposit') }}" class="wallet-btn wallet-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Deposit') }}
            </a>
            <a href="{{ route('cryptocurrency.withdraw') }}" class="wallet-btn wallet-btn--ghost">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Withdraw') }}
            </a>
        </div>

        <ul class="wallet-hero__meta">
            <li>
                <span class="wallet-hero__meta-label">{{ __('Available') }}</span>
                <span class="wallet-hero__meta-value">${{ number_format($availableBalance, 2) }}</span>
            </li>
            <li>
                <span class="wallet-hero__meta-label">{{ __('Pending') }}</span>
                <span class="wallet-hero__meta-value">${{ number_format($pendingBalance, 2) }}</span>
            </li>
            <li>
                <span class="wallet-hero__meta-label">{{ __('Tokens') }}</span>
                <span class="wallet-hero__meta-value">{{ $wallets->count() }}</span>
            </li>
        </ul>
    </section>

    <!-- My Tokens -->
    <section class="wallet-block">
        <div class="wallet-block__head">
            <h2 class="wallet-block__title">{{ __('My Tokens') }}</h2>
            <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-block__link">{{ __('See all') }}</a>
        </div>

        @forelse($wallets as $wallet)
            @php
                $isUp = ($wallet->cryptocurrency->price_change_24h ?? 0) >= 0;
                $tokenValue = $wallet->balance * $wallet->cryptocurrency->current_price;
            @endphp
            <a href="{{ route('cryptocurrency.show', $wallet->cryptocurrency->id) }}" class="token-item">
                <div class="token-item__row">
                    <div class="token-item__id">
                        @if($wallet->cryptocurrency->logo)
                            <img src="{{ asset('storage/' . $wallet->cryptocurrency->logo) }}" alt="{{ $wallet->cryptocurrency->name }}" class="token-item__logo">
                        @else
                            <span class="token-item__symbol-circle">{{ substr($wallet->cryptocurrency->symbol, 0, 1) }}</span>
                        @endif
                        <div class="token-item__meta">
                            <span class="token-item__name">{{ $wallet->cryptocurrency->name }}</span>
                            <span class="token-item__price">
                                ${{ number_format($wallet->cryptocurrency->current_price, $wallet->cryptocurrency->current_price < 1 ? 4 : 2) }}
                                <span class="token-item__delta token-item__delta--{{ $isUp ? 'up' : 'down' }}">
                                    {{ $isUp ? '+' : '' }}{{ number_format($wallet->cryptocurrency->price_change_24h ?? 0, 2) }}%
                                </span>
                            </span>
                        </div>
                    </div>
                    <div class="token-item__spark">
                        <svg viewBox="0 0 120 44" preserveAspectRatio="none" aria-hidden="true">
                            <path d="{{ $walletSpark($wallet->cryptocurrency->symbol, $isUp) }}"
                                  fill="none"
                                  stroke="{{ $isUp ? '#22c55e' : '#f43f5e' }}"
                                  stroke-width="2"
                                  stroke-linecap="round"
                                  stroke-linejoin="round"/>
                        </svg>
                    </div>
                </div>
                <div class="token-item__foot">
                    <span class="token-item__amount">{{ rtrim(rtrim(number_format($wallet->balance, 4), '0'), '.') }} {{ $wallet->cryptocurrency->symbol }}</span>
                    <span class="token-item__value">${{ number_format($tokenValue, 2) }}</span>
                </div>
            </a>
        @empty
            <div class="wallet-empty">
                <span class="wallet-empty__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M21 12V7H5a2 2 0 0 1-2-2 2 2 0 0 1 2-2h14v4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-9" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M18 12a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h2v-5h-2z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <h4>{{ __("You don't own any tokens yet") }}</h4>
                <p>{{ __('Start by buying tokens from content creators you support') }}</p>
                <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Browse Marketplace') }}</a>
            </div>
        @endforelse
    </section>

    <!-- Transaction History -->
    <section class="wallet-block" id="transaction-history">
        <div class="wallet-block__head">
            <h2 class="wallet-block__title">{{ __('Transaction History') }}</h2>
        </div>

        <div class="wallet-filters">
            <a href="{{ route('cryptocurrency.transactions', ['type' => 'all']) }}" class="wallet-filter {{ request('type', 'all') == 'all' ? 'is-active' : '' }}">{{ __('All') }}</a>
            <a href="{{ route('cryptocurrency.transactions', ['type' => 'buy']) }}" class="wallet-filter {{ request('type') == 'buy' ? 'is-active' : '' }}">{{ __('Buy') }}</a>
            <a href="{{ route('cryptocurrency.transactions', ['type' => 'sell']) }}" class="wallet-filter {{ request('type') == 'sell' ? 'is-active' : '' }}">{{ __('Sell') }}</a>
            <a href="{{ route('cryptocurrency.transactions', ['type' => 'transfer']) }}" class="wallet-filter {{ request('type') == 'transfer' ? 'is-active' : '' }}">{{ __('Transfer') }}</a>
        </div>

        @forelse($transactions as $txn)
            @php
                $txnUp = $txn->type == 'buy';
            @endphp
            <div class="txn-item">
                <span class="txn-item__icon txn-item__icon--{{ $txn->type }}" aria-hidden="true">
                    @if($txn->type == 'buy')
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @elseif($txn->type == 'sell')
                        <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10l-3-3M17 17H7l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    @endif
                </span>
                <div class="txn-item__meta">
                    <span class="txn-item__title">{{ ucfirst($txn->type) }} {{ $txn->cryptocurrency->symbol ?? '' }}</span>
                    <span class="txn-item__date">{{ $txn->created_at->format('M d, Y · H:i') }}</span>
                </div>
                <div class="txn-item__right">
                    <span class="txn-item__amount">${{ number_format($txn->total_price, 2) }}</span>
                    <span class="txn-item__status txn-item__status--{{ $txn->status }}">{{ ucfirst($txn->status) }}</span>
                </div>
            </div>
        @empty
            <div class="wallet-empty">
                <span class="wallet-empty__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 3h6a1 1 0 0 1 1 1v2H8V4a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 12h6M9 16h4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                    </svg>
                </span>
                <h4>{{ __('No transactions found') }}</h4>
                <p>{{ __('Your transaction history will appear here') }}</p>
            </div>
        @endforelse

        @if(isset($transactions) && $transactions->hasPages())
            <div class="wallet-pagination">
                {{ $transactions->links() }}
            </div>
        @endif
    </section>

</div>
</div>

<script>
    (function () {
        var toggle = document.getElementById('walletBalanceToggle');
        var balance = document.querySelector('.wallet-hero__balance');
        if (!toggle || !balance) return;
        var open = toggle.querySelector('.wallet-hero__eye-open');
        var closed = toggle.querySelector('.wallet-hero__eye-closed');
        var hidden = false;
        toggle.addEventListener('click', function () {
            hidden = !hidden;
            if (hidden) {
                balance.dataset.value = balance.textContent;
                balance.textContent = '••••••';
                document.querySelector('.wallet-hero').classList.add('is-hidden');
            } else {
                balance.textContent = balance.dataset.value || balance.getAttribute('data-balance');
                document.querySelector('.wallet-hero').classList.remove('is-hidden');
            }
            if (open) open.hidden = hidden;
            if (closed) closed.hidden = !hidden;
        });
    })();
</script>

@endsection
