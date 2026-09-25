@extends('layouts.user-no-nav')

@section('page_title', __('Achievements'))

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $unlockedCount = $unlocked->count();
    $totalCount = count($all);
    $completion = $totalCount > 0 ? (int) round($unlockedCount / $totalCount * 100) : 0;
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/gamification.css') }}?v=20260817a">
@endsection

@section('content')
<div class="gm-page {{ $isDarkTheme ? 'gm-page--dark' : 'gm-page--light' }}">
<div class="gm-container">

    <header class="gm-hero">
        <h1 class="gm-hero__title">🏅 {{ __('Achievements') }}</h1>
        <p class="gm-hero__sub">{{ $unlockedCount }} / {{ $totalCount }} {{ __('unlocked') }} · {{ $completion }}%</p>

        <div class="gm-hero__meter" role="progressbar" aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
            <div class="gm-hero__meter-fill" style="width: {{ $completion }}%"></div>
        </div>

        <ul class="gm-hero__stats">
            <li>
                <span class="gm-hero__stat-value">{{ number_format($me->xp ?? 0) }}</span>
                <span class="gm-hero__stat-label">{{ __('XP') }}</span>
            </li>
            <li>
                <span class="gm-hero__stat-value">{{ $me->level ?? 1 }}</span>
                <span class="gm-hero__stat-label">{{ __('Level') }}</span>
            </li>
            <li>
                <span class="gm-hero__stat-value">🔥 {{ $me->streak_count ?? 0 }}</span>
                <span class="gm-hero__stat-label">{{ __('Day streak') }}</span>
            </li>
            <li>
                <span class="gm-hero__stat-value">{{ number_format($totalXp) }}</span>
                <span class="gm-hero__stat-label">{{ __('XP from badges') }}</span>
            </li>
        </ul>
    </header>

    <p style="margin:0;">
        <a href="{{ route('gamification.leaderboard') }}" class="gm-tab" style="display:inline-block;flex:none;">🏆 {{ __('View leaderboard') }}</a>
    </p>

    @foreach($grouped as $catKey => $group)
        @php
            $groupUnlocked = collect($group['items'])->filter(fn ($a, $k) => $unlocked->has($k))->count();
        @endphp
        <section class="gm-section">
            <div class="gm-section__head">
                <h2 class="gm-section__title">{{ __($group['label']) }}</h2>
                <span class="gm-section__count">{{ $groupUnlocked }} / {{ count($group['items']) }}</span>
            </div>

            <div class="gm-grid">
                @foreach($group['items'] as $key => $a)
                    @php
                        $isUnlocked = $unlocked->has($key);
                        $p = $progress[$key] ?? ['current' => 0, 'target' => 1, 'pct' => 0];
                        // Only worth showing a bar for multi-step goals still in progress.
                        $showBar = !$isUnlocked && $p['target'] > 1;
                    @endphp
                    <article class="gm-card {{ $isUnlocked ? 'gm-card--unlocked' : 'gm-card--locked' }}">
                        <div class="gm-card__icon">{{ $a['icon'] }}</div>
                        <div class="gm-card__name">{{ __($a['name']) }}</div>
                        <div class="gm-card__desc">{{ __($a['desc']) }}</div>

                        @if($showBar)
                            <div class="gm-progress">
                                <div class="gm-progress__track" role="progressbar"
                                     aria-valuenow="{{ $p['pct'] }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="gm-progress__fill" style="width: {{ $p['pct'] }}%"></div>
                                </div>
                                <span class="gm-progress__label">
                                    {{ $p['current'] }} / {{ $p['target'] }}{{ $a['unit'] ? ' ' . __($a['unit']) : '' }}
                                </span>
                            </div>
                        @endif

                        <div class="gm-card__status {{ $isUnlocked ? 'gm-card__status--unlocked' : 'gm-card__status--xp' }}">
                            @if($isUnlocked)
                                ✓ {{ __('Unlocked') }}
                            @else
                                +{{ $a['xp'] }} XP
                            @endif
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endforeach

</div>
</div>
@endsection
