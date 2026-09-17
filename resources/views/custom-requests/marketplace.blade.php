@extends('layouts.generic')

@section('page_title', __('Challenges'))

@php
    $items = $requests->getCollection();
    $onFirstPage = $requests->currentPage() === 1;

    // The newest delivered clip whose video is actually on disk leads the Watch tab.
    $spotlight = ($tab === 'watch' && $search === '' && $onFirstPage)
        ? $items->first(fn ($cr) => $cr->delivery_video_url !== null)
        : null;
    $wall = $spotlight ? $items->reject(fn ($cr) => $cr->is($spotlight)) : $items;

    $tabUrl = fn ($key) => route('custom-requests.marketplace', $key === 'watch' ? [] : ['tab' => $key]);

    $dare = Auth::check()
        ? ['label' => __('Dare'), 'onclick' => 'CustomRequest.showCreateModal()']
        : ['label' => __('Dare'), 'href' => route('login')];
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/challenges.css') }}?v=20260917a">
@endsection

@section('content')
<div class="dx ch">

    @include('elements.discover.subnav', [
        'tabs' => array_filter([
            ['label' => __('Watch'), 'href' => $tabUrl('watch'), 'active' => $search === '' && $tab === 'watch'],
            ['label' => __('Fund'), 'href' => $tabUrl('open'), 'active' => $search === '' && $tab === 'open'],
            ['label' => __('Top'), 'href' => $tabUrl('top'), 'active' => $search === '' && $tab === 'top'],
            Auth::check() ? ['label' => __('Mine'), 'href' => route('custom-requests.my-requests'), 'active' => false] : null,
        ]),
        'search' => $search,
        'searchAction' => route('custom-requests.marketplace'),
        'searchLabel' => __('Search challenges'),
        'cta' => $dare,
    ])

    <main class="dx-main">

        {{-- ============ Spotlight ============ --}}
        @if($spotlight)
            @php
                $s = $spotlight;
                $sThumb = $s->delivery_thumbnail_url;
                $sUrl = route('custom-requests.show', $s->id);
                $sBackers = (int) ($s->backers_count ?? 0);
                $sViews = (int) ($s->views_count ?? 0);
            @endphp
            <section class="ch-spot" data-dx-media aria-label="{{ __('Featured challenge') }}">
                <a href="{{ $sUrl }}" class="ch-spot__media" tabindex="-1" aria-hidden="true">
                    <video data-dx-autoplay data-src="{{ $s->delivery_video_url }}"
                           @if($sThumb) poster="{{ $sThumb }}" @endif
                           muted loop playsinline preload="none"></video>
                </a>
                <span class="ch-spot__scrim"></span>
                <span class="ch-spot__play" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M7 4v16l13-8z"/></svg>
                </span>

                <div class="ch-spot__top">
                    <span class="ch-chip ch-chip--win">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5 10 17 19 7"/></svg>
                        {{ __('Challenge done') }}
                    </span>
                    <button type="button" class="ch-spot__sound" data-dx-sound hidden
                            aria-pressed="false" aria-label="{{ __('Sound') }}">
                        <svg class="is-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="m23 9-6 6M17 9l6 6"/>
                        </svg>
                        <svg class="is-unmuted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/>
                        </svg>
                    </button>
                </div>

                <div class="ch-spot__info">
                    @if($s->creator)
                        <a href="{{ route('profile', ['username' => $s->creator->username]) }}" class="ch-spot__by">
                            <img src="{{ $s->creator->avatar }}" alt="" class="dx-avatar">
                            {{ $s->creator->name }}
                            @if($s->delivered_at)
                                <time datetime="{{ $s->delivered_at->toIso8601String() }}">· {{ $s->delivered_at->diffForHumans() }}</time>
                            @endif
                        </a>
                    @endif

                    <h1 class="ch-spot__title"><a href="{{ $sUrl }}">{{ $s->title }}</a></h1>

                    <div class="ch-spot__stats">
                        @if((float) $s->current_amount > 0)
                            <span class="ch-spot__raised"><strong>${{ number_format((float) $s->current_amount, 0) }}</strong> {{ __('raised') }}</span>
                        @endif
                        @if($sBackers > 0)
                            <span><strong>{{ $sBackers }}</strong> {{ trans_choice('{1} backer|[2,*] backers', $sBackers) }}</span>
                        @endif
                        @if($sViews > 0)
                            <span><strong>{{ number_format($sViews) }}</strong> {{ __('views') }}</span>
                        @endif
                    </div>

                    <div class="ch-spot__cta">
                        <a href="{{ $sUrl }}" class="dx-btn dx-btn--primary">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
                            {{ __('Watch it') }}
                        </a>
                    </div>
                </div>
            </section>

            @if($stats['raised'] > 0 || $stats['watch'] > 0)
                <div class="dx-pulse" aria-label="{{ __('Challenge activity') }}">
                    @if($stats['raised'] > 0)
                        <span class="is-money"><strong>${{ number_format($stats['raised'], 0) }}</strong> {{ __('put up by fans') }}</span>
                    @endif
                    @if($stats['watch'] > 0)
                        <span><strong>{{ number_format($stats['watch']) }}</strong> {{ trans_choice('{1} challenge done|[2,*] challenges done', $stats['watch']) }}</span>
                    @endif
                    @if($stats['open'] > 0)
                        <span><strong>{{ number_format($stats['open']) }}</strong> {{ __('waiting on backers') }}</span>
                    @endif
                </div>
            @endif
        @endif

        {{-- ============ Main list for the current tab ============ --}}
        @if($wall->count())
            <section>
                <div class="dx-section__head">
                    <div>
                        @if($search !== '')
                            <h2 class="dx-section__title">{{ __('Results for') }} “{{ $search }}”</h2>
                            <p class="dx-section__sub">{{ trans_choice('{1} :count challenge|[2,*] :count challenges', $requests->total(), ['count' => $requests->total()]) }}</p>
                        @elseif($tab === 'open')
                            <h2 class="dx-section__title">{{ __('Back a challenge') }}</h2>
                            <p class="dx-section__sub">{{ __('Closest to their goal first') }}</p>
                        @elseif($tab === 'top')
                            <h2 class="dx-section__title">{{ __('Biggest challenges') }}</h2>
                            <p class="dx-section__sub">{{ __('Ranked by how much fans put up') }}</p>
                        @else
                            <h2 class="dx-section__title">{{ $spotlight ? __('More to watch') : __('Challenges to watch') }}</h2>
                        @endif
                    </div>
                </div>

                @if($search === '' && $tab === 'open')
                    <div class="ch-fund-grid">
                        @foreach($wall as $cr)
                            @include('custom-requests.partials.fund-card', ['cr' => $cr])
                        @endforeach
                    </div>
                @elseif($search === '' && $tab === 'top')
                    <ol class="ch-rank">
                        @foreach($wall->values() as $i => $cr)
                            @php
                                $rank = $requests->firstItem() + $i;
                                $rThumb = $cr->delivery_thumbnail_url;
                                $rGoal = (float) $cr->goal_amount;
                                $rPct = $rGoal > 0 ? (int) min(100, round((float) $cr->current_amount / $rGoal * 100)) : 0;
                                $rDone = $cr->status === 'completed';
                            @endphp
                            <li>
                                <a href="{{ route('custom-requests.show', $cr->id) }}" class="ch-rank__row">
                                    <span class="ch-rank__num {{ $rank <= 3 ? 'is-top' : '' }}">{{ $rank }}</span>
                                    <span class="ch-rank__thumb">
                                        @if($rThumb)
                                            <img src="{{ $rThumb }}" alt="" loading="lazy">
                                        @else
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                                        @endif
                                    </span>
                                    <span class="ch-rank__body">
                                        <span class="ch-rank__title">{{ $cr->title }}</span>
                                        <span class="ch-rank__meta">
                                            {{ $cr->creator->name ?? __('Unknown') }} ·
                                            @if($rDone)
                                                <span class="is-win">{{ __('Done') }}</span>
                                            @elseif($rGoal > 0)
                                                {{ $rPct }}% {{ __('funded') }}
                                            @else
                                                {{ __('Open') }}
                                            @endif
                                        </span>
                                    </span>
                                    <span class="ch-rank__amount">${{ number_format((float) $cr->current_amount, 0) }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <div class="ch-grid">
                        @foreach($wall as $cr)
                            @include('custom-requests.partials.challenge-card', ['cr' => $cr])
                        @endforeach
                    </div>
                @endif
            </section>
        @elseif(!$spotlight)
            {{-- ============ Empty ============ --}}
            <div class="dx-empty">
                <span class="dx-empty__icon" aria-hidden="true">
                    @if($search !== '')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    @endif
                </span>
                @if($search !== '')
                    <h3>{{ __('Nothing matches that') }}</h3>
                    <p>{{ __('Try another word, or dare a creator to do it.') }}</p>
                    <a href="{{ route('custom-requests.marketplace') }}" class="dx-btn dx-btn--ghost">{{ __('Clear search') }}</a>
                @elseif($tab === 'open')
                    <h3>{{ __('Nothing waiting on backers') }}</h3>
                    <p>{{ __('Every challenge is either funded or done. Start the next one.') }}</p>
                    @include('custom-requests.partials.dare-button')
                @else
                    <h3>{{ __('No challenges yet') }}</h3>
                    <p>{{ __('Dare a creator to do something. Put money behind it, and everyone gets to watch.') }}</p>
                    @include('custom-requests.partials.dare-button')
                @endif
            </div>
        @endif

        @if($requests->hasPages())
            <div class="dx-pagination">{{ $requests->links() }}</div>
        @endif

        {{-- ============ How it works ============ --}}
        @if($tab === 'watch' && $search === '' && $onFirstPage)
            <section class="ch-how">
                <div>
                    <h2>{{ __('How challenges work') }}</h2>
                </div>
                <ol class="ch-how__steps">
                    <li>
                        <span class="ch-how__n">1</span>
                        <strong>{{ __('Dare') }}</strong>
                        <span>{{ __('Pitch a challenge to a creator and set a goal.') }}</span>
                    </li>
                    <li>
                        <span class="ch-how__n">2</span>
                        <strong>{{ __('Fund') }}</strong>
                        <span>{{ __('Fans chip in until the goal is hit.') }}</span>
                    </li>
                    <li>
                        <span class="ch-how__n">3</span>
                        <strong>{{ __('Watch') }}</strong>
                        <span>{{ __('The creator does it and posts the video here.') }}</span>
                    </li>
                </ol>
                @include('custom-requests.partials.dare-button')
            </section>
        @endif

    </main>
</div>

{{-- The create-request modal is already rendered by layouts/generic.blade.php — including it
     again here would duplicate #createCustomRequestModal and break the modal. --}}
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/discover.js') }}?v=20260917a" defer></script>
@endsection
