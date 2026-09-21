@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    /**
     * Build a sparkline path from a token's REAL recorded price history.
     * Returns null when there is not enough history to plot — callers render a flat
     * baseline instead. We deliberately never synthesise fake price movement here.
     */
    $sparkPath = function ($crypto) {
        $history = $crypto->price_history;
        if (is_string($history)) {
            $history = json_decode($history, true);
        }
        if (!is_array($history)) {
            return null;
        }

        $points = [];
        foreach ($history as $entry) {
            $price = is_array($entry) ? ($entry['price'] ?? $entry['value'] ?? null) : $entry;
            if (is_numeric($price)) {
                $points[] = (float) $price;
            }
        }
        $points = array_slice($points, -24);
        if (count($points) < 2) {
            return null;
        }

        $min = min($points);
        $max = max($points);
        $range = ($max - $min) ?: 1;
        $w = 120;
        $h = 44;
        $step = $w / (count($points) - 1);

        $path = '';
        foreach ($points as $i => $p) {
            $x = round($i * $step, 2);
            $y = round($h - (($p - $min) / $range) * $h, 2);
            $path .= ($i === 0 ? 'M' : 'L') . $x . ' ' . $y . ' ';
        }

        return trim($path);
    };
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/wallet.css') }}?v=20260921a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
@endsection

@section('content')
<div class="wallet-page {{ $isDarkTheme ? 'wallet-page--dark' : 'wallet-page--light' }}">
<div class="wallet-container">

    {{-- ── Balance card ──────────────────────────────────────────────────────────── --}}
    <section class="wallet-hero" id="walletHero">
        <div class="wallet-hero__top">
            <span class="wallet-hero__label">{{ __('Your balance') }}</span>
            <button type="button" class="wallet-hero__eye" id="walletBalanceToggle" aria-pressed="false" aria-label="{{ __('Hide balance') }}">
                <svg class="wallet-hero__eye-open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                <svg class="wallet-hero__eye-closed" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                    <path d="M3 3l18 18M10.6 10.6a3 3 0 004.24 4.24M9.9 5.2A9.7 9.7 0 0112 5c6.5 0 10 7 10 7a17.6 17.6 0 01-3.4 4.3M6.1 6.1A17.7 17.7 0 002 12s3.5 7 10 7c1 0 1.96-.14 2.86-.4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>

            @if($address)
                <span class="wallet-hero__addr" title="{{ $address }}">
                    <span class="wallet-hero__addr-dot" aria-hidden="true"></span>
                    {{ substr($address, 0, 6) }}…{{ substr($address, -4) }}
                </span>
            @endif
        </div>

        <div class="wallet-hero__balance-row">
            <span class="wallet-hero__coin" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/><path d="M12 6.5v11M14.6 9.3c-.5-.8-1.5-1.3-2.6-1.3-1.5 0-2.6.8-2.6 2s1.1 1.7 2.6 2c1.5.3 2.6.8 2.6 2s-1.1 2-2.6 2c-1.1 0-2.1-.5-2.6-1.3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
            </span>
            <div class="wallet-hero__balance" data-balance="${{ number_format($totalBalance, 2) }}">${{ number_format($totalBalance, 2) }}</div>
        </div>
        <p class="wallet-hero__hint">{{ __("Your spendable balance plus what your coins are worth today") }}</p>

        {{-- Deposit/withdraw route to the REAL payment flow (Stripe/PayPal via my.settings),
             not CryptocurrencyController's stub which records a "completed" deposit without
             ever taking payment. --}}
        <div class="wallet-hero__actions">
            <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'deposit']) }}" class="wallet-btn wallet-btn--money">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                {{ __('Add funds') }}
            </a>
            <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw']) }}" class="wallet-btn wallet-btn--ghost">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Cash out') }}
            </a>
        </div>
    </section>

    {{-- Every form of value the user holds; each tile opens its tab --}}
    <ul class="wallet-breakdown" aria-label="{{ __('Balance breakdown') }}">
        <li>
            <button type="button" class="wallet-breakdown__item" data-open-panel="overview">
                <span class="wallet-breakdown__label">{{ __('Credits') }}</span>
                <span class="wallet-breakdown__value is-money">${{ number_format($credits, 2) }}</span>
                <span class="wallet-breakdown__sub">{{ __('Ready to spend') }}</span>
            </button>
        </li>
        <li>
            <button type="button" class="wallet-breakdown__item" data-open-panel="tokens">
                <span class="wallet-breakdown__label">{{ __('Coins') }}</span>
                <span class="wallet-breakdown__value is-money">${{ number_format($tokenValue, 2) }}</span>
                <span class="wallet-breakdown__sub">{{ trans_choice('{0} None held|{1} :count coin|[2,*] :count coins', $tokenWallets->count(), ['count' => $tokenWallets->count()]) }}</span>
            </button>
        </li>
        <li>
            <button type="button" class="wallet-breakdown__item" data-open-panel="coins">
                <span class="wallet-breakdown__label">{{ __('Creator coins') }}</span>
                <span class="wallet-breakdown__value">{{ number_format($coinBalances->count()) }}</span>
                <span class="wallet-breakdown__sub">{{ $coinValue > 0 ? '≈ $' . number_format($coinValue, 2) . ' ' . __('in perks') : __('Back a creator') }}</span>
            </button>
        </li>
        <li>
            <button type="button" class="wallet-breakdown__item" data-open-panel="nfts">
                <span class="wallet-breakdown__label">{{ __('NFTs') }}</span>
                <span class="wallet-breakdown__value">{{ number_format($nftCount) }}</span>
                <span class="wallet-breakdown__sub">{{ $nftCount ? __('In your collection') : __('Mint your first') }}</span>
            </button>
        </li>
    </ul>

    {{-- ── Tabs ──────────────────────────────────────────────────────────────────── --}}
    <nav class="wallet-tabs" role="tablist" aria-label="{{ __('Wallet sections') }}">
        <button class="wallet-tab is-active" role="tab" aria-selected="true" data-panel="overview">{{ __('Overview') }}</button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="tokens">{{ __('Coins') }} <span class="wallet-tab__count">{{ $tokenWallets->count() }}</span></button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="nfts">{{ __('NFTs') }} <span class="wallet-tab__count">{{ $nftCount }}</span></button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="coins">{{ __('Creator coins') }} <span class="wallet-tab__count">{{ $coinBalances->count() }}</span></button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="activity">{{ __('Activity') }}</button>
    </nav>

    {{-- ── Overview ──────────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel is-active" id="wallet-panel-overview" role="tabpanel">
        {{-- Everything a crypto-naive user needs is in dollars above. Connecting an outside
             crypto wallet, addresses and raw sends are for people who already use crypto, so
             they live behind this disclosure instead of greeting everyone. --}}
        <details class="wallet-advanced">
        <summary class="wallet-advanced__summary">
            <span>{{ __('Use an outside crypto wallet') }}</span>
            <span class="wallet-advanced__tag">{{ __('Advanced') }}</span>
        </summary>
        @include('nft.partials.wallet-connect')

        @if($address)
            <div class="wallet-split">
                {{-- Receive --}}
                <div class="wallet-block">
                    <div class="wallet-block__head">
                        <h2 class="wallet-block__title">{{ __('Receive') }}</h2>
                    </div>
                    <div class="wallet-receive">
                        <div id="wallet-qr" class="wallet-receive__qr"></div>
                        <div class="wallet-receive__addr">
                            <input type="text" id="wallet-address-field" value="{{ $address }}" readonly aria-label="{{ __('Your wallet address') }}">
                            <button type="button" id="copy-address-btn" class="wallet-btn wallet-btn--ghost wallet-btn--inline" aria-label="{{ __('Copy address') }}">
                                {{ __('Copy') }}
                            </button>
                        </div>
                        <p class="wallet-note">{{ __('Only for sending from an outside crypto wallet.') }}</p>
                    </div>
                </div>

                {{-- Send --}}
                <div class="wallet-block">
                    <div class="wallet-block__head">
                        <h2 class="wallet-block__title">{{ __('Send') }} {{ strtoupper(config('web3.network')) }}</h2>
                        @if($nativeBalance !== null)
                            <span class="wallet-block__link">{{ number_format($nativeBalance, 4) }}</span>
                        @endif
                    </div>
                    <div class="wallet-form">
                        <label class="wallet-field">
                            <span>{{ __('Recipient address') }}</span>
                            <input type="text" id="send-to" placeholder="0x...">
                        </label>
                        <label class="wallet-field">
                            <span>{{ __('Amount') }}</span>
                            <input type="number" id="send-amount" step="0.0001" min="0" placeholder="0.0">
                        </label>
                        <button type="button" class="wallet-btn wallet-btn--primary wallet-btn--inline" id="send-btn">{{ __('Send') }}</button>
                        <div id="send-status" class="wallet-note"></div>
                        <p class="wallet-note">
                            {{ __('Sends are signed by your own wallet — the platform never holds your keys.') }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
        </details>

        {{-- Quick links keep the rest of the ecosystem one tap away --}}
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('Explore') }}</h2>
            </div>
            <div class="wallet-links">
                <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-link">
                    <span class="wallet-link__title">{{ __('Coins') }}</span>
                    <span class="wallet-link__sub">{{ __('Buy from $1') }}</span>
                </a>
                <a href="{{ route('nft.marketplace') }}" class="wallet-link">
                    <span class="wallet-link__title">{{ __('NFT Marketplace') }}</span>
                    <span class="wallet-link__sub">{{ __('Collect digital assets') }}</span>
                </a>
                <a href="{{ route('creator-coins.index') }}" class="wallet-link">
                    <span class="wallet-link__title">{{ __('Creator Coins') }}</span>
                    <span class="wallet-link__sub">{{ __('Support your creators') }}</span>
                </a>
                <a href="{{ route('nft.mintable') }}" class="wallet-link">
                    <span class="wallet-link__title">{{ __('Mint an NFT') }}</span>
                    <span class="wallet-link__sub">{{ __('From your uploads') }}</span>
                </a>
            </div>
        </div>
    </section>

    {{-- ── Tokens ────────────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel" id="wallet-panel-tokens" role="tabpanel" hidden>
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('My coins') }}</h2>
                <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-block__link">{{ __('See all') }}</a>
            </div>

            @forelse($tokenWallets as $wallet)
                @php
                    $crypto = $wallet->cryptocurrency;
                    $change = (float) ($crypto->price_change_24h ?? 0);
                    $isUp = $change >= 0;
                    // A 0.00% "change" is no movement, not a gain — don't paint it green.
                    $deltaClass = abs($change) < 0.005 ? 'flat' : ($isUp ? 'up' : 'down');
                    // Dollars only: the holding's value and how much that moved today.
                    $holdingValue = \App\Support\TokenMoney::toUsd((float) $wallet->balance, (float) $crypto->current_price);
                    $move = \App\Support\TokenMoney::changeUsd($holdingValue, $change);
                    $path = $sparkPath($crypto);
                @endphp
                <a href="{{ route('cryptocurrency.show', $crypto->id) }}" class="token-item">
                    <div class="token-item__row">
                        <div class="token-item__id">
                            @if($crypto->logo)
                                <img src="{{ asset('storage/' . $crypto->logo) }}" alt="{{ $crypto->name }}" class="token-item__logo">
                            @else
                                <span class="token-item__symbol-circle">{{ strtoupper(substr($crypto->name, 0, 1)) }}</span>
                            @endif
                            <div class="token-item__meta">
                                <span class="token-item__name">{{ $crypto->name }}</span>
                                <span class="token-item__price">
                                    <span class="token-item__delta token-item__delta--{{ $deltaClass }}">
                                        @if($deltaClass === 'flat')
                                            {{ __('No change today') }}
                                        @else
                                            {{ $deltaClass === 'up' ? __('Up') : __('Down') }} {{ \App\Support\TokenMoney::format(abs($move)) }} {{ __('today') }}
                                        @endif
                                    </span>
                                </span>
                            </div>
                        </div>
                        {{-- Only plotted from real recorded history; with none, show nothing rather
                             than a line that reads as a flat market. --}}
                        @if($path !== null)
                            <div class="token-item__spark">
                                <svg viewBox="0 0 120 44" preserveAspectRatio="none" aria-hidden="true">
                                    <path d="{{ $path }}" fill="none" stroke="{{ $isUp ? '#22e0a0' : '#ff3b5c' }}"
                                          stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <div class="token-item__foot">
                        <span class="token-item__amount">{{ __('Worth now') }}</span>
                        <span class="token-item__value">{{ \App\Support\TokenMoney::format($holdingValue) }}</span>
                    </div>
                </a>
            @empty
                <div class="wallet-empty">
                    <h4>{{ __("You don't own any coins yet") }}</h4>
                    <p>{{ __('Back a creator you love — start with as little as $1') }}</p>
                    <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Browse Marketplace') }}</a>
                </div>
            @endforelse
        </div>

        @if(session('send_success'))
            <p class="wallet-note wallet-note--boxed wallet-note--success" role="status">{{ session('send_success') }}</p>
        @endif

        {{-- Send to another member, in dollars. Units are worked out on the server
             (CoinSendController); nothing here ever shows or asks for one. --}}
        @if($tokenWallets->count())
            @php
                $sendable = $tokenWallets->filter(fn ($w) => $w->cryptocurrency->transferable)->values();
                $firstSend = old('coin_id') ? $sendable->firstWhere('cryptocurrency_id', (int) old('coin_id')) : null;
                $firstSend = $firstSend ?? $sendable->first();
            @endphp
            @if($firstSend)
                <div class="wallet-block" id="wallet-send">
                    <div class="wallet-block__head">
                        <h2 class="wallet-block__title">{{ __('Send to someone') }}</h2>
                    </div>
                    <form method="POST" class="wallet-form" id="coinSendForm"
                          action="{{ route('wallet.coins.send', $firstSend->cryptocurrency_id) }}">
                        @csrf
                        @if($sendable->count() > 1)
                            <label class="wallet-field">
                                <span>{{ __('Which coin') }}</span>
                                <select name="coin_id" id="coinSendCoin">
                                    @foreach($sendable as $w)
                                        @php $worth = \App\Support\TokenMoney::toUsd((float) $w->balance, (float) $w->cryptocurrency->current_price); @endphp
                                        <option value="{{ $w->cryptocurrency_id }}"
                                                data-action="{{ route('wallet.coins.send', $w->cryptocurrency_id) }}"
                                                data-worth="{{ \App\Support\TokenMoney::format($worth) }}"
                                                @selected($w->is($firstSend))>
                                            {{ $w->cryptocurrency->name }} — {{ \App\Support\TokenMoney::format($worth) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                        @else
                            <input type="hidden" name="coin_id" value="{{ $firstSend->cryptocurrency_id }}">
                        @endif
                        <label class="wallet-field">
                            <span>{{ __('To') }}</span>
                            <input type="text" name="recipient" value="{{ old('recipient') }}" placeholder="@username"
                                   autocomplete="off" autocapitalize="none" spellcheck="false" required>
                        </label>
                        <label class="wallet-field">
                            <span>{{ __('Amount') }}</span>
                            <span class="wallet-money-input">
                                <span class="wallet-money-input__sign" aria-hidden="true">$</span>
                                <input type="number" name="amount_usd" id="coinSendAmount" inputmode="decimal"
                                       step="0.01" min="1" placeholder="0.00" value="{{ old('amount_usd') }}">
                            </span>
                        </label>
                        <p class="wallet-note">
                            {{ __('You have') }} <strong id="coinSendWorth">{{ \App\Support\TokenMoney::format(\App\Support\TokenMoney::toUsd((float) $firstSend->balance, (float) $firstSend->cryptocurrency->current_price)) }}</strong>
                            {{ __('to send. They get the same dollar value in their wallet, instantly and free.') }}
                        </p>
                        @if(session('send_error'))
                            <p class="wallet-note wallet-note--error" role="alert">{{ session('send_error') }}</p>
                        @endif
                        @foreach(['recipient', 'amount_usd'] as $field)
                            @error($field)<p class="wallet-note wallet-note--error" role="alert">{{ $message }}</p>@enderror
                        @endforeach
                        <div class="wallet-send__actions">
                            <button type="submit" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Send') }}</button>
                            <button type="submit" name="send_all" value="1" class="wallet-btn wallet-btn--ghost wallet-btn--inline" formnovalidate>{{ __('Send all') }}</button>
                        </div>
                    </form>
                </div>
            @endif
        @endif
    </section>

    {{-- ── NFTs ──────────────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel" id="wallet-panel-nfts" role="tabpanel" hidden>
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('My NFTs') }}</h2>
                <a href="{{ route('nft.my-listings') }}" class="wallet-block__link">{{ __('My listings') }}</a>
            </div>

            @if($nfts->count())
                {{-- Same tile as the marketplace: the piece, its price, who made it. --}}
                <div class="nx-grid">
                    @foreach($nfts as $nft)
                        @include('nft.partials.card', [
                            'nft' => $nft,
                            'note' => $nft->isPendingMint() ? __('Being created') : null,
                        ])
                    @endforeach
                </div>
            @else
                <div class="wallet-empty">
                    <h4>{{ __('No NFTs yet') }}</h4>
                    <p>{{ __('Buy one from a creator, or turn something you posted into one you can sell') }}</p>
                    <a href="{{ route('nft.marketplace') }}" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Explore NFTs') }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- ── Creator coins ─────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel" id="wallet-panel-coins" role="tabpanel" hidden>
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('Creator Coins') }}</h2>
                <a href="{{ route('creator-coins.index') }}" class="wallet-block__link">{{ __('Browse') }}</a>
            </div>

            @forelse($coinBalances as $balance)
                @php $coin = $balance->coin; @endphp
                <a href="{{ route('creator-coins.show', $coin->id) }}" class="token-item">
                    <div class="token-item__row">
                        <div class="token-item__id">
                            @if($coin->logo)
                                <img src="{{ asset('storage/' . $coin->logo) }}" alt="{{ $coin->name }}" class="token-item__logo">
                            @else
                                <span class="token-item__symbol-circle">{{ strtoupper(substr($coin->name, 0, 1)) }}</span>
                            @endif
                            <div class="token-item__meta">
                                <span class="token-item__name">{{ $coin->name }}</span>
                                <span class="token-item__price">{{ __('Unlocks perks') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="token-item__foot">
                        <span class="token-item__amount">{{ __('In perks') }}</span>
                        <span class="token-item__value">{{ \App\Support\TokenMoney::format((float) $balance->balance * (float) $coin->price_per_point) }}</span>
                    </div>
                </a>
            @empty
                <div class="wallet-empty">
                    <h4>{{ __('No creator coins yet') }}</h4>
                    <p>{{ __('Buy a creator coin to unlock perks and support creators directly') }}</p>
                    <a href="{{ route('creator-coins.index') }}" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Browse Creator Coins') }}</a>
                </div>
            @endforelse

            @if($coinBalances->count())
                <p class="wallet-note wallet-note--boxed">
                    {{ __('Creator points unlock perks and access. They are not redeemable for cash.') }}
                    {{ __('Indicative value:') }} <strong>${{ number_format($coinValue, 2) }}</strong>
                </p>
            @endif
        </div>
    </section>

    {{-- ── Activity ──────────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel" id="wallet-panel-activity" role="tabpanel" hidden>
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('Recent Activity') }}</h2>
            </div>

            @forelse($activity as $item)
                <div class="txn-item">
                    <span class="txn-item__icon txn-item__icon--{{ $item['kind'] }}" aria-hidden="true">
                        @if($item['kind'] === 'nft')
                            <svg viewBox="0 0 24 24" fill="none"><rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/><path d="m21 15-5-5L5 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @elseif($item['kind'] === 'coin')
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.75"/><path d="M12 7v10M9.5 9.5h5M9.5 14.5h5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none"><path d="M7 7h10l-3-3M17 17H7l3 3" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <div class="txn-item__meta">
                        <span class="txn-item__title">{{ $item['label'] }}</span>
                        <span class="txn-item__date">{{ $item['at'] ? $item['at']->diffForHumans() : '' }}</span>
                    </div>
                    <div class="txn-item__right">
                        @if($item['amount'] !== null)
                            <span class="txn-item__amount txn-item__amount--{{ $item['in'] ? 'in' : 'out' }}">
                                {{ $item['in'] ? '+' : '−' }}{{ $item['amount'] }}
                            </span>
                        @else
                            <span class="txn-item__status">{{ $item['in'] ? __('in') : __('out') }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="wallet-empty">
                    <h4>{{ __('No activity yet') }}</h4>
                    <p>{{ __('Your token, coin and NFT activity will appear here') }}</p>
                </div>
            @endforelse
        </div>
    </section>

</div>
</div>

@if($address)
<script src="{{ asset('libs/easyqrcodejs/dist/easy.qrcode.min.js') }}"></script>
@endif
<script>
(function () {
    // --- Balance visibility ---------------------------------------------------------
    var toggle = document.getElementById('walletBalanceToggle');
    var balance = document.querySelector('.wallet-hero__balance');
    if (toggle && balance) {
        var open = toggle.querySelector('.wallet-hero__eye-open');
        var closed = toggle.querySelector('.wallet-hero__eye-closed');
        var hidden = false;
        toggle.addEventListener('click', function () {
            hidden = !hidden;
            balance.textContent = hidden ? '••••••' : balance.getAttribute('data-balance');
            if (open) open.hidden = hidden;
            if (closed) closed.hidden = !hidden;
            // The breakdown carries the same figures, so it hides with the headline.
            document.querySelector('.wallet-page').classList.toggle('is-balance-hidden', hidden);
            toggle.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            toggle.setAttribute('aria-label', hidden ? @json(__('Show balance')) : @json(__('Hide balance')));
        });
    }

    // --- Tabs -----------------------------------------------------------------------
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.wallet-tab'));
    function activate(name, push) {
        tabs.forEach(function (t) {
            var on = t.dataset.panel === name;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        document.querySelectorAll('.wallet-panel').forEach(function (p) {
            var on = p.id === 'wallet-panel-' + name;
            p.hidden = !on;
            p.classList.toggle('is-active', on);
        });
        if (push && window.history && history.replaceState) {
            history.replaceState(null, '', '#' + name);
        }
    }
    tabs.forEach(function (t) {
        t.addEventListener('click', function () { activate(t.dataset.panel, true); });
    });
    // Breakdown tiles are shortcuts into their tab.
    document.querySelectorAll('[data-open-panel]').forEach(function (b) {
        b.addEventListener('click', function () {
            activate(b.dataset.openPanel, true);
            var bar = document.querySelector('.wallet-tabs');
            if (bar && bar.scrollIntoView) bar.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
    // Deep-link support: /wallet#nfts opens straight to the NFT tab.
    var hash = (window.location.hash || '').replace('#', '');
    if (hash && document.getElementById('wallet-panel-' + hash)) {
        activate(hash, false);
    }

    @if($address)
    // --- Receive: QR + copy ---------------------------------------------------------
    var address = @json($address);
    try {
        if (window.QRCode) {
            new QRCode(document.getElementById('wallet-qr'), {
                text: address, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M
            });
        }
    } catch (e) { /* QR is optional */ }

    var copyBtn = document.getElementById('copy-address-btn');
    if (copyBtn) copyBtn.addEventListener('click', function () {
        var field = document.getElementById('wallet-address-field');
        field.select();
        if (navigator.clipboard) navigator.clipboard.writeText(address);
        else document.execCommand('copy');
        var original = copyBtn.textContent;
        copyBtn.textContent = @json(__('Copied'));
        setTimeout(function () { copyBtn.textContent = original; }, 1500);
    });

    // --- Send: signed by the user's own wallet, never by the platform ----------------
    var sendBtn = document.getElementById('send-btn');
    var status = document.getElementById('send-status');
    function say(msg, kind) {
        status.textContent = msg;
        status.className = 'wallet-note wallet-note--' + kind;
    }
    if (sendBtn) sendBtn.addEventListener('click', async function () {
        var to = document.getElementById('send-to').value.trim();
        var amount = parseFloat(document.getElementById('send-amount').value);
        if (!/^0x[a-fA-F0-9]{40}$/.test(to)) { return say(@json(__('Enter a valid 0x recipient address.')), 'error'); }
        if (!(amount > 0)) { return say(@json(__('Enter an amount greater than zero.')), 'error'); }
        if (typeof window.ethereum === 'undefined') {
            return say(@json(__('No browser wallet detected. Connect a wallet to send.')), 'error');
        }
        try {
            say(@json(__('Confirm the transaction in your wallet…')), 'muted');
            var accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            var valueWei = '0x' + Math.floor(amount * 1e18).toString(16);
            var hash = await window.ethereum.request({
                method: 'eth_sendTransaction',
                params: [{ from: accounts[0], to: to, value: valueWei }]
            });
            say(@json(__('Sent. Transaction:')) + ' ' + hash, 'success');
        } catch (e) {
            say(@json(__('Send failed:')) + ' ' + (e.message || e), 'error');
        }
    });
    @endif

    // --- Send to someone: dollars only ----------------------------------------------
    var sendForm = document.getElementById('coinSendForm');
    if (sendForm) {
        var coinSelect = document.getElementById('coinSendCoin');
        var worthEl = document.getElementById('coinSendWorth');
        if (coinSelect) coinSelect.addEventListener('change', function () {
            var opt = coinSelect.options[coinSelect.selectedIndex];
            sendForm.action = opt.getAttribute('data-action');
            worthEl.textContent = opt.getAttribute('data-worth');
        });
        // Sends can't be undone, so read it back in plain words first.
        sendForm.addEventListener('submit', function (e) {
            var all = e.submitter && e.submitter.name === 'send_all';
            var who = sendForm.recipient.value.trim().replace(/^@/, '');
            if (!who) { e.preventDefault(); sendForm.recipient.focus(); return; }
            var amount = all ? worthEl.textContent : '$' + (parseFloat(sendForm.amount_usd.value) || 0).toFixed(2);
            if (!all && !(parseFloat(sendForm.amount_usd.value) >= 1)) { e.preventDefault(); sendForm.amount_usd.focus(); return; }
            if (!window.confirm(@json(__('Send')) + ' ' + amount + ' ' + @json(__('to')) + ' @' + who + '?')) e.preventDefault();
        });
    }
})();
</script>
@endsection
