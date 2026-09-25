@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    // --- Operator card: the same level, XP, streak and sparks the Rewards hub tracks.
    $perLevel = \App\Services\GamificationService::XP_PER_LEVEL;
    $level = (int) ($user->level ?? 1);
    $xp = (int) ($user->xp ?? 0);
    $intoLevel = $perLevel > 0 ? $xp % $perLevel : 0;
    $levelPct = $perLevel > 0 ? round($intoLevel / $perLevel * 100) : 0;
    // Cosmetic rank names, by level band. Pure flavour; nothing is gated on them.
    $rankTitle = collect([35 => __('Legend'), 20 => __('Whale'), 10 => __('Collector'), 5 => __('Hustler'), 0 => __('Rookie')])
        ->first(fn ($title, $min) => $level >= $min);
    $frameClass = \App\Services\PerkShopService::frameClass($user);

    // --- Loadout: how the value splits across the three kinds of money.
    $loadoutTotal = $credits + $tokenValue + $coinValue;
    $pct = fn ($v) => $loadoutTotal > 0 ? round($v / $loadoutTotal * 100, 1) : 0;

    // --- Quests: first steps, ticked off from what the user actually holds.
    $quests = [
        ['done' => $credits > 0 || $tokenWallets->count() > 0, 'text' => __('Add funds to your wallet'), 'href' => route('my.settings', ['type' => 'wallet', 'active' => 'deposit'])],
        ['done' => $tokenWallets->count() > 0, 'text' => __('Own your first coin'), 'href' => route('cryptocurrency.marketplace')],
        ['done' => $nftCount > 0, 'text' => __('Collect an NFT'), 'href' => route('nft.marketplace')],
        ['done' => $coinBalances->count() > 0, 'text' => __('Back a creator'), 'href' => route('creator-coins.index')],
    ];
    $questsDone = collect($quests)->where('done', true)->count();

    // --- Holdings, biggest first, for the overview preview and the coins tab.
    $holdings = $tokenWallets->map(function ($w) {
        $crypto = $w->cryptocurrency;
        $change = (float) ($crypto->price_change_24h ?? 0);
        $value = \App\Support\TokenMoney::toUsd((float) $w->balance, (float) $crypto->current_price);
        $prices = \App\Support\Sparkline::prices($crypto);

        return (object) [
            'crypto' => $crypto,
            'value' => $value,
            'move' => \App\Support\TokenMoney::changeUsd($value, $change),
            // A 0.00% "change" is no movement, not a gain — don't paint it green.
            'dir' => abs($change) < 0.005 ? 'flat' : ($change > 0 ? 'up' : 'down'),
            'spark' => \App\Support\Sparkline::line($prices, 64, 26),
        ];
    })->sortByDesc('value')->values();

    // --- Activity, grouped into days.
    $activityDays = $activity->groupBy(function ($item) {
        if (!$item['at']) {
            return __('Earlier');
        }
        if ($item['at']->isToday()) {
            return __('Today');
        }

        return $item['at']->isYesterday() ? __('Yesterday') : $item['at']->format('M j');
    });
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Chakra+Petch:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    {{-- wallet.css now only dresses the outside-wallet disclosure; the page itself is loot-hud. --}}
    <link rel="stylesheet" href="{{ asset('css/pages/wallet.css') }}?v=20260921a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
    <link rel="stylesheet" href="{{ asset('css/frames.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/loot-hud.css') }}?v=20260924a">
@endsection

@section('content')
<div class="lh wallet-page {{ $isDarkTheme ? 'lh--dark wallet-page--dark' : 'lh--light wallet-page--light' }}">
<div class="lh-wrap">

    @foreach(['success', 'error'] as $flash)
        @if(session($flash))
            <div class="lh-flash lh-flash--{{ $flash }}" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach

    {{-- ── Operator card ──────────────────────────────────────────────────────────── --}}
    <section class="lh-panel lh-brk lh-sheen wh-hero lh-in" id="walletHero">
        <span class="lh-sheen__bar" aria-hidden="true"></span>

        <div class="wh-op">
            <a href="{{ route('profile', ['username' => $user->username]) }}" class="wh-op__avatar {{ $frameClass }}">
                <img src="{{ $user->avatar }}" alt="">
                <span class="wh-op__lvl" aria-label="{{ __('Level :n', ['n' => $level]) }}">{{ $level }}</span>
            </a>
            <div class="wh-op__id">
                <span class="wh-op__name">{{ '@' . $user->username }}</span>
                <span class="wh-op__rank">{{ __('Rank') }} · {{ $rankTitle }}</span>
            </div>
            <button type="button" class="wh-eye" id="walletBalanceToggle" aria-pressed="false" aria-label="{{ __('Hide balance') }}">
                <svg class="wh-eye__open" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.75"/>
                </svg>
                <svg class="wh-eye__closed" viewBox="0 0 24 24" fill="none" aria-hidden="true" hidden>
                    <path d="M3 3l18 18M10.6 10.6a3 3 0 004.24 4.24M9.9 5.2A9.7 9.7 0 0112 5c6.5 0 10 7 10 7a17.6 17.6 0 01-3.4 4.3M6.1 6.1A17.7 17.7 0 002 12s3.5 7 10 7c1 0 1.96-.14 2.86-.4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>

        <div class="wh-worth">
            <span class="lh-cap">{{ __('Net worth') }}</span>
            <div class="wh-worth__row">
                <span class="wh-worth__value" id="walletBalance" data-balance="${{ number_format($totalBalance, 2) }}"
                      data-count-to="{{ number_format($totalBalance, 2, '.', '') }}">${{ number_format($totalBalance, 2) }}</span>
            </div>
            <p class="wh-worth__hint">{{ __('Spendable balance plus what your coins are worth today') }}</p>
        </div>

        {{-- Loadout: one segmented bar showing where the value sits. --}}
        <div class="wh-loadout">
            <div class="wh-loadout__top">
                <span class="lh-cap">{{ __('Loadout') }}</span>
                @if($loadoutTotal <= 0)
                    <span class="lh-cap">{{ __('Empty') }}</span>
                @endif
            </div>
            <div class="lh-meter lh-meter--ticks" aria-hidden="true">
                <div class="lh-split">
                    <span class="is-credits" style="width: {{ $pct($credits) }}%"></span>
                    <span class="is-coins" style="width: {{ $pct($tokenValue) }}%"></span>
                    <span class="is-creator" style="width: {{ $pct($coinValue) }}%"></span>
                </div>
            </div>
            <div class="lh-legend">
                <span class="lh-legend__item"><span class="lh-legend__dot" style="background: var(--lh-gold)"></span>{{ __('Credits') }} <b class="wh-legend-val">${{ number_format($credits, 2) }}</b></span>
                <span class="lh-legend__item"><span class="lh-legend__dot" style="background: var(--lh-cyan)"></span>{{ __('Coins') }} <b class="wh-legend-val">${{ number_format($tokenValue, 2) }}</b></span>
                <span class="lh-legend__item"><span class="lh-legend__dot" style="background: var(--lh-violet)"></span>{{ __('Perks') }} <b class="wh-legend-val">${{ number_format($coinValue, 2) }}</b></span>
            </div>
        </div>

        <div class="wh-xp">
            <div class="wh-xp__top">
                <span class="lh-cap">{{ __('LVL :a → :b', ['a' => $level, 'b' => $level + 1]) }}</span>
                <span class="wh-xp__nums">{{ number_format($intoLevel) }} / {{ number_format($perLevel) }} XP</span>
            </div>
            <div class="lh-meter lh-meter--ticks" role="progressbar" aria-valuenow="{{ $levelPct }}" aria-valuemin="0" aria-valuemax="100"
                 aria-label="{{ __('Progress to the next level') }}">
                <span class="lh-meter__fill" style="width: {{ $levelPct }}%"></span>
            </div>
            <div class="wh-xp__chips">
                <a href="{{ route('engagement.hub') }}" class="lh-chip">🔥 <b>{{ number_format((int) ($user->streak_count ?? 0)) }}</b> {{ __('day streak') }}</a>
                <a href="{{ route('engagement.hub') }}" class="lh-chip">✨ <b>{{ number_format((int) ($user->sparks ?? 0)) }}</b> {{ __('sparks') }}</a>
            </div>
        </div>

        {{-- Hotbar. Deposit/withdraw route to the REAL payment flow (Stripe/PayPal via
             my.settings), not CryptocurrencyController's stub which records a "completed"
             deposit without ever taking payment. --}}
        <nav class="wh-hotbar" aria-label="{{ __('Wallet actions') }}">
            <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'deposit']) }}" class="wh-hot wh-hot--money">
                <span class="wh-hot__key" aria-hidden="true">1</span>
                <span class="wh-hot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2.25" stroke-linecap="round"/></svg></span>
                {{ __('Add') }}
            </a>
            <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw']) }}" class="wh-hot">
                <span class="wh-hot__key" aria-hidden="true">2</span>
                <span class="wh-hot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16v10H4zM12 10v4M4 11h2M18 13h2" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                {{ __('Cash out') }}
            </a>
            <button type="button" class="wh-hot" data-open-panel="tokens" data-scroll-to="wallet-send">
                <span class="wh-hot__key" aria-hidden="true">3</span>
                <span class="wh-hot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4 18-7z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                {{ __('Send') }}
            </button>
            <a href="{{ route('cryptocurrency.marketplace') }}" class="wh-hot">
                <span class="wh-hot__key" aria-hidden="true">4</span>
                <span class="wh-hot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg></span>
                {{ __('Buy coins') }}
            </a>
        </nav>
    </section>

    {{-- ── Inventory: every form of value; each slot opens its tab ─────────────────── --}}
    <section class="lh-in" style="--i: 1">
        <div class="lh-head">
            <h2 class="lh-head__title">{{ __('Inventory') }}</h2>
            <span class="lh-head__rule" aria-hidden="true"></span>
            <span class="lh-head__code">4 {{ __('slots') }}</span>
        </div>
        <ul class="wh-slots" aria-label="{{ __('Balance breakdown') }}">
            <li>
                <button type="button" class="lh-slot" data-open-panel="overview">
                    <span class="lh-slot__top">
                        <span class="lh-slot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><ellipse cx="12" cy="6" rx="7" ry="3" stroke="currentColor" stroke-width="1.75"/><path d="M5 6v6c0 1.66 3.13 3 7 3s7-1.34 7-3V6M5 12v6c0 1.66 3.13 3 7 3s7-1.34 7-3v-6" stroke="currentColor" stroke-width="1.75"/></svg></span>
                        <span class="lh-slot__idx">01</span>
                    </span>
                    <span class="lh-cap">{{ __('Credits') }}</span>
                    <span class="lh-slot__value is-money" data-count-to="{{ number_format($credits, 2, '.', '') }}">${{ number_format($credits, 2) }}</span>
                    <span class="lh-slot__sub">{{ __('Ready to spend') }}</span>
                </button>
            </li>
            <li>
                <button type="button" class="lh-slot lh-slot--cyan" data-open-panel="tokens">
                    <span class="lh-slot__top">
                        <span class="lh-slot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M12 7v10" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg></span>
                        <span class="lh-slot__idx">02</span>
                    </span>
                    <span class="lh-cap">{{ __('Coins') }}</span>
                    <span class="lh-slot__value is-money" data-count-to="{{ number_format($tokenValue, 2, '.', '') }}">${{ number_format($tokenValue, 2) }}</span>
                    <span class="lh-slot__sub">{{ trans_choice('{0} None held|{1} :count coin|[2,*] :count coins', $tokenWallets->count(), ['count' => $tokenWallets->count()]) }}</span>
                </button>
            </li>
            <li>
                <button type="button" class="lh-slot lh-slot--violet" data-open-panel="coins">
                    <span class="lh-slot__top">
                        <span class="lh-slot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg></span>
                        <span class="lh-slot__idx">03</span>
                    </span>
                    <span class="lh-cap">{{ __('Creator coins') }}</span>
                    <span class="lh-slot__value">{{ number_format($coinBalances->count()) }}</span>
                    <span class="lh-slot__sub">{{ $coinValue > 0 ? '≈ $' . number_format($coinValue, 2) . ' ' . __('in perks') : __('Back a creator') }}</span>
                </button>
            </li>
            <li>
                <button type="button" class="lh-slot lh-slot--brand" data-open-panel="nfts">
                    <span class="lh-slot__top">
                        <span class="lh-slot__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12l3 6-9 12L3 9z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M3 9h18M9 3l3 18 3-18" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/></svg></span>
                        <span class="lh-slot__idx">04</span>
                    </span>
                    <span class="lh-cap">{{ __('NFTs') }}</span>
                    <span class="lh-slot__value">{{ number_format($nftCount) }}</span>
                    <span class="lh-slot__sub">{{ $nftCount ? __('In your collection') : __('Mint your first') }}</span>
                </button>
            </li>
        </ul>
    </section>

    {{-- ── Tabs ──────────────────────────────────────────────────────────────────── --}}
    <nav class="lh-tabs lh-in" style="--i: 2" role="tablist" aria-label="{{ __('Wallet sections') }}" id="walletTabs">
        <button class="lh-tab is-active" role="tab" aria-selected="true" data-panel="overview">{{ __('Overview') }}</button>
        <button class="lh-tab" role="tab" aria-selected="false" data-panel="tokens">{{ __('Coins') }} <span class="lh-tab__count">{{ $tokenWallets->count() }}</span></button>
        <button class="lh-tab" role="tab" aria-selected="false" data-panel="nfts">{{ __('NFTs') }} <span class="lh-tab__count">{{ $nftCount }}</span></button>
        <button class="lh-tab" role="tab" aria-selected="false" data-panel="coins">{{ __('Creator') }} <span class="lh-tab__count">{{ $coinBalances->count() }}</span></button>
        <button class="lh-tab" role="tab" aria-selected="false" data-panel="activity">{{ __('Activity') }}</button>
    </nav>

    {{-- ── Overview ──────────────────────────────────────────────────────────────── --}}
    <section class="lh-tabpanel is-active" id="wallet-panel-overview" role="tabpanel">

        {{-- Quests --}}
        <div class="lh-panel lh-brk">
            <div class="wh-quests__top">
                <div>
                    <span class="lh-cap">{{ __('Wallet quests') }}</span>
                    <p class="wh-quests__title">{{ $questsDone === count($quests) ? __('All done — nice.') : __('Finish setting up your wallet') }}</p>
                </div>
                <span class="lh-chip lh-chip--gold"><b class="lh-gold">{{ $questsDone }}</b>/{{ count($quests) }}</span>
            </div>
            <div class="lh-meter lh-meter--ticks lh-meter--gold" aria-hidden="true" style="margin-bottom: 6px">
                <span class="lh-meter__fill" style="width: {{ round($questsDone / count($quests) * 100) }}%"></span>
            </div>
            @foreach($quests as $quest)
                <a href="{{ $quest['href'] }}" class="wh-quest {{ $quest['done'] ? 'is-done' : '' }}">
                    <span class="wh-quest__box" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none"><path d="m5 12.5 4.5 4.5L19 7.5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="wh-quest__text">{{ $quest['text'] }}</span>
                    @unless($quest['done'])
                        <span class="wh-quest__go">{{ __('Go') }} →</span>
                    @endunless
                    <span class="lh-sr">{{ $quest['done'] ? __('Done') : __('Not done') }}</span>
                </a>
            @endforeach
        </div>

        {{-- Top holdings --}}
        @if($holdings->count())
            <div>
                <div class="lh-head">
                    <h2 class="lh-head__title">{{ __('Top holdings') }}</h2>
                    <span class="lh-head__rule" aria-hidden="true"></span>
                    <button type="button" class="lh-head__link" data-open-panel="tokens" style="background:none;border:0;padding:0;cursor:pointer">{{ __('All') }}</button>
                </div>
                <div class="lh-list">
                    @foreach($holdings->take(3) as $h)
                        @include('wallet.partials.holding-row', ['h' => $h])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Latest activity --}}
        @if($activity->count())
            <div>
                <div class="lh-head">
                    <h2 class="lh-head__title">{{ __('Latest') }}</h2>
                    <span class="lh-head__rule" aria-hidden="true"></span>
                    <button type="button" class="lh-head__link" data-open-panel="activity" style="background:none;border:0;padding:0;cursor:pointer">{{ __('Log') }}</button>
                </div>
                <div class="lh-list">
                    @foreach($activity->take(3) as $item)
                        @include('wallet.partials.activity-row', ['item' => $item])
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Explore --}}
        <div>
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('Explore') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
            </div>
            <div class="wh-explore">
                @foreach([
                    ['route' => route('cryptocurrency.marketplace'), 'title' => __('Coins'), 'sub' => __('Buy from $1'),
                     'icon' => '<path d="M12 2l8.66 5v10L12 22l-8.66-5V7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M12 7v10" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>'],
                    ['route' => route('nft.marketplace'), 'title' => __('NFT market'), 'sub' => __('Collect one-offs'),
                     'icon' => '<path d="M6 3h12l3 6-9 12L3 9z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M3 9h18" stroke="currentColor" stroke-width="1.5"/>'],
                    ['route' => route('creator-coins.index'), 'title' => __('Creator coins'), 'sub' => __('Unlock perks'),
                     'icon' => '<path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/>'],
                    ['route' => route('nft.mintable'), 'title' => __('Mint an NFT'), 'sub' => __('From your uploads'),
                     'icon' => '<path d="M12 3v12M6 9l6-6 6 6M4 17v4h16v-4" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>'],
                ] as $tile)
                    <a href="{{ $tile['route'] }}" class="wh-tile">
                        <span class="wh-tile__ico"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true">{!! $tile['icon'] !!}</svg></span>
                        <svg class="wh-tile__arrow" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        <span class="wh-tile__title">{{ $tile['title'] }}</span>
                        <span class="wh-tile__sub">{{ $tile['sub'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Everything a crypto-naive user needs is in dollars above. Connecting an outside
             crypto wallet, addresses and raw sends are for people who already use crypto, so
             they live behind this disclosure at the bottom instead of greeting everyone. --}}
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
    </section>

    {{-- ── Coins ─────────────────────────────────────────────────────────────────── --}}
    <section class="lh-tabpanel" id="wallet-panel-tokens" role="tabpanel" hidden>
        <div>
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('My coins') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
                <a href="{{ route('cryptocurrency.marketplace') }}" class="lh-head__link">{{ __('Market') }}</a>
            </div>

            @if($holdings->count())
                <div class="lh-list">
                    @foreach($holdings as $h)
                        @include('wallet.partials.holding-row', ['h' => $h])
                    @endforeach
                </div>
            @else
                <div class="lh-empty">
                    <span class="lh-empty__icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 2l8.66 5v10L12 22l-8.66-5V7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg></span>
                    <h3>{{ __('No coins in your bag') }}</h3>
                    <p>{{ __('Back a creator you love — start with as little as $1') }}</p>
                    <a href="{{ route('cryptocurrency.marketplace') }}" class="lh-btn lh-btn--gold">{{ __('Browse coins') }}</a>
                </div>
            @endif
        </div>

        @if(session('send_success'))
            <p class="lh-flash lh-flash--success" role="status">{{ session('send_success') }}</p>
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
                @php
                    $firstWorth = \App\Support\TokenMoney::toUsd((float) $firstSend->balance, (float) $firstSend->cryptocurrency->current_price);
                @endphp
                <div class="lh-panel lh-brk" id="wallet-send">
                    <div class="lh-head" style="margin-top: 0">
                        <h2 class="lh-head__title">{{ __('Transfer') }}</h2>
                        <span class="lh-head__rule" aria-hidden="true"></span>
                        <span class="lh-chip lh-chip--up"><span class="lh-live" aria-hidden="true"></span>{{ __('Instant · free') }}</span>
                    </div>
                    <form method="POST" class="wh-send" id="coinSendForm"
                          action="{{ route('wallet.coins.send', $firstSend->cryptocurrency_id) }}">
                        @csrf
                        @if($sendable->count() > 1)
                            <div class="lh-field">
                                <span class="lh-cap">{{ __('Which coin') }}</span>
                                <select name="coin_id" id="coinSendCoin" aria-label="{{ __('Which coin') }}">
                                    @foreach($sendable as $w)
                                        @php $worth = \App\Support\TokenMoney::toUsd((float) $w->balance, (float) $w->cryptocurrency->current_price); @endphp
                                        <option value="{{ $w->cryptocurrency_id }}"
                                                data-action="{{ route('wallet.coins.send', $w->cryptocurrency_id) }}"
                                                data-worth="{{ \App\Support\TokenMoney::format($worth) }}"
                                                data-worth-raw="{{ number_format($worth, 2, '.', '') }}"
                                                @selected($w->is($firstSend))>
                                            {{ $w->cryptocurrency->name }} — {{ \App\Support\TokenMoney::format($worth) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="coin_id" value="{{ $firstSend->cryptocurrency_id }}">
                        @endif
                        <div class="lh-field">
                            <span class="lh-cap" id="coinSendToLabel">{{ __('To') }}</span>
                            <input type="text" class="lh-input" name="recipient" value="{{ old('recipient') }}" placeholder="@username"
                                   aria-labelledby="coinSendToLabel" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                        </div>
                        <div class="lh-field">
                            <span class="lh-cap" id="coinSendAmountLabel">{{ __('Amount') }}</span>
                            <span class="lh-money">
                                <span class="lh-money__sign" aria-hidden="true">$</span>
                                <input type="number" class="lh-input" name="amount_usd" id="coinSendAmount" inputmode="decimal"
                                       aria-labelledby="coinSendAmountLabel" step="0.01" min="1" placeholder="0.00" value="{{ old('amount_usd') }}">
                            </span>
                            <div class="lh-quick" id="coinSendQuick" data-worth="{{ number_format($firstWorth, 2, '.', '') }}">
                                <button type="button" data-amount="5">$5</button>
                                <button type="button" data-amount="10">$10</button>
                                <button type="button" data-amount="25">$25</button>
                                <button type="button" data-fraction="0.5">{{ __('Half') }}</button>
                            </div>
                        </div>
                        <p class="lh-note">
                            {{ __('You have') }} <strong id="coinSendWorth">{{ \App\Support\TokenMoney::format($firstWorth) }}</strong>
                            {{ __('to send. They get the same dollar value in their wallet, instantly and free.') }}
                        </p>
                        @if(session('send_error'))
                            <p class="lh-note lh-note--error" role="alert">{{ session('send_error') }}</p>
                        @endif
                        @foreach(['recipient', 'amount_usd'] as $field)
                            @error($field)<p class="lh-note lh-note--error" role="alert">{{ $message }}</p>@enderror
                        @endforeach
                        <div class="wh-send__actions">
                            <button type="submit" class="lh-btn lh-btn--brand">
                                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 3 10 14M21 3l-7 18-4-7-7-4 18-7z" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ __('Send') }}
                            </button>
                            <button type="submit" name="send_all" value="1" class="lh-btn lh-btn--ghost" formnovalidate>{{ __('Send all') }}</button>
                        </div>
                    </form>
                </div>
            @endif
        @endif
    </section>

    {{-- ── NFTs ──────────────────────────────────────────────────────────────────── --}}
    <section class="lh-tabpanel" id="wallet-panel-nfts" role="tabpanel" hidden>
        <div>
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('My NFTs') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
                <a href="{{ route('nft.my-listings') }}" class="lh-head__link">{{ __('Listings') }}</a>
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
                <div class="lh-empty">
                    <span class="lh-empty__icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12l3 6-9 12L3 9z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M3 9h18" stroke="currentColor" stroke-width="1.5"/></svg></span>
                    <h3>{{ __('No NFTs yet') }}</h3>
                    <p>{{ __('Buy one from a creator, or turn something you posted into one you can sell') }}</p>
                    <a href="{{ route('nft.marketplace') }}" class="lh-btn lh-btn--gold">{{ __('Explore NFTs') }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- ── Creator coins ─────────────────────────────────────────────────────────── --}}
    <section class="lh-tabpanel" id="wallet-panel-coins" role="tabpanel" hidden>
        <div>
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('Creator coins') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
                <a href="{{ route('creator-coins.index') }}" class="lh-head__link">{{ __('Browse') }}</a>
            </div>

            @if($coinBalances->count())
                <div class="lh-list">
                    @foreach($coinBalances as $balance)
                        @php $coin = $balance->coin; @endphp
                        <a href="{{ route('creator-coins.show', $coin->id) }}" class="lh-row">
                            <span class="lh-hex lh-hex--sm lh-hex--violet">
                                @if($coin->logo)
                                    <img src="{{ asset('storage/' . $coin->logo) }}" alt="">
                                @else
                                    <span class="lh-hex__glyph">{{ strtoupper(substr($coin->name, 0, 1)) }}</span>
                                @endif
                            </span>
                            <span class="lh-row__main">
                                <span class="lh-row__title">{{ $coin->name }}</span>
                                <span class="lh-row__sub">{{ __('Unlocks perks') }}</span>
                            </span>
                            <span class="lh-row__end">
                                <span class="lh-row__value">{{ \App\Support\TokenMoney::format((float) $balance->balance * (float) $coin->price_per_point) }}</span>
                                <span class="lh-cap">{{ __('In perks') }}</span>
                            </span>
                        </a>
                    @endforeach
                </div>
                <p class="lh-note" style="margin-top: 12px">
                    {{ __('Creator points unlock perks and access. They are not redeemable for cash.') }}
                    {{ __('Indicative value:') }} <strong>${{ number_format($coinValue, 2) }}</strong>
                </p>
            @else
                <div class="lh-empty">
                    <span class="lh-empty__icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="m12 3 2.6 5.6 6.1.7-4.5 4.2 1.2 6L12 16.6 6.6 19.5l1.2-6L3.3 9.3l6.1-.7z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/></svg></span>
                    <h3>{{ __('No creator coins yet') }}</h3>
                    <p>{{ __('Buy a creator coin to unlock perks and support creators directly') }}</p>
                    <a href="{{ route('creator-coins.index') }}" class="lh-btn lh-btn--gold">{{ __('Browse creator coins') }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- ── Activity ──────────────────────────────────────────────────────────────── --}}
    <section class="lh-tabpanel" id="wallet-panel-activity" role="tabpanel" hidden>
        <div>
            <div class="lh-head">
                <h2 class="lh-head__title">{{ __('Activity log') }}</h2>
                <span class="lh-head__rule" aria-hidden="true"></span>
                <span class="lh-head__code">{{ $activity->count() }}</span>
            </div>

            @if($activity->count())
                <div class="lh-list">
                    @foreach($activityDays as $day => $items)
                        <h3 class="lh-day">{{ $day }}</h3>
                        @foreach($items as $item)
                            @include('wallet.partials.activity-row', ['item' => $item])
                        @endforeach
                    @endforeach
                </div>
            @else
                <div class="lh-empty">
                    <span class="lh-empty__icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h10M4 18h7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/></svg></span>
                    <h3>{{ __('Nothing logged yet') }}</h3>
                    <p>{{ __('Your coin, creator coin and NFT activity will show up here') }}</p>
                </div>
            @endif
        </div>
    </section>

</div>
</div>

@if($address)
<script src="{{ asset('libs/easyqrcodejs/dist/easy.qrcode.min.js') }}"></script>
@endif
<script src="{{ asset('js/pages/wallet-hud.js') }}?v=20260924a" defer></script>
<script>
(function () {
    // --- Balance visibility ---------------------------------------------------------
    var toggle = document.getElementById('walletBalanceToggle');
    var balance = document.getElementById('walletBalance');
    if (toggle && balance) {
        var open = toggle.querySelector('.wh-eye__open');
        var closed = toggle.querySelector('.wh-eye__closed');
        var hidden = false;
        toggle.addEventListener('click', function () {
            hidden = !hidden;
            balance.textContent = hidden ? '$••••••' : balance.getAttribute('data-balance');
            if (open) open.hidden = hidden;
            if (closed) closed.hidden = !hidden;
            // The slots, legend and rows carry the same figures, so they blur with the headline.
            document.querySelector('.wallet-page').classList.toggle('is-balance-hidden', hidden);
            toggle.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            toggle.setAttribute('aria-label', hidden ? @json(__('Show balance')) : @json(__('Hide balance')));
        });
    }

    // --- Tabs -----------------------------------------------------------------------
    var tabs = Array.prototype.slice.call(document.querySelectorAll('.lh-tab'));
    function activate(name, push) {
        tabs.forEach(function (t) {
            var on = t.dataset.panel === name;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            if (on && t.scrollIntoView) t.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        });
        document.querySelectorAll('.lh-tabpanel').forEach(function (p) {
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
    // Slots, hotbar and "All" links are shortcuts into their tab.
    document.querySelectorAll('[data-open-panel]').forEach(function (b) {
        b.addEventListener('click', function () {
            activate(b.dataset.openPanel, true);
            var target = (b.dataset.scrollTo && document.getElementById(b.dataset.scrollTo)) || document.getElementById('walletTabs');
            if (target && target.scrollIntoView) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
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
        var quick = document.getElementById('coinSendQuick');
        var amountEl = document.getElementById('coinSendAmount');
        if (coinSelect) coinSelect.addEventListener('change', function () {
            var opt = coinSelect.options[coinSelect.selectedIndex];
            sendForm.action = opt.getAttribute('data-action');
            worthEl.textContent = opt.getAttribute('data-worth');
            if (quick) quick.setAttribute('data-worth', opt.getAttribute('data-worth-raw'));
        });
        // Quick amounts: fixed dollars, or half of what this coin is worth.
        if (quick) quick.addEventListener('click', function (e) {
            var b = e.target.closest('button');
            if (!b) return;
            var worth = parseFloat(quick.getAttribute('data-worth')) || 0;
            var value = b.dataset.fraction ? Math.floor(worth * parseFloat(b.dataset.fraction) * 100) / 100 : parseFloat(b.dataset.amount);
            amountEl.value = Math.min(value, worth).toFixed(2);
            quick.querySelectorAll('button').forEach(function (x) { x.classList.toggle('is-on', x === b); });
        });
        if (amountEl && quick) amountEl.addEventListener('input', function () {
            quick.querySelectorAll('button').forEach(function (x) { x.classList.remove('is-on'); });
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
