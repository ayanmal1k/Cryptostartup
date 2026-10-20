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
    <link rel="stylesheet" href="{{ asset('css/pages/wallet.css') }}?v=20260804a">
@endsection

@section('content')
<div class="wallet-page {{ $isDarkTheme ? 'wallet-page--dark' : 'wallet-page--light' }}">
<div class="wallet-container">

    {{-- ── Balance hero ──────────────────────────────────────────────────────────── --}}
    <section class="wallet-hero">
        <div class="wallet-hero__top">
            <span class="wallet-hero__label">{{ __('Total Balance') }}</span>
            <button type="button" class="wallet-hero__eye" id="walletBalanceToggle" aria-label="{{ __('Toggle balance visibility') }}">
                <svg class="wallet-hero__eye-open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                <svg class="wallet-hero__eye-closed" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                    <path d="M3 3l18 18M10.6 10.6a3 3 0 004.24 4.24M9.9 5.2A9.7 9.7 0 0112 5c6.5 0 10 7 10 7a17.6 17.6 0 01-3.4 4.3M6.1 6.1A17.7 17.7 0 002 12s3.5 7 10 7c1 0 1.96-.14 2.86-.4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="wallet-hero__balance" data-balance="${{ number_format($totalBalance, 2) }}">${{ number_format($totalBalance, 2) }}</div>
        <p class="wallet-hero__hint">{{ __('Platform credits + token holdings') }}</p>

        {{-- Deposit/withdraw route to the REAL payment flow (Stripe/PayPal via my.settings),
             not CryptocurrencyController's stub which records a "completed" deposit without
             ever taking payment. --}}
        <div class="wallet-hero__actions">
            <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'deposit']) }}" class="wallet-btn wallet-btn--primary">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Deposit') }}
            </a>
            <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw']) }}" class="wallet-btn wallet-btn--ghost">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Withdraw') }}
            </a>
        </div>

        {{-- Every form of value the user holds, in one strip --}}
        <ul class="wallet-hero__meta">
            <li>
                <span class="wallet-hero__meta-label">{{ __('Credits') }}</span>
                <span class="wallet-hero__meta-value">${{ number_format($credits, 2) }}</span>
            </li>
            <li>
                <span class="wallet-hero__meta-label">{{ __('Tokens') }}</span>
                <span class="wallet-hero__meta-value">${{ number_format($tokenValue, 2) }}</span>
            </li>
            <li>
                <span class="wallet-hero__meta-label">{{ __('Coins') }}</span>
                <span class="wallet-hero__meta-value">{{ $coinBalances->count() }}</span>
            </li>
            <li>
                <span class="wallet-hero__meta-label">{{ __('NFTs') }}</span>
                <span class="wallet-hero__meta-value">{{ $nftCount }}</span>
            </li>
        </ul>
    </section>

    {{-- ── Tabs ──────────────────────────────────────────────────────────────────── --}}
    <nav class="wallet-tabs" role="tablist" aria-label="{{ __('Wallet sections') }}">
        <button class="wallet-tab is-active" role="tab" aria-selected="true" data-panel="overview">{{ __('Overview') }}</button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="tokens">{{ __('Tokens') }} <span class="wallet-tab__count">{{ $tokenWallets->count() }}</span></button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="nfts">{{ __('NFTs') }} <span class="wallet-tab__count">{{ $nftCount }}</span></button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="coins">{{ __('Coins') }} <span class="wallet-tab__count">{{ $coinBalances->count() }}</span></button>
        <button class="wallet-tab" role="tab" aria-selected="false" data-panel="activity">{{ __('Activity') }}</button>
    </nav>

    {{-- ── Overview ──────────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel is-active" id="wallet-panel-overview" role="tabpanel">
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
                        <p class="wallet-note">{{ __('Share this address to receive tokens and NFTs.') }}</p>
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

        {{-- Quick links keep the rest of the ecosystem one tap away --}}
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('Explore') }}</h2>
            </div>
            <div class="wallet-links">
                <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-link">
                    <span class="wallet-link__title">{{ __('Token Marketplace') }}</span>
                    <span class="wallet-link__sub">{{ __('Discover creator tokens') }}</span>
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
                <h2 class="wallet-block__title">{{ __('My Tokens') }}</h2>
                <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-block__link">{{ __('See all') }}</a>
            </div>

            @forelse($tokenWallets as $wallet)
                @php
                    $crypto = $wallet->cryptocurrency;
                    $change = (float) ($crypto->price_change_24h ?? 0);
                    $isUp = $change >= 0;
                    $holdingValue = (float) $wallet->balance * (float) $crypto->current_price;
                    $path = $sparkPath($crypto);
                @endphp
                <a href="{{ route('cryptocurrency.show', $crypto->id) }}" class="token-item">
                    <div class="token-item__row">
                        <div class="token-item__id">
                            @if($crypto->logo)
                                <img src="{{ asset('storage/' . $crypto->logo) }}" alt="{{ $crypto->name }}" class="token-item__logo">
                            @else
                                <span class="token-item__symbol-circle">{{ strtoupper(substr($crypto->symbol ?? $crypto->name, 0, 1)) }}</span>
                            @endif
                            <div class="token-item__meta">
                                <span class="token-item__name">{{ $crypto->name }}</span>
                                <span class="token-item__price">
                                    ${{ number_format($crypto->current_price, $crypto->current_price < 1 ? 4 : 2) }}
                                    <span class="token-item__delta token-item__delta--{{ $isUp ? 'up' : 'down' }}">
                                        {{ $isUp ? '+' : '' }}{{ number_format($change, 2) }}%
                                    </span>
                                </span>
                            </div>
                        </div>
                        <div class="token-item__spark">
                            <svg viewBox="0 0 120 44" preserveAspectRatio="none" aria-hidden="true">
                                {{-- Real history when we have it; an honest flat line when we don't. --}}
                                <path d="{{ $path ?? 'M0 22 L120 22' }}"
                                      fill="none"
                                      stroke="{{ $path === null ? 'currentColor' : ($isUp ? '#22c55e' : '#f43f5e') }}"
                                      stroke-width="2"
                                      stroke-linecap="round"
                                      stroke-linejoin="round"
                                      {{ $path === null ? 'opacity=0.25' : '' }}/>
                            </svg>
                        </div>
                    </div>
                    <div class="token-item__foot">
                        <span class="token-item__amount">{{ rtrim(rtrim(number_format($wallet->balance, 4), '0'), '.') }} {{ $crypto->symbol }}</span>
                        <span class="token-item__value">${{ number_format($holdingValue, 2) }}</span>
                    </div>
                </a>
            @empty
                <div class="wallet-empty">
                    <h4>{{ __("You don't own any tokens yet") }}</h4>
                    <p>{{ __('Start by buying tokens from creators you support') }}</p>
                    <a href="{{ route('cryptocurrency.marketplace') }}" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Browse Marketplace') }}</a>
                </div>
            @endforelse
        </div>
    </section>

    {{-- ── NFTs ──────────────────────────────────────────────────────────────────── --}}
    <section class="wallet-panel" id="wallet-panel-nfts" role="tabpanel" hidden>
        <div class="wallet-block">
            <div class="wallet-block__head">
                <h2 class="wallet-block__title">{{ __('My NFTs') }}</h2>
                <a href="{{ route('nft.my-listings') }}" class="wallet-block__link">{{ __('My listings') }}</a>
            </div>

            @if($nfts->count())
                <div class="nft-grid">
                    @foreach($nfts as $nft)
                        <a href="{{ route('nft.show', $nft->id) }}" class="nft-card">
                            <div class="nft-card__media">
                                @if($nft->image_url)
                                    <img src="{{ $nft->image_url }}" alt="{{ $nft->name }}" loading="lazy">
                                @else
                                    <span class="nft-card__placeholder" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/>
                                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                                            <path d="m21 15-5-5L5 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                @endif
                                <span class="nft-card__status nft-card__status--{{ $nft->status }}">{{ ucfirst(str_replace('_', ' ', $nft->status)) }}</span>
                            </div>
                            <div class="nft-card__body">
                                <span class="nft-card__name">{{ $nft->name }}</span>
                                <span class="nft-card__sub">
                                    @if($nft->token_id !== null)
                                        #{{ $nft->token_id }}
                                    @else
                                        {{ __('Pending') }}
                                    @endif
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="wallet-empty">
                    <h4>{{ __('No NFTs yet') }}</h4>
                    <p>{{ __('Mint an NFT from a video or photo you have uploaded') }}</p>
                    <a href="{{ route('nft.mintable') }}" class="wallet-btn wallet-btn--primary wallet-btn--inline">{{ __('Mint from my uploads') }}</a>
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
                                <span class="token-item__symbol-circle">{{ strtoupper(substr($coin->symbol ?? $coin->name, 0, 1)) }}</span>
                            @endif
                            <div class="token-item__meta">
                                <span class="token-item__name">{{ $coin->name }}</span>
                                <span class="token-item__price">{{ $coin->symbol }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="token-item__foot">
                        <span class="token-item__amount">{{ rtrim(rtrim(number_format($balance->balance, 4), '0'), '.') }} {{ __('points') }}</span>
                        <span class="token-item__value">${{ number_format((float) $balance->balance * (float) $coin->price_per_point, 2) }}</span>
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
})();
</script>
@endsection
