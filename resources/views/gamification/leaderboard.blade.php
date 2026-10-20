@extends('layouts.user-no-nav')

@section('page_title', __('Leaderboard'))

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    // Metric shown per row depends on the active tab.
    $metricFor = function ($u) use ($tab) {
        if ($tab === 'streaks') return '🔥 ' . (int) ($u->streak_count ?? 0);
        if ($tab === 'level')   return __('Lv') . ' ' . (int) ($u->level ?? 1);
        return number_format((int) ($u->xp ?? 0)) . ' XP';
    };

    $podium = $rows->take(3);
    $rest = $rows->slice(3);
    $medals = ['🥇', '🥈', '🥉'];
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/gamification.css') }}?v=20260817a">
@endsection

@section('content')
<div class="gm-page {{ $isDarkTheme ? 'gm-page--dark' : 'gm-page--light' }}">
<div class="gm-container">

    <header class="gm-hero">
        <h1 class="gm-hero__title">🏆 {{ __('Leaderboard') }}</h1>
        <p class="gm-hero__sub">
            @if($myRank)
                {{ __('Your rank') }}: <strong>#{{ number_format($myRank) }}</strong>
                @if($total) {{ __('of') }} {{ number_format($total) }} @endif
            @else
                {{ __('Climb the ranks by staying active!') }}
            @endif
        </p>

        <ul class="gm-hero__stats">
            <li>
                <span class="gm-hero__stat-value">{{ number_format($me->xp ?? 0) }}</span>
                <span class="gm-hero__stat-label">{{ __('Your XP') }}</span>
            </li>
            <li>
                <span class="gm-hero__stat-value">{{ $me->level ?? 1 }}</span>
                <span class="gm-hero__stat-label">{{ __('Level') }}</span>
            </li>
            <li>
                <span class="gm-hero__stat-value">🔥 {{ $me->streak_count ?? 0 }}</span>
                <span class="gm-hero__stat-label">{{ __('Streak') }}</span>
            </li>
        </ul>
    </header>

    <nav class="gm-tabs">
        @foreach(['xp' => '⭐ ' . __('Top XP'), 'streaks' => '🔥 ' . __('Top Streaks'), 'level' => '🎖️ ' . __('Top Level')] as $key => $label)
            <a href="{{ route('gamification.leaderboard', $key === 'xp' ? [] : ['tab' => $key]) }}"
               class="gm-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
        @endforeach
    </nav>

    @if($rows->count())
        {{-- Podium for the top three --}}
        @if($podium->count() === 3)
            <section class="gm-podium">
                @foreach([1, 0, 2] as $slot)
                    @php $u = $podium[$slot] ?? null; @endphp
                    @if($u)
                        <div class="gm-podium__slot gm-podium__slot--{{ $slot + 1 }}">
                            <span class="gm-podium__medal">{{ $medals[$slot] }}</span>
                            <img src="{{ $u->avatar }}" alt="" class="gm-podium__avatar" loading="lazy">
                            <span class="gm-podium__name">{{ $u->name }}</span>
                            <span class="gm-podium__metric">{{ $metricFor($u) }}</span>
                        </div>
                    @endif
                @endforeach
            </section>
        @endif

        <section class="gm-board">
            {{-- slice() preserves keys, so $i is already the zero-based overall position
                 whether or not the top three were split out into the podium. --}}
            @foreach(($podium->count() === 3 ? $rest : $rows) as $i => $u)
                @php $rank = $i + 1; @endphp
                <div class="gm-row {{ $me && $u->id === $me->id ? 'gm-row--me' : '' }}">
                    <span class="gm-row__rank">
                        @if($rank <= 3 && $podium->count() !== 3)
                            {{ $medals[$rank - 1] }}
                        @else
                            #{{ $rank }}
                        @endif
                    </span>
                    <img src="{{ $u->avatar }}" alt="" class="gm-row__avatar" loading="lazy">
                    <span class="gm-row__meta">
                        <span class="gm-row__name">{{ $u->name }}</span>
                        <span class="gm-row__sub">{{ '@' . $u->username }} · {{ __('Level') }} {{ $u->level ?? 1 }}</span>
                    </span>
                    <span class="gm-row__metric">{{ $metricFor($u) }}</span>
                </div>
            @endforeach
        </section>
    @else
        <div class="gm-empty">{{ __('No rankings yet — be the first!') }}</div>
    @endif

    <p style="margin:0;">
        <a href="{{ route('gamification.achievements') }}" class="gm-tab" style="display:inline-block;flex:none;">🏅 {{ __('View achievements') }}</a>
    </p>

</div>
</div>
@endsection
