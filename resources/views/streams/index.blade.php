@extends('layouts.generic')

@section('page_title', __('Live Streams'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/streams-index.css'
         ])->withFullUrl()
    !!}
    <link rel="stylesheet" href="{{ asset('css/pages/streams-index.css') }}?v=20260712b">
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/pages/streams-index.js'
         ])->withFullUrl()
    !!}
@stop

@section('content')
    @php
        $streamsDark = Cookie::get('app_theme') == null
            ? getSetting('site.default_user_theme') == 'dark'
            : Cookie::get('app_theme') == 'dark';
    @endphp
    <div class="streams-page streams-page--{{ $streamsDark ? 'dark' : 'light' }}">
        <div class="streams-page__scroll">
            <div class="streams-page__inner">
                <header class="streams-header">
                    <div class="streams-header__text">
                        <h1 class="streams-header__title d-none d-md-block">{{ __('Live Streams') }}</h1>
                        <p class="streams-header__subtitle">{{ __('Watch creators go live or start your own broadcast') }}</p>
                    </div>
                    @auth
                        <a href="{{ route('streams.create') }}" class="streams-btn streams-btn--primary streams-header__cta">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                            <span class="streams-header__cta-label">{{ __('Go Live') }}</span>
                        </a>
                    @endauth
                </header>

                <section class="streams-panel" aria-label="{{ __('Live streams list') }}">
                    @if($streams->count() > 0)
                        <div class="streams-grid">
                            @foreach($streams as $stream)
                                @include('elements.streams.stream-card', ['stream' => $stream])
                            @endforeach
                        </div>
                        <div class="streams-pagination">
                            {{ $streams->links() }}
                        </div>
                    @else
                        <div class="streams-empty">
                            <div class="streams-empty__icon" aria-hidden="true">
                                <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/>
                                    <rect x="2" y="6" width="14" height="12" rx="2"/>
                                </svg>
                            </div>
                            <p class="streams-empty__title">{{ __('No live streams right now') }}</p>
                            <p class="streams-empty__hint">{{ __('Check back later or be the first to go live!') }}</p>
                            @auth
                                <a href="{{ route('streams.create') }}" class="streams-btn streams-btn--primary streams-empty__cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m16 13 5.223 3.482a.5.5 0 0 0 .777-.416V7.87a.5.5 0 0 0-.752-.432L16 10.5"/><rect x="2" y="6" width="14" height="12" rx="2"/></svg>
                                    {{ __('Start Streaming') }}
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="streams-btn streams-btn--primary streams-empty__cta">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M13.8 12H3"/></svg>
                                    {{ __('Login to Stream') }}
                                </a>
                            @endauth
                        </div>
                    @endif
                </section>

                <section class="streams-guide" aria-label="{{ __('How to start streaming') }}">
                    <h2 class="streams-guide__title">{{ __('How to Start Streaming') }}</h2>
                    <div class="streams-guide__steps">
                        <div class="streams-guide__step">
                            <div class="streams-guide__step-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/></svg>
                            </div>
                            <div class="streams-guide__step-num">1</div>
                            <h3 class="streams-guide__step-title">{{ __('Set Up Your Stream') }}</h3>
                            <p class="streams-guide__step-text">{{ __('Create a new stream and get your unique streaming key') }}</p>
                        </div>
                        <div class="streams-guide__step">
                            <div class="streams-guide__step-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                            </div>
                            <div class="streams-guide__step-num">2</div>
                            <h3 class="streams-guide__step-title">{{ __('Configure Your Software') }}</h3>
                            <p class="streams-guide__step-text">{{ __('Use OBS, Streamlabs or any RTMP compatible software') }}</p>
                        </div>
                        <div class="streams-guide__step">
                            <div class="streams-guide__step-icon" aria-hidden="true">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4.9 19.1C1 15.2 1 8.8 4.9 4.9M19.1 4.9C23 8.8 23 15.1 19.1 19"/><path d="M12 2v4M12 18v4M2 12h4M18 12h4"/></svg>
                            </div>
                            <div class="streams-guide__step-num">3</div>
                            <h3 class="streams-guide__step-title">{{ __('Go Live') }}</h3>
                            <p class="streams-guide__step-text">{{ __('Start broadcasting and connect with your audience') }}</p>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
@stop
