@extends('layouts.generic')

@section('page_title', __('Challenges'))

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/challenges.css') }}?v=20260817a">
@endsection

@section('content')
<div class="ch-page {{ $isDarkTheme ? 'ch-page--dark' : 'ch-page--light' }}">
<div class="ch-container">

    <header class="ch-header">
        <div>
            <h1 class="ch-header__title">{{ __('Challenges') }}</h1>
            <p class="ch-header__sub">{{ __('Fans put up the money. Creators do the challenge. Watch what happens.') }}</p>
        </div>
        <div class="ch-header__actions">
            <a href="{{ route('custom-requests.my-requests') }}" class="ch-btn ch-btn--ghost">{{ __('My requests') }}</a>
            <button type="button" class="ch-btn ch-btn--primary" onclick="CustomRequest.showCreateModal()">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                {{ __('Request a challenge') }}
            </button>
        </div>
    </header>

    <section class="ch-stats">
        <div class="ch-stat">
            <span class="ch-stat__value">{{ number_format($stats['watch']) }}</span>
            <span class="ch-stat__label">{{ __('To watch') }}</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__value">{{ number_format($stats['open']) }}</span>
            <span class="ch-stat__label">{{ __('Open now') }}</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__value">${{ number_format($stats['raised'], 2) }}</span>
            <span class="ch-stat__label">{{ __('Total raised') }}</span>
        </div>
        <div class="ch-stat">
            <span class="ch-stat__value">{{ number_format($stats['completed']) }}</span>
            <span class="ch-stat__label">{{ __('Completed') }}</span>
        </div>
    </section>

    <section class="ch-toolbar">
        <form action="{{ route('custom-requests.marketplace') }}" method="GET" class="ch-search" role="search">
            <input type="hidden" name="tab" value="{{ $tab }}">
            <span class="ch-search__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5" stroke-linecap="round"/></svg>
            </span>
            <input type="text" name="search" class="ch-search__input" value="{{ $search }}"
                   autocomplete="off" placeholder="{{ __('Search challenges...') }}"
                   aria-label="{{ __('Search challenges') }}">
            <button type="submit" class="ch-search__btn">{{ __('Search') }}</button>
        </form>

        <nav class="ch-tabs">
            @foreach(['watch' => __('Watch'), 'open' => __('Fund a challenge'), 'funded' => __('Completed')] as $key => $label)
                <a href="{{ route('custom-requests.marketplace', array_filter(['search' => $search, 'tab' => $key])) }}"
                   class="ch-tab {{ $tab === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </nav>
    </section>

    {{-- Delivered challenge videos: the reason to be on this page at all. --}}
    @if($delivered->count() && $tab === 'watch')
        <section class="ch-section">
            <div class="ch-section__head">
                <h2 class="ch-section__title">{{ __('Latest challenges') }}</h2>
                <span class="ch-section__count">{{ $delivered->count() }}</span>
            </div>
            <div class="ch-grid">
                @foreach($delivered as $cr)
                    @include('custom-requests.partials.challenge-card', ['cr' => $cr])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Almost funded — one more contribution tips these over. --}}
    @if($almost->count() && $search === '')
        <section class="ch-section">
            <div class="ch-section__head">
                <h2 class="ch-section__title">{{ __('Almost funded') }}</h2>
                <span class="ch-section__count">{{ __('closest to their goal') }}</span>
            </div>
            <div class="ch-rail">
                @foreach($almost as $cr)
                    @include('custom-requests.partials.challenge-card', ['cr' => $cr, 'wide' => true])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Open challenges looking for backers --}}
    @if($open->count() && $tab !== 'funded')
        <section class="ch-section">
            <div class="ch-section__head">
                <h2 class="ch-section__title">{{ __('Open for funding') }}</h2>
                <a href="{{ route('custom-requests.marketplace', ['tab' => 'open']) }}" class="ch-section__count">{{ __('See all') }}</a>
            </div>
            <div class="ch-grid">
                @foreach($open->take(8) as $cr)
                    @include('custom-requests.partials.challenge-card', ['cr' => $cr, 'wide' => true])
                @endforeach
            </div>
        </section>
    @endif

    {{-- Nothing anywhere --}}
    @if(!$delivered->count() && !$open->count())
        <div class="ch-empty">
            <span class="ch-empty__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="m10 8 6 4-6 4V8z"/><rect x="2" y="3" width="20" height="18" rx="2"/></svg>
            </span>
            <h3>{{ $search !== '' ? __('Nothing matches that search') : __('No challenges yet') }}</h3>
            <p>
                {{ $search !== ''
                    ? __('Try a different word.')
                    : __('Dare a creator to do something. Put money behind it, and everyone gets to watch the result.') }}
            </p>
            @if($search !== '')
                <a href="{{ route('custom-requests.marketplace') }}" class="ch-btn ch-btn--ghost">{{ __('Clear search') }}</a>
            @else
                <button type="button" class="ch-btn ch-btn--primary" onclick="CustomRequest.showCreateModal()">{{ __('Request a challenge') }}</button>
            @endif
        </div>
    @endif

    @if($requests->hasPages())
        <div class="ch-pagination">{{ $requests->links() }}</div>
    @endif

</div>
</div>

{{-- The create-request modal is already rendered by layouts/generic.blade.php — including it
     again here would duplicate #createCustomRequestModal and break the modal. --}}

<script>
/* Hover-to-preview: play the clip muted while the pointer is over the tile, like a
   YouTube browse grid. Only bound on devices that actually have a hover pointer, so
   phones are left to their poster images and don't burn data auto-playing. */
(function () {
    if (!window.matchMedia || !window.matchMedia('(hover: hover)').matches) return;

    document.querySelectorAll('.ch-card__media video').forEach(function (video) {
        var card = video.closest('.ch-card');
        if (!card) return;

        card.addEventListener('mouseenter', function () {
            video.currentTime = 0;
            var p = video.play();
            if (p && p.catch) p.catch(function () {});
        });

        card.addEventListener('mouseleave', function () {
            video.pause();
            video.currentTime = 0;
        });
    });
})();
</script>
@endsection
