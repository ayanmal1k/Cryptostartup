@extends('layouts.generic')

@section('page_title', __('Live'))

@section('styles')
    @include('streams.partials.head')
@stop

@section('content')
<div class="dx">
    @include('streams.partials.subnav', ['active' => 'live', 'liveCount' => $live->count()])

    <main class="dx-main">
        @foreach(['success', 'error'] as $flash)
            @if(session($flash))
                <div class="dx-alert dx-alert--{{ $flash }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        @if($mine)
            <a href="{{ route('streams.broadcast', $mine) }}" class="lv-mine">
                <span class="lv-badge"><span class="lv-dot"></span>{{ __('LIVE') }}</span>
                <span>{{ __('You\'re live: :title', ['title' => $mine->title]) }}</span>
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        @endif

        <section>
            <div class="dx-section__head">
                <div>
                    <h2 class="dx-section__title">{{ __('Live now') }}</h2>
                    @if($live->count())
                        <p class="dx-section__sub">{{ trans_choice('{1} :n creator on air|[2,*] :n creators on air', $live->count(), ['n' => $live->count()]) }}</p>
                    @endif
                </div>
            </div>

            @if($live->count())
                <div class="lv-grid">
                    @foreach($live as $stream)
                        @include('streams.partials.card', ['stream' => $stream])
                    @endforeach
                </div>
            @else
                <div class="dx-empty">
                    <span class="dx-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.9 19.1a10 10 0 0 1 0-14.2M19.1 4.9a10 10 0 0 1 0 14.2M7.8 16.2a6 6 0 0 1 0-8.4M16.2 7.8a6 6 0 0 1 0 8.4"/><circle cx="12" cy="12" r="2"/></svg>
                    </span>
                    <h3>{{ __('Nobody\'s live right now') }}</h3>
                    <p>{{ __('Creators you follow show up here the moment they go live.') }}</p>
                    <a href="{{ route('streams.create') }}" class="dx-btn dx-btn--primary">{{ __('Go live yourself') }}</a>
                </div>
            @endif
        </section>

        @if($recent->count())
            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Recently live') }}</h2>
                        <p class="dx-section__sub">{{ __('The last seven days') }}</p>
                    </div>
                </div>
                <div class="dx-rail">
                    @foreach($recent as $stream)
                        @include('streams.partials.card', ['stream' => $stream])
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</div>
<script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
@stop
