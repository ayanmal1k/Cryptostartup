@extends('layouts.user-no-nav')

@section('page_title', __('Rewards'))

@php
    $sparks   = (int) ($me->sparks ?? 0);
    $streak   = (int) ($me->streak_count ?? 0);
    $freezes  = (int) ($me->streak_freezes ?? 0);
    $level    = (int) ($me->level ?? 1);

    $claimable = $board->filter(fn ($q) => $q->completed_at !== null && $q->claimed_at === null)->count();
    $claimed   = $board->filter(fn ($q) => $q->claimed_at !== null)->count();
    $swept     = $board->count() > 0 && $claimed === $board->count();

    $frameClass = \App\Services\PerkShopService::frameClass($me);
    $ownedFrames = $inventory->pluck('reward_key')->intersect($frames)->values();
    $gamesLeft = $playsLeft->sum();
    $money = fn ($v) => '$' . number_format((float) $v, 2);
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/frames.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/rewards.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/supply-drop.css') }}?v=20260923a">
@endsection

@section('content')
<div class="dx rw">

    @include('elements.discover.subnav', [
        'tabs' => [
            ['label' => __('Today'), 'href' => '#today', 'active' => true, 'count' => ($spunToday ? 0 : 1) + $claimable],
            ['label' => __('Games'), 'href' => '#games', 'active' => false, 'count' => $gamesLeft],
            ['label' => __('Drops'), 'href' => '#drops', 'active' => false, 'count' => $drops->count()],
            ['label' => __('Shop'), 'href' => '#shop', 'active' => false],
        ],
    ])

    <main class="dx-main">

        {{-- ================================================================ Hero --}}
        <section class="rw-hero">
            <div class="rw-hero__me">
                <span class="rw-avatar {{ $frameClass }}" id="rw-avatar">
                    <img src="{{ $me->avatar }}" alt="">
                </span>
                <div class="rw-hero__who">
                    <span class="rw-hero__level">{{ __('Level') }} <strong class="js-level">{{ $level }}</strong></span>
                    <div class="rw-xpbar" role="progressbar" aria-valuenow="{{ $progress['percent'] }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="rw-xpbar__fill js-xp-fill" style="width: {{ $progress['percent'] }}%"></div>
                    </div>
                    <span class="rw-hero__xp">
                        <strong id="rw-xp-into">{{ number_format($progress['into']) }}</strong> / {{ number_format($progress['span']) }}
                        {{ __('XP to level') }} {{ $progress['next_level'] }}
                    </span>
                </div>
            </div>

            <ul class="rw-stats">
                <li class="rw-stat">
                    <span class="rw-stat__icon" aria-hidden="true">✨</span>
                    <span class="rw-stat__value" id="rw-sparks">{{ number_format($sparks) }}</span>
                    <span class="rw-stat__label">{{ __('Sparks') }}</span>
                </li>
                <li class="rw-stat">
                    <span class="rw-stat__icon" aria-hidden="true">🔥</span>
                    <span class="rw-stat__value" id="rw-streak">{{ $streak }}</span>
                    <span class="rw-stat__label">{{ __('Day streak') }}</span>
                </li>
                <li class="rw-stat">
                    <span class="rw-stat__icon" aria-hidden="true">🧊</span>
                    <span class="rw-stat__value" id="rw-freezes">{{ $freezes }}</span>
                    <span class="rw-stat__label">{{ __('Freezes') }}</span>
                </li>
                <li class="rw-stat">
                    <span class="rw-stat__icon" aria-hidden="true">📈</span>
                    <span class="rw-stat__value">+{{ number_format($today['xp']) }}</span>
                    <span class="rw-stat__label">{{ __('XP today') }}</span>
                </li>
            </ul>
        </section>

        {{-- =============================================================== Today --}}
        <div class="rw-panel" id="rw-panel-today" data-rw-panel="today">

            <section class="rw-spin {{ $spunToday ? 'is-spent' : '' }}" id="rw-spin">
                <div class="rw-spin__copy">
                    <span class="dx-eyebrow">{{ __('Daily spin') }} · {{ __('free') }}</span>
                    <h2 class="rw-spin__title">{{ $spunToday ? __('You spun today') : __('Spin for a prize') }}</h2>
                    <p class="rw-spin__sub" id="rw-spin-sub">
                        @if($spunToday)
                            {{ __('Your next free spin is ready in') }} <strong class="js-countdown" data-left="{{ now()->diffInSeconds(now()->copy()->endOfDay()) }}">—</strong>
                        @else
                            {{ __('One free spin every day. XP, sparks, streak freezes — or a Bronze Drop.') }}
                        @endif
                    </p>
                </div>

                <div class="rw-wheel">
                    <span class="rw-wheel__pointer" aria-hidden="true"></span>
                    <svg class="rw-wheel__disc" id="rw-wheel" viewBox="-100 -100 200 200" aria-hidden="true">
                        @php $n = count($spin); @endphp
                        @foreach($spin as $i => $seg)
                            @php
                                $a0 = deg2rad(-90 + 360 / $n * $i);
                                $a1 = deg2rad(-90 + 360 / $n * ($i + 1));
                                $mid = deg2rad(-90 + 360 / $n * ($i + 0.5));
                                $path = sprintf('M0 0 L%.3f %.3f A96 96 0 0 1 %.3f %.3f Z', 96 * cos($a0), 96 * sin($a0), 96 * cos($a1), 96 * sin($a1));
                                $tx = 62 * cos($mid);
                                $ty = 62 * sin($mid);
                                $rot = rad2deg($mid) + 90;
                            @endphp
                            <path d="{{ $path }}" class="rw-wheel__seg rw-wheel__seg--{{ $i % 2 ? 'b' : 'a' }} {{ $seg['type'] === 'drop' ? 'rw-wheel__seg--jackpot' : '' }}"/>
                            <g transform="translate({{ round($tx, 2) }} {{ round($ty, 2) }}) rotate({{ round($rot, 2) }})">
                                <text class="rw-wheel__icon" y="-4" text-anchor="middle">{{ $seg['icon'] }}</text>
                                <text class="rw-wheel__label" y="12" text-anchor="middle">{{ $seg['label'] }}</text>
                            </g>
                        @endforeach
                        <circle r="96" class="rw-wheel__rim"/>
                    </svg>
                    <button type="button" class="rw-wheel__hub js-spin" {{ $spunToday ? 'disabled' : '' }}
                            data-segments="{{ $n }}" aria-label="{{ __('Spin the wheel') }}">
                        {{ $spunToday ? '✓' : __('SPIN') }}
                    </button>
                </div>
            </section>

            @if($freezes === 0 && $streak >= 3)
                <p class="rw-note">
                    🧊 {{ __('No streak freezes. Miss a day and your :n-day streak resets.', ['n' => $streak]) }}
                    <a href="#shop" class="js-tab-link">{{ __('Get one') }}</a>
                </p>
            @endif

            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __("Today's quests") }}</h2>
                        <p class="dx-section__sub">{{ $claimed }} / {{ $board->count() }} {{ __('claimed') }}</p>
                    </div>
                </div>

                @if($board->isEmpty())
                    <p class="dx-section__sub">{{ __('Quests will appear here shortly.') }}</p>
                @else
                    <div class="rw-quests" id="rw-quests">
                        @foreach($board as $quest)
                            @php
                                $def = $quests[$quest->quest_key] ?? null;
                                if (!$def) { continue; }
                                $done = $quest->completed_at !== null;
                                $claimedQ = $quest->claimed_at !== null;
                            @endphp
                            <article class="rw-quest {{ $claimedQ ? 'is-claimed' : ($done ? 'is-ready' : '') }}" data-quest-id="{{ $quest->id }}">
                                <span class="rw-quest__icon" aria-hidden="true">{{ $def['icon'] }}</span>
                                <div class="rw-quest__body">
                                    <h3 class="rw-quest__label">{{ __($def['label']) }}</h3>
                                    <div class="rw-quest__track" role="progressbar" aria-valuenow="{{ $quest->percent }}" aria-valuemin="0" aria-valuemax="100">
                                        <div class="rw-quest__fill" style="width: {{ $quest->percent }}%"></div>
                                    </div>
                                    <p class="rw-quest__meta">
                                        <span>{{ $quest->progress }} / {{ $quest->target }}</span>
                                        <span>+{{ $def['xp'] }} XP · +{{ $def['sparks'] }} ✨</span>
                                    </p>
                                </div>
                                <div class="rw-quest__action">
                                    @if($claimedQ)
                                        <span class="rw-chip rw-chip--done">✓ {{ __('Claimed') }}</span>
                                    @elseif($done)
                                        <button type="button" class="dx-btn dx-btn--primary dx-btn--sm js-claim-quest">{{ __('Claim') }}</button>
                                    @else
                                        <span class="rw-chip">{{ $quest->target - $quest->progress }} {{ __('to go') }}</span>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                    <div class="rw-sweep {{ $swept ? 'is-done' : '' }}">
                        🏁
                        @if($swept)
                            {{ __('Board cleared. Bonus and a Gold Drop are yours.') }}
                        @else
                            {{ __('Clear all :n for +50 XP, +25 ✨ and a Gold Drop.', ['n' => $board->count()]) }}
                        @endif
                    </div>
                @endif
            </section>
        </div>

        {{-- =============================================================== Games --}}
        <div class="rw-panel" id="rw-panel-games" data-rw-panel="games" hidden>
            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Games') }}</h2>
                        <p class="dx-section__sub">{{ __('Free to play. Plays reset every day.') }}</p>
                    </div>
                </div>
                <div class="rw-games">
                    @foreach($games as $key => $game)
                        <article class="rw-game rw-game--{{ $key }}">
                            <span class="rw-game__art" aria-hidden="true">{{ $game['icon'] }}</span>
                            <div class="rw-game__body">
                                <h3 class="rw-game__title">{{ __($game['label']) }}</h3>
                                <p class="rw-game__blurb">{{ __($game['blurb']) }}</p>
                                <p class="rw-game__prize">{{ $key === 'tap' ? __('Up to +40 XP · +12 ✨') : __('Up to +40 XP · +12 ✨') }}</p>
                            </div>
                            <div class="rw-game__foot">
                                <span class="rw-chip js-plays-left" data-game="{{ $key }}">
                                    {{ trans_choice('{0} No plays left|{1} :count play left|[2,*] :count plays left', $playsLeft[$key], ['count' => $playsLeft[$key]]) }}
                                </span>
                                <button type="button" class="dx-btn dx-btn--primary dx-btn--sm js-play" data-game="{{ $key }}"
                                        {{ $playsLeft[$key] > 0 ? '' : 'disabled' }}>{{ __('Play') }}</button>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <p class="rw-links">
                <a href="{{ route('gamification.leaderboard') }}" class="dx-btn dx-btn--ghost dx-btn--sm">🏆 {{ __('Leaderboard') }}</a>
                <a href="{{ route('gamification.achievements') }}" class="dx-btn dx-btn--ghost dx-btn--sm">🏅 {{ __('Achievements') }} ({{ $achievementCount }})</a>
            </p>
        </div>

        {{-- =============================================================== Drops --}}
        <div class="rw-panel" id="rw-panel-drops" data-rw-panel="drops" hidden>
            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Your drops') }}</h2>
                        <p class="dx-section__sub" id="rw-drop-count">{{ $drops->count() }} {{ __('unopened') }}</p>
                    </div>
                </div>

                <div class="rw-pity">
                    <div class="dx-progress"><div class="dx-progress__fill rw-pity__fill" style="width: {{ (int) round(($pityThreshold - $pity) / max(1, $pityThreshold) * 100) }}%"></div></div>
                    <p class="dx-section__sub">
                        @if($pity <= 0)
                            ⭐ {{ __('Your next drop is a guaranteed Rare or better.') }}
                        @else
                            {{ __('Guaranteed Rare or better in :n drops', ['n' => $pity]) }}
                        @endif
                    </p>
                </div>

                <div class="rw-drops" id="rw-drops">
                    @forelse($drops as $drop)
                        @php $tier = $tiers[$drop->tier] ?? $tiers['bronze']; @endphp
                        <button type="button" class="rw-drop rw-drop--{{ $drop->tier }} js-open-drop" data-drop-id="{{ $drop->id }}">
                            <span class="rw-drop__icon" aria-hidden="true">{{ $tier['icon'] }}</span>
                            <span class="rw-drop__label">{{ __($tier['label']) }}</span>
                            <span class="rw-drop__cta">{{ __('Tap to open') }}</span>
                        </button>
                    @empty
                        <p class="dx-section__sub" id="rw-drops-empty">{{ __('No unopened drops. Clear your quests, spin the wheel, or level up.') }}</p>
                    @endforelse
                </div>
            </section>

            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Trade sparks for drops') }}</h2>
                        <p class="dx-section__sub">{{ __('Sparks are earned by playing — they can never be bought.') }}</p>
                    </div>
                </div>
                <div class="rw-shelves">
                    @foreach($tiers as $key => $tier)
                        @php $total = array_sum($tier['weights']); @endphp
                        <article class="rw-shelf rw-shelf--{{ $key }}">
                            <span class="rw-shelf__icon" aria-hidden="true">{{ $tier['icon'] }}</span>
                            <h3 class="rw-shelf__label">{{ __($tier['label']) }}</h3>
                            <ul class="rw-shelf__odds">
                                @foreach($tier['weights'] as $rarity => $weight)
                                    <li class="rw-odd--{{ $rarity }}"><span>{{ ucfirst($rarity) }}</span><span>{{ rtrim(rtrim(number_format($weight / $total * 100, 1), '0'), '.') }}%</span></li>
                                @endforeach
                            </ul>
                            <button type="button" class="dx-btn dx-btn--ghost dx-btn--sm dx-btn--block js-buy-drop"
                                    data-tier="{{ $key }}" data-cost="{{ $tier['cost'] }}" {{ $sparks < $tier['cost'] ? 'disabled' : '' }}>
                                {{ $tier['cost'] }} ✨
                            </button>
                        </article>
                    @endforeach
                </div>
            </section>
        </div>

        {{-- ================================================================ Shop --}}
        <div class="rw-panel" id="rw-panel-shop" data-rw-panel="shop" hidden>
            <section class="rw-balance">
                <div>
                    <span class="dx-eyebrow">{{ __('Your balance') }}</span>
                    <span class="rw-balance__value" id="rw-credits">{{ $money($credits) }}</span>
                </div>
                <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'deposit']) }}" class="dx-btn dx-btn--money">{{ __('Add funds') }}</a>
            </section>

            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Perks') }}</h2>
                        <p class="dx-section__sub">{{ __('Fixed prices, no surprises. Spins, games and drops stay free — they are never for sale.') }}</p>
                    </div>
                </div>
                <div class="rw-perks">
                    @foreach($perks as $key => $perk)
                        @php
                            $isFrame = $perk['grant']['type'] === 'frame';
                            $owned = $isFrame && $ownedFrames->contains($perk['grant']['key']);
                        @endphp
                        <article class="rw-perk {{ $isFrame ? 'rw-perk--frame' : '' }}">
                            @if($isFrame)
                                <span class="rw-avatar rw-avatar--sm has-frame frame-{{ \Illuminate\Support\Str::after($perk['grant']['key'], 'flair_') }}" aria-hidden="true">
                                    <img src="{{ $me->avatar }}" alt="">
                                </span>
                            @else
                                <span class="rw-perk__icon" aria-hidden="true">{{ $perk['icon'] }}</span>
                            @endif
                            <div class="rw-perk__body">
                                <h3 class="rw-perk__title">
                                    {{ __($perk['label']) }}
                                    @if(!empty($perk['tag']))<span class="rw-tag">{{ __($perk['tag']) }}</span>@endif
                                </h3>
                                <p class="rw-perk__blurb">{{ __($perk['blurb']) }}</p>
                            </div>
                            @if($owned)
                                <span class="rw-chip rw-chip--done">{{ __('Owned') }}</span>
                            @else
                                <button type="button" class="dx-btn dx-btn--money dx-btn--sm js-buy-perk" data-perk="{{ $key }}"
                                        data-confirm="{{ __('Buy :item for :price?', ['item' => __($perk['label']), 'price' => $money($perk['price'])]) }}">
                                    {{ $money($perk['price']) }}
                                </button>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>

            @if($ownedFrames->isNotEmpty())
                <section>
                    <div class="dx-section__head">
                        <div>
                            <h2 class="dx-section__title">{{ __('Your frames') }}</h2>
                            <p class="dx-section__sub">{{ __('Shown on your profile picture.') }}</p>
                        </div>
                    </div>
                    <div class="rw-frames">
                        @foreach($ownedFrames as $frameKey)
                            @php $wearing = ($me->equipped_frame ?? null) === $frameKey; @endphp
                            <button type="button" class="rw-frame js-equip {{ $wearing ? 'is-wearing' : '' }}" data-frame="{{ $wearing ? '' : $frameKey }}">
                                <span class="rw-avatar rw-avatar--sm has-frame frame-{{ \Illuminate\Support\Str::after($frameKey, 'flair_') }}"><img src="{{ $me->avatar }}" alt=""></span>
                                <span>{{ __($rewards[$frameKey]['name'] ?? $frameKey) }}</span>
                                <span class="rw-frame__state">{{ $wearing ? __('Wearing · tap to remove') : __('Wear') }}</span>
                            </button>
                        @endforeach
                    </div>
                </section>
            @endif

            @php $collection = $inventory->reject(fn ($i) => in_array($i->reward_key, $frames, true)); @endphp
            @if($collection->isNotEmpty())
                <section>
                    <div class="dx-section__head"><h2 class="dx-section__title">{{ __('Collection') }}</h2></div>
                    <div class="rw-inventory">
                        @foreach($collection as $item)
                            @php $def = $rewards[$item->reward_key] ?? null; @endphp
                            @if($def)
                                <span class="rw-chip">{{ $def['icon'] }} {{ __($def['name']) }}@if($item->quantity > 1) ×{{ $item->quantity }}@endif</span>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

    </main>
</div>

{{-- Opening a drop: the crate is airdropped in, lands, and bursts open.
     Every step is timed in CSS (supply-drop.css); rewards.js only fills in the
     reward and starts it. --}}
<div class="rw-air" id="rw-air" hidden aria-live="polite">
    <button type="button" class="rw-air__skip" id="rw-air-skip">{{ __('Skip') }}</button>
    <div class="rw-air__scene" role="dialog" aria-modal="true">
        <div class="rw-air__ground" aria-hidden="true"></div>

        <div class="rw-air__dust" aria-hidden="true"><span></span><span></span><span></span></div>
        <div class="rw-air__beam" aria-hidden="true"></div>

        <div class="rw-air__rig" aria-hidden="true">
            <div class="rw-air__swing">
                <div class="rw-air__chute">
                    <svg viewBox="0 0 190 120" fill="none" aria-hidden="true">
                        <path d="M5 54a90 90 0 0 1 180 0c0 6-8 10-18 10H23C13 64 5 60 5 54Z" fill="#d8d6d0"/>
                        <path d="M5 54a90 90 0 0 1 45-46c-8 14-12 30-12 46 0 6-4 10-10 10h-5C13 64 5 60 5 54Z" fill="#b9b6ae"/>
                        <path d="M185 54a90 90 0 0 0-45-46c8 14 12 30 12 46 0 6 4 10 10 10h5c10 0 18-4 18-10Z" fill="#b9b6ae"/>
                        <path d="M95 8c-10 14-16 30-16 46 0 6 3 10 7 10h18c4 0 7-4 7-10 0-16-6-32-16-46Z" fill="#c7c4bc"/>
                        <path d="M28 64 78 112M95 64v48M162 64l-50 48" stroke="rgba(255,255,255,.5)" stroke-width="2"/>
                    </svg>
                </div>
                <div class="rw-air__crate-wrap">
                    <div class="rw-air__crate">
                        <div class="rw-air__lid"></div>
                        <div class="rw-air__body">
                            <span class="rw-air__stencil" id="rw-air-stencil">📦</span>
                        </div>
                        <div class="rw-air__seam"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="rw-air__flash" aria-hidden="true"></div>

        <div class="rw-air__loot">
            <div class="rw-air__icon" id="rw-air-icon">🎁</div>
            <p class="rw-air__rarity" id="rw-air-rarity"></p>
            <p class="rw-air__name" id="rw-air-name"></p>
        </div>
        <div class="rw-air__actions">
            <button type="button" class="dx-btn dx-btn--primary dx-btn--block" id="rw-air-close">{{ __('Nice') }}</button>
        </div>
    </div>
</div>

{{-- Reveal: spin prizes. --}}
<div class="rw-reveal" id="rw-reveal" hidden aria-live="polite">
    <div class="rw-reveal__box" role="dialog" aria-modal="true">
        <div class="rw-reveal__burst" id="rw-reveal-burst">🎁</div>
        <p class="rw-reveal__rarity" id="rw-reveal-rarity"></p>
        <p class="rw-reveal__name" id="rw-reveal-name"></p>
        <button type="button" class="dx-btn dx-btn--primary dx-btn--block" id="rw-reveal-close">{{ __('Nice') }}</button>
    </div>
</div>

{{-- Game stage. --}}
<div class="rw-stage" id="rw-stage" hidden>
    <div class="rw-stage__box" role="dialog" aria-modal="true" aria-labelledby="rw-stage-title">
        <header class="rw-stage__head">
            <h2 class="rw-stage__title" id="rw-stage-title"></h2>
            <span class="rw-stage__hud" id="rw-stage-hud"></span>
            <button type="button" class="rw-stage__close" id="rw-stage-close" aria-label="{{ __('Close') }}">✕</button>
        </header>
        <div class="rw-stage__timer" id="rw-stage-timer"><span></span></div>
        <div class="rw-stage__arena" id="rw-stage-arena"></div>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        window.rwRoutes = {
            claim: "{{ url('/rewards/quests') }}",
            open: "{{ url('/rewards/drops') }}",
            buy: "{{ route('engagement.drop.buy') }}",
            spin: "{{ route('engagement.spin') }}",
            games: "{{ url('/rewards/games') }}",
            shop: "{{ url('/rewards/shop') }}",
            frame: "{{ route('engagement.frame.equip') }}"
        };
        window.rwText = {
            claimed: @json(__('Claimed')),
            unopened: @json(__('unopened')),
            error: @json(__('Something went wrong. Try again.')),
            playsLeft: @json(__(':count left')),
            noPlays: @json(__('No plays left')),
            youWon: @json(__('You won')),
            tapGo: @json(__('Tap the targets!')),
            score: @json(__('Score')),
            moves: @json(__('Moves')),
            done: @json(__('Nice!')),
            playAgain: @json(__('Play again')),
            close: @json(__('Close')),
            spent: @json(__('You spun today')),
            nextSpin: @json(__('Your next free spin is ready in')),
            games: @json(['tap' => __('Tap Rush'), 'memory' => __('Memory Flip')])
        };
    </script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <script src="{{ asset('js/rewards.js') }}?v=20260923a"></script>
@endsection
