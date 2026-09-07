@extends('layouts.user-no-nav')

@section('page_title', __('Rewards'))

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $sparks   = (int) ($me->sparks ?? 0);
    $streak   = (int) ($me->streak_count ?? 0);
    $freezes  = (int) ($me->streak_freezes ?? 0);
    $level    = (int) ($me->level ?? 1);

    $claimable = $board->filter(fn ($q) => $q->completed_at !== null && $q->claimed_at === null)->count();
    $claimed   = $board->filter(fn ($q) => $q->claimed_at !== null)->count();
    $swept     = $board->count() > 0 && $claimed === $board->count();
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/gamification.css') }}?v=20260907a">
    <link rel="stylesheet" href="{{ asset('css/pages/rewards.css') }}?v=20260907a">
@endsection

@section('content')
<div class="gm-page {{ $isDarkTheme ? 'gm-page--dark' : 'gm-page--light' }}">
<div class="gm-container rw">

    {{-- ------------------------------------------------------------------ Hero --}}
    <header class="rw-hero">
        <div class="rw-hero__level">
            {{-- The ring is the level; the arc is progress toward the next one. --}}
            <div class="rw-ring" style="--pct: {{ $progress['percent'] }}">
                <div class="rw-ring__inner">
                    <span class="rw-ring__label">{{ __('Level') }}</span>
                    <span class="rw-ring__value">{{ $level }}</span>
                </div>
            </div>
            <p class="rw-hero__next">
                <strong id="rw-xp-into">{{ number_format($progress['into']) }}</strong>
                / {{ number_format($progress['span']) }} {{ __('XP to level') }} {{ $progress['next_level'] }}
            </p>
        </div>

        <ul class="rw-hero__stats">
            <li class="rw-stat rw-stat--sparks">
                <span class="rw-stat__icon">✨</span>
                <span class="rw-stat__value" id="rw-sparks">{{ number_format($sparks) }}</span>
                <span class="rw-stat__label">{{ __('Sparks') }}</span>
            </li>
            <li class="rw-stat rw-stat--streak">
                <span class="rw-stat__icon">🔥</span>
                <span class="rw-stat__value" id="rw-streak">{{ $streak }}</span>
                <span class="rw-stat__label">{{ __('Day streak') }}</span>
            </li>
            <li class="rw-stat rw-stat--freeze">
                <span class="rw-stat__icon">🧊</span>
                <span class="rw-stat__value" id="rw-freezes">{{ $freezes }}</span>
                <span class="rw-stat__label">{{ __('Streak freezes') }}</span>
            </li>
            <li class="rw-stat rw-stat--today">
                <span class="rw-stat__icon">📈</span>
                <span class="rw-stat__value">+{{ number_format($today['xp']) }}</span>
                <span class="rw-stat__label">{{ __('XP today') }}</span>
            </li>
        </ul>
    </header>

    @if($freezes === 0 && $streak >= 3)
        <p class="rw-note rw-note--warn">
            🧊 {{ __('You have no streak freezes. Miss one day and your :n-day streak resets.', ['n' => $streak]) }}
            <span class="rw-note__hint">{{ __('Freezes come out of drops.') }}</span>
        </p>
    @endif

    {{-- ---------------------------------------------------------------- Quests --}}
    <section class="rw-section">
        <div class="rw-section__head">
            <h2 class="rw-section__title">🎯 {{ __("Today's quests") }}</h2>
            <span class="rw-section__meta">
                {{ $claimed }} / {{ $board->count() }} {{ __('claimed') }}
            </span>
        </div>

        @if($board->isEmpty())
            <p class="gm-empty">{{ __('Quests will appear here shortly.') }}</p>
        @else
            <div class="rw-quests" id="rw-quests">
                @foreach($board as $quest)
                    @php
                        $def = $quests[$quest->quest_key] ?? null;
                        if (!$def) { continue; }
                        $done    = $quest->completed_at !== null;
                        $claimedQ = $quest->claimed_at !== null;
                        $pct     = $quest->percent;
                    @endphp
                    <article class="rw-quest {{ $claimedQ ? 'is-claimed' : ($done ? 'is-ready' : '') }}"
                             data-quest-id="{{ $quest->id }}">
                        <span class="rw-quest__icon" aria-hidden="true">{{ $def['icon'] }}</span>

                        <div class="rw-quest__body">
                            <h3 class="rw-quest__label">{{ __($def['label']) }}</h3>
                            <div class="rw-quest__track" role="progressbar"
                                 aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                <div class="rw-quest__fill" style="width: {{ $pct }}%"></div>
                            </div>
                            <p class="rw-quest__count">
                                {{ $quest->progress }} / {{ $quest->target }}
                                <span class="rw-quest__reward">+{{ $def['xp'] }} XP · +{{ $def['sparks'] }} ✨</span>
                            </p>
                        </div>

                        <div class="rw-quest__action">
                            @if($claimedQ)
                                <span class="rw-chip rw-chip--done">✓ {{ __('Claimed') }}</span>
                            @elseif($done)
                                <button type="button" class="rw-btn rw-btn--claim js-claim-quest">
                                    {{ __('Claim') }}
                                </button>
                            @else
                                <span class="rw-chip">{{ $quest->target - $quest->progress }} {{ __('to go') }}</span>
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="rw-sweep {{ $swept ? 'is-done' : '' }}">
                @if($swept)
                    <span>🏁 {{ __('Board cleared. Bonus and a Gold Drop are yours.') }}</span>
                @else
                    <span>🏁 {{ __('Clear all :n for +50 XP, +25 ✨ and a Gold Drop.', ['n' => $board->count()]) }}</span>
                @endif
            </div>
        @endif
    </section>

    {{-- ----------------------------------------------------------------- Drops --}}
    <section class="rw-section">
        <div class="rw-section__head">
            <h2 class="rw-section__title">🎁 {{ __('Your drops') }}</h2>
            <span class="rw-section__meta" id="rw-drop-count">
                {{ $drops->count() }} {{ __('unopened') }}
            </span>
        </div>

        <div class="rw-pity">
            <div class="rw-pity__track">
                <div class="rw-pity__fill"
                     style="width: {{ (int) round(($pityThreshold - $pity) / max(1, $pityThreshold) * 100) }}%"></div>
            </div>
            <p class="rw-pity__label">
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
                <button type="button" class="rw-drop rw-drop--{{ $drop->tier }} js-open-drop"
                        data-drop-id="{{ $drop->id }}">
                    <span class="rw-drop__icon">{{ $tier['icon'] }}</span>
                    <span class="rw-drop__label">{{ __($tier['label']) }}</span>
                    <span class="rw-drop__cta">{{ __('Open') }}</span>
                </button>
            @empty
                <p class="gm-empty" id="rw-drops-empty">
                    {{ __('No unopened drops. Clear your quests, hit a streak milestone, or level up.') }}
                </p>
            @endforelse
        </div>
    </section>

    {{-- ------------------------------------------------------------------ Shop --}}
    <section class="rw-section">
        <div class="rw-section__head">
            <h2 class="rw-section__title">🛒 {{ __('Trade sparks for drops') }}</h2>
        </div>

        <p class="rw-note">
            {{ __('Sparks are earned, never bought. There is no way to pay money for a drop.') }}
        </p>

        <div class="rw-shop">
            @foreach($tiers as $key => $tier)
                <article class="rw-shelf rw-shelf--{{ $key }}">
                    <span class="rw-shelf__icon">{{ $tier['icon'] }}</span>
                    <h3 class="rw-shelf__label">{{ __($tier['label']) }}</h3>
                    <ul class="rw-shelf__odds">
                        @php $total = array_sum($tier['weights']); @endphp
                        @foreach($tier['weights'] as $rarity => $weight)
                            <li class="rw-odd rw-odd--{{ $rarity }}">
                                <span>{{ ucfirst($rarity) }}</span>
                                <span>{{ rtrim(rtrim(number_format($weight / $total * 100, 1), '0'), '.') }}%</span>
                            </li>
                        @endforeach
                    </ul>
                    <button type="button"
                            class="rw-btn rw-btn--buy js-buy-drop"
                            data-tier="{{ $key }}"
                            data-cost="{{ $tier['cost'] }}"
                            {{ $sparks < $tier['cost'] ? 'disabled' : '' }}>
                        {{ $tier['cost'] }} ✨
                    </button>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ------------------------------------------------------------- Inventory --}}
    @if($inventory->isNotEmpty())
        <section class="rw-section">
            <div class="rw-section__head">
                <h2 class="rw-section__title">🎒 {{ __('Collection') }}</h2>
            </div>
            <div class="rw-inventory">
                @foreach($inventory as $item)
                    @php $def = $rewards[$item->reward_key] ?? null; @endphp
                    @if($def)
                        <div class="rw-item rw-item--{{ $def['rarity'] }}">
                            <span class="rw-item__icon">{{ $def['icon'] }}</span>
                            <span class="rw-item__name">{{ __($def['name']) }}</span>
                            @if($item->quantity > 1)
                                <span class="rw-item__qty">×{{ $item->quantity }}</span>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif

    <p class="rw-links">
        <a href="{{ route('gamification.achievements') }}" class="gm-tab">
            🏅 {{ __('Achievements') }} ({{ $achievementCount }})
        </a>
        <a href="{{ route('gamification.leaderboard') }}" class="gm-tab">
            🏆 {{ __('Leaderboard') }}
        </a>
    </p>

</div>
</div>

{{-- Reveal overlay. Populated by JS when a drop is opened. --}}
<div class="rw-reveal" id="rw-reveal" hidden aria-live="polite">
    <div class="rw-reveal__box" role="dialog" aria-modal="true">
        <div class="rw-reveal__burst" id="rw-reveal-burst">🎁</div>
        <p class="rw-reveal__rarity" id="rw-reveal-rarity"></p>
        <p class="rw-reveal__name" id="rw-reveal-name"></p>
        <button type="button" class="rw-btn rw-btn--claim" id="rw-reveal-close">{{ __('Nice') }}</button>
    </div>
</div>
@endsection

@section('scripts')
    <script>
        window.rwRoutes = {
            claim: "{{ url('/rewards/quests') }}",
            open:  "{{ url('/rewards/drops') }}",
            buy:   "{{ route('engagement.drop.buy') }}"
        };
        window.rwText = {
            claimed:  @json(__('Claimed')),
            toGo:     @json(__('to go')),
            unopened: @json(__('unopened')),
            error:    @json(__('Something went wrong. Try again.'))
        };
    </script>
    {{-- Loaded here rather than globally: the reveal is the only thing on this page
         that needs it, and the celebration partial only pulls it when it has events. --}}
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.2/dist/confetti.browser.min.js"></script>
    <script src="{{ asset('js/rewards.js') }}?v=20260907a"></script>
@endsection
