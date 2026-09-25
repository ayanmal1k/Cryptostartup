@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $lvl = $levelCard;
    $tierClass = 'tier-' . $lvl['tier']['key'];
    $frameClass = \App\Services\PerkShopService::frameClass($user);
    $firstName = explode(' ', trim($user->name))[0] ?: $user->username;

    $dayDir = abs($dayMove) < 0.005 ? 'flat' : ($dayMove > 0 ? 'up' : 'down');
    $dayPct = $tokenValue > 0 && abs($dayMove) >= 0.005 ? $dayMove / max(0.01, $tokenValue - $dayMove) * 100 : 0;
    $weekMax = max(1, max(array_column($lvl['week'], 'xp')));

    // Send: only coins the creator allows to be transferred.
    $sendable = $tokenWallets->filter(fn ($w) => $w->cryptocurrency->transferable)->values();
    $firstSend = old('coin_id') ? $sendable->firstWhere('cryptocurrency_id', (int) old('coin_id')) : null;
    $firstSend = $firstSend ?? $sendable->first();
    $firstWorth = $firstSend ? \App\Support\TokenMoney::toUsd((float) $firstSend->balance, (float) $firstSend->cryptocurrency->current_price) : 0;
    $sendHasError = session('send_error') || $errors->has('recipient') || $errors->has('amount_usd');

    $activityDays = $activity->take(12)->groupBy(function ($item) {
        if (!$item['at']) return __('Earlier');
        if ($item['at']->isToday()) return __('Today');
        return $item['at']->isYesterday() ? __('Yesterday') : $item['at']->format('M j');
    });

    $icon = [
        'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>',
        'send' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg>',
        'out' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="18" height="13" rx="3"/><path d="M3 10h18M7 15h3"/></svg>',
        'buy' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17l6-6 4 4 8-8"/><path d="M14 7h7v7"/></svg>',
        'up' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 15 6-6 6 6"/></svg>',
        'down' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>',
        'chev' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>',
    ];
    $boostIcon = [
        'subscribe' => '<path d="M12 2l2.9 6.3 6.9.7-5.2 4.6 1.5 6.8L12 17l-6.1 3.4 1.5-6.8L2.2 9l6.9-.7z"/>',
        'stream_start' => '<circle cx="12" cy="12" r="2"/><path d="M16.2 7.8a6 6 0 0 1 0 8.4M7.8 16.2a6 6 0 0 1 0-8.4M19.1 4.9a10 10 0 0 1 0 14.2M4.9 19.1a10 10 0 0 1 0-14.2"/>',
        'nft_mint' => '<path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18"/>',
        'tip_send' => '<path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>',
        'purchase' => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
        'custom_request' => '<path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/>',
        'video_upload' => '<rect x="2" y="6" width="14" height="12" rx="2"/><path d="m16 10 6-3v10l-6-3"/>',
        'post_create' => '<path d="M12 20h9M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z"/>',
    ];
    $boostHref = [
        'subscribe' => route('feed'),
        'stream_start' => route('streams.create'),
        'nft_mint' => route('nft.mintable'),
        'tip_send' => route('feed'),
        'purchase' => route('feed'),
        'custom_request' => route('custom-requests.marketplace'),
        'video_upload' => url('/create'),
        'post_create' => url('/create'),
        'follow' => route('feed'),
        'comment_create' => route('feed'),
        'stream_join' => route('streams.index'),
    ];
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap">
    {{-- wallet.css only dresses the outside-wallet disclosure now. --}}
    <link rel="stylesheet" href="{{ asset('css/pages/wallet.css') }}?v=20260921a">
    <link rel="stylesheet" href="{{ asset('css/frames.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/fx.css') }}?v=20260925a">
@endsection

@section('content')
<div class="fx wallet-page {{ $isDarkTheme ? 'wallet-page--dark' : 'fx--light wallet-page--light' }} {{ $tierClass }}"
     data-fx-live="{{ route('wallet.live') }}" data-fx-every="30000">
<div class="fx-wrap fx-wrap--wide">

    @foreach(['success', 'error', 'send_success'] as $flash)
        @if(session($flash))
            <div class="fx-flash fx-flash--{{ $flash === 'error' ? 'error' : 'success' }}" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach

    {{-- ── Top bar ─────────────────────────────────────────────────────────────── --}}
    <header class="fx-top fx-in">
        <a href="{{ route('profile', ['username' => $user->username]) }}" class="fx-top__avatar {{ $frameClass }}">
            <img src="{{ $user->avatar }}" alt="">
        </a>
        <div class="fx-top__hello">
            <small data-fx-greeting>{{ __('Hello') }}</small>
            <strong>{{ $firstName }}</strong>
        </div>
        <a href="{{ route('engagement.hub') }}" class="fx-lvl" aria-label="{{ __('Level :n, :tier tier', ['n' => $lvl['level'], 'tier' => $lvl['tier']['name']]) }}">
            <span class="fx-lvl__ring" style="--p: {{ $lvl['percent'] }}"><b data-fx-bind="level.level">{{ $lvl['level'] }}</b></span>
            <span class="fx-lvl__name" data-fx-bind="level.tier">{{ $lvl['tier']['name'] }}</span>
        </a>
    </header>

    <div class="fx-grid">
        <div class="fx-col">

            {{-- ── Balance card ──────────────────────────────────────────────────── --}}
            <section class="fx-card fx-in" style="--i: 1; order: 1" aria-label="{{ __('Balance') }}">
                <div class="fx-card__top">
                    <span class="fx-card__label">
                        {{ __('Total balance') }}
                        <button type="button" class="fx-card__eye" data-fx-eye aria-pressed="false" aria-label="{{ __('Hide balances') }}">
                            <svg data-open viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg data-closed hidden viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18M10.6 10.6a3 3 0 0 0 4.2 4.2M9.9 5.2A9.7 9.7 0 0 1 12 5c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3.4 4.3M6.1 6.1A17.7 17.7 0 0 0 2 12s3.5 7 10 7c1 0 2-.1 2.9-.4"/></svg>
                        </button>
                    </span>
                    <span class="fx-card__brand">{{ strtoupper(getSetting('site.name') ?: 'Wallet') }}</span>
                </div>

                <div class="fx-card__value" data-fx-money data-fx-big data-fx-bind="total" data-fx-count="{{ number_format($totalBalance, 2, '.', '') }}">
                    {{ '$' . number_format(floor($totalBalance)) }}<span class="fx-cents">.{{ substr(number_format($totalBalance, 2, '.', ''), -2) }}</span>
                </div>

                <div class="fx-card__move">
                    @if($dayDir === 'flat')
                        <span class="fx-pill">{{ __('No change today') }}</span>
                    @else
                        <span class="fx-pill" data-fx-money>{!! $icon[$dayDir] !!}{{ \App\Support\TokenMoney::format(abs($dayMove)) }} · {{ number_format(abs($dayPct), 2) }}%</span>
                        <span>{{ __('today') }}</span>
                    @endif
                </div>

                <div class="fx-card__split">
                    <div><small>{{ __('Credits') }}</small><b data-fx-money data-fx-bind="credits" data-fx-value="{{ $credits }}">{{ \App\Support\TokenMoney::format($credits) }}</b></div>
                    <div><small>{{ __('Coins') }}</small><b data-fx-money data-fx-bind="coins" data-fx-value="{{ $tokenValue }}">{{ \App\Support\TokenMoney::format($tokenValue) }}</b></div>
                    <div><small>{{ __('Perks') }}</small><b data-fx-money data-fx-bind="perks" data-fx-value="{{ $coinValue }}">{{ \App\Support\TokenMoney::format($coinValue) }}</b></div>
                </div>
            </section>

            {{-- ── Actions. Deposit/withdraw use the real payment flow (Stripe/PayPal via
                 my.settings), not the stub that recorded deposits without payment. ──── --}}
            <nav class="fx-actions fx-in" style="--i: 2; order: 2" aria-label="{{ __('Wallet actions') }}">
                <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'deposit']) }}" class="fx-action fx-action--primary">
                    <span class="fx-action__icon">{!! $icon['plus'] !!}</span>{{ __('Add money') }}
                </a>
                <button type="button" class="fx-action" @if($firstSend) data-fx-open="fx-send" @else onclick="location.href='{{ route('cryptocurrency.marketplace') }}'" @endif>
                    <span class="fx-action__icon">{!! $icon['send'] !!}</span>{{ __('Send') }}
                </button>
                <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'withdraw']) }}" class="fx-action">
                    <span class="fx-action__icon">{!! $icon['out'] !!}</span>{{ __('Cash out') }}
                </a>
                <a href="{{ route('cryptocurrency.marketplace') }}" class="fx-action">
                    <span class="fx-action__icon">{!! $icon['buy'] !!}</span>{{ __('Buy coins') }}
                </a>
            </nav>

            {{-- ── Coin value over time ──────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 3; order: 4" data-fx-chart data-fx-live-series="coins">
                <script type="application/json">{!! json_encode(['series' => $series, 'mode' => 'money', 'flatText' => __('No change')]) !!}</script>
                <div class="fx-chart__head">
                    <div>
                        <span class="fx-label">{{ __('Your coins') }}</span>
                        <div class="fx-chart__value" data-fx-money data-fx-chart-value>{{ \App\Support\TokenMoney::format($tokenValue) }}</div>
                        <div class="fx-chart__delta fx-flat" data-fx-chart-delta>&nbsp;</div>
                    </div>
                    <span class="fx-live">{{ __('Live') }}</span>
                </div>
                <div class="fx-chart__box">
                    <div class="fx-chart__tip" aria-hidden="true"></div>
                    <div class="fx-chart__empty" hidden>
                        <b>{{ __('Your chart starts with your first coin') }}</b>
                        <span>{{ __('Buy any coin from $1 and watch it here.') }}</span>
                    </div>
                </div>
                <div class="fx-seg" role="group" aria-label="{{ __('Time range') }}">
                    <span class="fx-seg__thumb" aria-hidden="true"></span>
                    <button type="button" value="1w" aria-pressed="false">1W</button>
                    <button type="button" value="1m" class="is-on" aria-pressed="true">1M</button>
                    <button type="button" value="3m" aria-pressed="false">3M</button>
                    <button type="button" value="1y" aria-pressed="false">1Y</button>
                    <button type="button" value="all" aria-pressed="false">{{ __('All') }}</button>
                </div>
            </section>

            {{-- ── Assets ────────────────────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 4; order: 5" id="assets">
                <div class="fx-head">
                    <h2 class="fx-head__title">{{ __('Assets') }}</h2>
                    <a href="{{ route('cryptocurrency.marketplace') }}" class="fx-head__link">{{ __('Market') }} {!! $icon['chev'] !!}</a>
                </div>

                @if($holdings->count() || $coinBalances->count())
                    <div class="fx-chips" data-fx-filter="fx-assets" role="group" aria-label="{{ __('Filter assets') }}" style="margin-bottom: 8px">
                        <button type="button" class="fx-chip is-on" data-kind="all" aria-pressed="true">{{ __('All') }}</button>
                        <button type="button" class="fx-chip" data-kind="coin" aria-pressed="false">{{ __('Coins') }} · {{ $holdings->count() }}</button>
                        <button type="button" class="fx-chip" data-kind="creator" aria-pressed="false">{{ __('Creator coins') }} · {{ $coinBalances->count() }}</button>
                    </div>

                    <div class="fx-list" id="fx-assets">
                        @foreach($holdings as $h)
                            <a href="{{ route('cryptocurrency.show', $h->id) }}" class="fx-row" data-kind="coin" data-fx-holding="{{ $h->id }}">
                                <span class="fx-coin">
                                    @if($h->crypto->logo)<img src="{{ asset('storage/' . $h->crypto->logo) }}" alt="">@else{{ strtoupper(substr($h->crypto->name, 0, 1)) }}@endif
                                </span>
                                <span class="fx-row__main">
                                    <span class="fx-row__title">{{ $h->crypto->name }}</span>
                                    <span class="fx-row__sub {{ 'fx-' . $h->dir }}" data-fx-money>
                                        @if($h->dir === 'flat')
                                            {{ __('No change today') }}
                                        @else
                                            {{ $h->dir === 'up' ? '+' : '−' }}{{ \App\Support\TokenMoney::format(abs($h->move)) }} {{ __('today') }}
                                        @endif
                                    </span>
                                </span>
                                @if($h->spark)
                                    <span class="fx-row__spark" aria-hidden="true">
                                        <svg viewBox="0 0 72 28" preserveAspectRatio="none"><path d="{{ $h->spark }}" fill="none" stroke="{{ $h->dir === 'down' ? 'var(--fx-down)' : 'var(--fx-up)' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/></svg>
                                    </span>
                                @endif
                                <span class="fx-row__end">
                                    <span class="fx-row__value" data-fx-money data-fx-hvalue data-fx-value="{{ $h->value }}">{{ \App\Support\TokenMoney::format($h->value) }}</span>
                                    @if($h->dir !== 'flat')
                                        <span class="fx-row__meta {{ 'fx-' . $h->dir }}">{{ $h->change > 0 ? '+' : '' }}{{ number_format($h->change, 2) }}%</span>
                                    @endif
                                </span>
                            </a>
                        @endforeach

                        @foreach($coinBalances as $balance)
                            @php $coin = $balance->coin; @endphp
                            <a href="{{ route('creator-coins.show', $coin->id) }}" class="fx-row" data-kind="creator">
                                <span class="fx-coin fx-coin--creator">
                                    @if($coin->logo)<img src="{{ asset('storage/' . $coin->logo) }}" alt="">@else{{ strtoupper(substr($coin->name, 0, 1)) }}@endif
                                </span>
                                <span class="fx-row__main">
                                    <span class="fx-row__title">{{ $coin->name }}</span>
                                    <span class="fx-row__sub">{{ __('Creator coin · unlocks perks') }}</span>
                                </span>
                                <span class="fx-row__end">
                                    <span class="fx-row__value" data-fx-money>{{ \App\Support\TokenMoney::format((float) $balance->balance * (float) $coin->price_per_point) }}</span>
                                    <span class="fx-row__meta fx-flat">{{ __('in perks') }}</span>
                                </span>
                            </a>
                        @endforeach

                        <div class="fx-empty" data-fx-filter-empty hidden>
                            <p>{{ __('Nothing here yet.') }}</p>
                        </div>
                    </div>
                @else
                    <div class="fx-empty">
                        <span class="fx-empty__icon">{!! $icon['buy'] !!}</span>
                        <h3>{{ __('Start your portfolio') }}</h3>
                        <p>{{ __('Back a creator with their coin from $1. It shows up here, live.') }}</p>
                        <a href="{{ route('cryptocurrency.marketplace') }}" class="fx-btn fx-btn--primary fx-btn--sm" style="margin-top: 8px">{{ __('Browse coins') }}</a>
                    </div>
                @endif
            </section>

            {{-- ── Activity ──────────────────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 5; order: 6">
                <div class="fx-head">
                    <h2 class="fx-head__title">{{ __('Activity') }}</h2>
                    @if(Route::has('cryptocurrency.transactions'))
                        <a href="{{ route('cryptocurrency.transactions') }}" class="fx-head__link">{{ __('See all') }} {!! $icon['chev'] !!}</a>
                    @endif
                </div>
                @if($activity->count())
                    <div class="fx-list">
                        @foreach($activityDays as $day => $items)
                            <div class="fx-day">{{ $day }}</div>
                            @foreach($items as $item)
                                <div class="fx-row">
                                    <span class="fx-ico {{ $item['kind'] === 'nft' ? 'fx-ico--nft' : ($item['in'] ? 'fx-ico--in' : '') }}" aria-hidden="true">
                                        @if($item['kind'] === 'nft')
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18"/></svg>
                                        @elseif($item['in'])
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 7 7 17M16 17H7V8"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 17 17 7M8 7h9v9"/></svg>
                                        @endif
                                    </span>
                                    <span class="fx-row__main">
                                        <span class="fx-row__title" style="font-size: 14.5px">{{ $item['label'] }}</span>
                                        <span class="fx-row__sub">{{ $item['at'] ? $item['at']->format('g:i A') : '' }}</span>
                                    </span>
                                    <span class="fx-row__end">
                                        @if($item['amount'] !== null)
                                            <span class="fx-row__value {{ $item['in'] ? 'fx-up' : '' }}" data-fx-money>{{ $item['in'] ? '+' : '−' }}{{ $item['amount'] }}</span>
                                        @endif
                                    </span>
                                </div>
                            @endforeach
                        @endforeach
                    </div>
                @else
                    <div class="fx-empty">
                        <span class="fx-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 6h16M4 12h10M4 18h7"/></svg></span>
                        <h3>{{ __('No activity yet') }}</h3>
                        <p>{{ __('Buys, sends and NFT moves show up here the moment they happen.') }}</p>
                    </div>
                @endif
            </section>
        </div>

        <div class="fx-col">
            {{-- ── Level / membership tier ───────────────────────────────────────── --}}
            <section class="fx-tier {{ $tierClass }} fx-in" style="--i: 2; order: 3" aria-label="{{ __('Your level') }}">
                <svg width="0" height="0" style="position: absolute" aria-hidden="true">
                    <defs><linearGradient id="fxTierGrad" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--tier-a)"/><stop offset="1" stop-color="var(--tier-b)"/></linearGradient></defs>
                </svg>
                <div class="fx-tier__main">
                    <div class="fx-ring" data-fx-ring="{{ $lvl['percent'] }}" role="progressbar" aria-valuenow="{{ $lvl['percent'] }}" aria-valuemin="0" aria-valuemax="100" aria-label="{{ __('Progress to level :n', ['n' => $lvl['next_level']]) }}">
                        <svg viewBox="0 0 100 100" aria-hidden="true">
                            <circle class="fx-ring__track" cx="50" cy="50" r="42"/>
                            <circle class="fx-ring__bar" cx="50" cy="50" r="42"/>
                        </svg>
                        <span class="fx-ring__center"><small>{{ __('Level') }}</small><b data-fx-bind="level.level">{{ $lvl['level'] }}</b></span>
                    </div>
                    <div style="min-width: 0">
                        <span class="fx-tier__badge"><i>✦</i>{{ $lvl['tier']['name'] }}</span>
                        <div class="fx-tier__title"><span data-fx-plain data-fx-bind="level.to_next" data-fx-value="{{ $lvl['to_next'] }}">{{ $lvl['to_next'] }}</span> {{ __('XP to go') }}</div>
                        <div class="fx-tier__sub">
                            {{ __('to Level :n', ['n' => $lvl['next_level']]) }}
                            @if($lvl['tier']['next'])
                                · <b>{{ trans_choice('{1} :n level|[2,*] :n levels', $lvl['levels_to_tier'], ['n' => $lvl['levels_to_tier']]) }}</b> {{ __('to :tier', ['tier' => $lvl['tier']['next']['name']]) }}
                            @else
                                · {{ __('Top tier') }}
                            @endif
                        </div>
                    </div>
                </div>

                <div class="fx-tier__stats">
                    <div class="fx-stat"><small>{{ __('Streak') }}</small><b><span aria-hidden="true">🔥</span><span data-fx-plain data-fx-bind="level.streak" data-fx-value="{{ $lvl['streak'] }}">{{ $lvl['streak'] }}</span></b></div>
                    <div class="fx-stat"><small>{{ __('XP today') }}</small><b style="gap: 1px">+<span data-fx-plain data-fx-bind="level.today_xp" data-fx-value="{{ $lvl['today_xp'] }}">{{ $lvl['today_xp'] }}</span></b></div>
                    <div class="fx-stat"><small>{{ __('Sparks') }}</small><b><span aria-hidden="true">✨</span><span data-fx-plain data-fx-bind="level.sparks" data-fx-value="{{ $lvl['sparks'] }}">{{ number_format($lvl['sparks']) }}</span></b></div>
                </div>

                <div class="fx-week">
                    <div class="fx-week__head">
                        <span class="fx-label">{{ __('XP this week') }}</span>
                        <span class="fx-num" style="font-weight: 600">{{ number_format($lvl['week_xp']) }} XP</span>
                    </div>
                    <div class="fx-week__bars">
                        @foreach($lvl['week'] as $i => $d)
                            <div class="fx-week__bar {{ $loop->last ? 'is-today' : '' }} {{ $d['xp'] > 0 ? 'has-xp' : '' }}" title="{{ $d['xp'] }} XP">
                                <span class="fx-week__fill" style="height: {{ max(4, round($d['xp'] / $weekMax * 64)) }}px; animation-delay: {{ $i * 60 }}ms"></span>
                                <small>{{ mb_substr($d['label'], 0, 3) }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>

                @if(count($lvl['boosts']))
                    <div class="fx-boosts">
                        <span class="fx-label" style="margin-bottom: 4px">{{ __('Level up faster today') }}</span>
                        @foreach($lvl['boosts'] as $b)
                            <a href="{{ $boostHref[$b['key']] ?? route('engagement.hub') }}" class="fx-boost">
                                <span class="fx-boost__dot"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $boostIcon[$b['key']] ?? $boostIcon['post_create'] !!}</svg></span>
                                <span class="fx-boost__text">{{ $b['label'] }}<small>{{ trans_choice('{1} :n left today|[2,*] :n left today', $b['left'], ['n' => $b['left']]) }}</small></span>
                                <span class="fx-boost__xp">+{{ $b['xp'] }} XP</span>
                            </a>
                        @endforeach
                    </div>
                @endif

                <a href="{{ route('engagement.hub') }}" class="fx-btn fx-btn--ghost fx-btn--block fx-btn--sm" style="margin-top: 16px">{{ __('Open Rewards') }} {!! $icon['chev'] !!}</a>
            </section>

            {{-- ── Collectibles ──────────────────────────────────────────────────── --}}
            <section class="fx-panel fx-panel--pad fx-in" style="--i: 3; order: 7">
                <div class="fx-head">
                    <h2 class="fx-head__title">{{ __('Collectibles') }} @if($nftCount)<span class="fx-label">· {{ $nftCount }}</span>@endif</h2>
                    <a href="{{ $nftCount ? route('nft.my-listings') : route('nft.marketplace') }}" class="fx-head__link">{{ $nftCount ? __('Manage') : __('Explore') }} {!! $icon['chev'] !!}</a>
                </div>
                @if($nftCount)
                    <div class="fx-rail">
                        @foreach($nfts->take(12) as $nft)
                            @php $listing = $nft->activeListing; @endphp
                            <a href="{{ route('nft.show', $nft->id) }}" class="fx-nft">
                                <span class="fx-nft__media">
                                    @if($nft->display_image_url)<img src="{{ $nft->display_image_url }}" alt="" loading="lazy">@endif
                                    @if($nft->isPendingMint())
                                        <span class="fx-nft__tag">{{ __('Creating…') }}</span>
                                    @elseif($listing)
                                        <span class="fx-nft__tag">{{ __('Listed') }}</span>
                                    @endif
                                </span>
                                <span class="fx-nft__name">{{ $nft->name }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="fx-empty">
                        <span class="fx-empty__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 3h12l3 6-9 12L3 9z"/><path d="M3 9h18"/></svg></span>
                        <h3>{{ __('No collectibles yet') }}</h3>
                        <p>{{ __('Buy one from a creator, or turn your own post into one fans can collect.') }}</p>
                        <div style="display: flex; gap: 8px; margin-top: 8px">
                            <a href="{{ route('nft.marketplace') }}" class="fx-btn fx-btn--primary fx-btn--sm">{{ __('Explore') }}</a>
                            <a href="{{ route('nft.mintable') }}" class="fx-btn fx-btn--ghost fx-btn--sm">{{ __('Create') }}</a>
                        </div>
                    </div>
                @endif
            </section>

            {{-- Outside crypto wallets are for people who already use crypto, so they
                 live behind this disclosure instead of greeting everyone. --}}
            <details class="wallet-advanced fx-in" style="--i: 4; order: 8">
                <summary class="wallet-advanced__summary">
                    <span>{{ __('Use an outside crypto wallet') }}</span>
                    <span class="wallet-advanced__tag">{{ __('Advanced') }}</span>
                </summary>
                @include('nft.partials.wallet-connect')

                @if($address)
                    <div class="wallet-split">
                        <div class="wallet-block">
                            <div class="wallet-block__head"><h2 class="wallet-block__title">{{ __('Receive') }}</h2></div>
                            <div class="wallet-receive">
                                <div id="wallet-qr" class="wallet-receive__qr"></div>
                                <div class="wallet-receive__addr">
                                    <input type="text" id="wallet-address-field" value="{{ $address }}" readonly aria-label="{{ __('Your wallet address') }}">
                                    <button type="button" id="copy-address-btn" class="wallet-btn wallet-btn--ghost wallet-btn--inline" aria-label="{{ __('Copy address') }}">{{ __('Copy') }}</button>
                                </div>
                                <p class="wallet-note">{{ __('Only for sending from an outside crypto wallet.') }}</p>
                            </div>
                        </div>
                        <div class="wallet-block">
                            <div class="wallet-block__head">
                                <h2 class="wallet-block__title">{{ __('Send') }} {{ strtoupper(config('web3.network')) }}</h2>
                                @if($nativeBalance !== null)<span class="wallet-block__link">{{ number_format($nativeBalance, 4) }}</span>@endif
                            </div>
                            <div class="wallet-form">
                                <label class="wallet-field"><span>{{ __('Recipient address') }}</span><input type="text" id="send-to" placeholder="0x..."></label>
                                <label class="wallet-field"><span>{{ __('Amount') }}</span><input type="number" id="send-amount" step="0.0001" min="0" placeholder="0.0"></label>
                                <button type="button" class="wallet-btn wallet-btn--primary wallet-btn--inline" id="send-btn">{{ __('Send') }}</button>
                                <div id="send-status" class="wallet-note"></div>
                                <p class="wallet-note">{{ __('Sends are signed by your own wallet — the platform never holds your keys.') }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </details>
        </div>
    </div>
</div>

{{-- ── Send sheet: dollars only. Units are worked out on the server (CoinSendController). ── --}}
@if($firstSend)
    <div class="fx-sheet" id="fx-send" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="fx-send-title" @if($sendHasError) data-fx-autoopen @endif>
        <div class="fx-sheet__panel">
            <div class="fx-sheet__grab" aria-hidden="true"></div>
            <div class="fx-sheet__head">
                <h2 class="fx-sheet__title" id="fx-send-title">{{ __('Send money') }}</h2>
                <button type="button" class="fx-icon-btn" data-fx-close aria-label="{{ __('Close') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" id="coinSendForm" action="{{ route('wallet.coins.send', $firstSend->cryptocurrency_id) }}" style="display: flex; flex-direction: column; gap: 16px">
                @csrf
                <div class="fx-field">
                    <span class="fx-field__label" id="fx-to-label">{{ __('To') }}</span>
                    <input type="text" class="fx-input" name="recipient" value="{{ old('recipient') }}" placeholder="@username"
                           aria-labelledby="fx-to-label" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                </div>

                <div class="fx-amount">
                    <span aria-hidden="true">$</span>
                    <input type="number" name="amount_usd" id="coinSendAmount" inputmode="decimal" step="0.01" min="1"
                           placeholder="0" value="{{ old('amount_usd') }}" aria-label="{{ __('Amount in dollars') }}">
                </div>

                <div class="fx-quick" id="coinSendQuick" data-worth="{{ number_format($firstWorth, 2, '.', '') }}">
                    <button type="button" data-amount="5">$5</button>
                    <button type="button" data-amount="10">$10</button>
                    <button type="button" data-amount="25">$25</button>
                    <button type="button" data-fraction="1">{{ __('Max') }}</button>
                </div>

                @if($sendable->count() > 1)
                    <div class="fx-field">
                        <span class="fx-field__label" id="fx-from-label">{{ __('From') }}</span>
                        <select name="coin_id" id="coinSendCoin" aria-labelledby="fx-from-label">
                            @foreach($sendable as $w)
                                @php $worth = \App\Support\TokenMoney::toUsd((float) $w->balance, (float) $w->cryptocurrency->current_price); @endphp
                                <option value="{{ $w->cryptocurrency_id }}" data-action="{{ route('wallet.coins.send', $w->cryptocurrency_id) }}"
                                        data-worth="{{ \App\Support\TokenMoney::format($worth) }}" data-worth-raw="{{ number_format($worth, 2, '.', '') }}" @selected($w->is($firstSend))>
                                    {{ $w->cryptocurrency->name }} — {{ \App\Support\TokenMoney::format($worth) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="coin_id" value="{{ $firstSend->cryptocurrency_id }}">
                @endif

                <p class="fx-note">
                    {{ __('Available') }}: <strong id="coinSendWorth">{{ \App\Support\TokenMoney::format($firstWorth) }}</strong>
                    · {{ __('They get the same dollar value, instantly and free.') }}
                </p>
                @if(session('send_error'))<p class="fx-note fx-note--error" role="alert">{{ session('send_error') }}</p>@endif
                @foreach(['recipient', 'amount_usd'] as $field)
                    @error($field)<p class="fx-note fx-note--error" role="alert">{{ $message }}</p>@enderror
                @endforeach

                <button type="submit" class="fx-btn fx-btn--primary fx-btn--block">{{ __('Send') }}</button>
                <button type="submit" name="send_all" value="1" class="fx-btn fx-btn--ghost fx-btn--block" formnovalidate style="margin-top: -6px">{{ __('Send everything') }}</button>
            </form>
        </div>
    </div>
@endif
</div>

@if($address)
<script src="{{ asset('libs/easyqrcodejs/dist/easy.qrcode.min.js') }}"></script>
@endif
<script src="{{ asset('js/pages/fx.js') }}?v=20260925a" defer></script>
<script>
(function () {
    @if($address)
    // --- Receive: QR + copy -------------------------------------------------------
    var address = @json($address);
    try {
        if (window.QRCode) {
            new QRCode(document.getElementById('wallet-qr'), { text: address, width: 150, height: 150, correctLevel: QRCode.CorrectLevel.M });
        }
    } catch (e) { /* QR is optional */ }
    var copyBtn = document.getElementById('copy-address-btn');
    if (copyBtn) copyBtn.addEventListener('click', function () {
        var field = document.getElementById('wallet-address-field');
        field.select();
        if (navigator.clipboard) navigator.clipboard.writeText(address); else document.execCommand('copy');
        var original = copyBtn.textContent;
        copyBtn.textContent = @json(__('Copied'));
        setTimeout(function () { copyBtn.textContent = original; }, 1500);
    });

    // --- Outside-wallet send: signed by the user's own wallet ----------------------
    var sendBtn = document.getElementById('send-btn');
    var status = document.getElementById('send-status');
    function say(msg, kind) { status.textContent = msg; status.className = 'wallet-note wallet-note--' + kind; }
    if (sendBtn) sendBtn.addEventListener('click', async function () {
        var to = document.getElementById('send-to').value.trim();
        var amount = parseFloat(document.getElementById('send-amount').value);
        if (!/^0x[a-fA-F0-9]{40}$/.test(to)) { return say(@json(__('Enter a valid 0x recipient address.')), 'error'); }
        if (!(amount > 0)) { return say(@json(__('Enter an amount greater than zero.')), 'error'); }
        if (typeof window.ethereum === 'undefined') { return say(@json(__('No browser wallet detected. Connect a wallet to send.')), 'error'); }
        try {
            say(@json(__('Confirm the transaction in your wallet…')), 'muted');
            var accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            var hash = await window.ethereum.request({ method: 'eth_sendTransaction', params: [{ from: accounts[0], to: to, value: '0x' + Math.floor(amount * 1e18).toString(16) }] });
            say(@json(__('Sent. Transaction:')) + ' ' + hash, 'success');
        } catch (e) { say(@json(__('Send failed:')) + ' ' + (e.message || e), 'error'); }
    });
    @endif

    // --- Send to a member: dollars only -------------------------------------------
    var form = document.getElementById('coinSendForm');
    if (!form) return;
    var select = document.getElementById('coinSendCoin');
    var worthEl = document.getElementById('coinSendWorth');
    var quick = document.getElementById('coinSendQuick');
    var amount = document.getElementById('coinSendAmount');
    if (select) select.addEventListener('change', function () {
        var opt = select.options[select.selectedIndex];
        form.action = opt.getAttribute('data-action');
        worthEl.textContent = opt.getAttribute('data-worth');
        quick.setAttribute('data-worth', opt.getAttribute('data-worth-raw'));
    });
    quick.addEventListener('click', function (e) {
        var b = e.target.closest('button');
        if (!b) return;
        var worth = parseFloat(quick.getAttribute('data-worth')) || 0;
        var v = b.dataset.fraction ? Math.floor(worth * parseFloat(b.dataset.fraction) * 100) / 100 : parseFloat(b.dataset.amount);
        amount.value = Math.min(v, worth).toFixed(2);
        quick.querySelectorAll('button').forEach(function (x) { x.classList.toggle('is-on', x === b); });
    });
    amount.addEventListener('input', function () { quick.querySelectorAll('button').forEach(function (x) { x.classList.remove('is-on'); }); });
    // Sends can't be undone, so read it back in plain words first.
    form.addEventListener('submit', function (e) {
        var all = e.submitter && e.submitter.name === 'send_all';
        var who = form.recipient.value.trim().replace(/^@/, '');
        if (!who) { e.preventDefault(); form.recipient.focus(); return; }
        if (!all && !(parseFloat(amount.value) >= 1)) { e.preventDefault(); amount.focus(); return; }
        var sum = all ? worthEl.textContent : '$' + parseFloat(amount.value).toFixed(2);
        if (!window.confirm(@json(__('Send')) + ' ' + sum + ' ' + @json(__('to')) + ' @' + who + '?')) e.preventDefault();
    });
})();
</script>
@endsection
