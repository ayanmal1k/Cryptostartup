@extends('layouts.generic')

@section('page_title', $stream->title)

@section('styles')
    @include('streams.partials.head')
@stop

@php
    $u = $stream->user;
    $isLive = (bool) $stream->is_live;
    $poster = $stream->thumbnail ? asset('storage/' . $stream->thumbnail) : null;
    // Only set once a video service is connected (see StreamController).
    $hasVideo = $isLive && !$locked && !empty($stream->hls_url);
@endphp

@section('content')
<div class="dx">
    @include('streams.partials.subnav', ['active' => null])

    <main class="dx-main">
        <div class="lv-stage">
            <div>
                <div class="lv-screen">
                    @if($poster)
                        <img class="lv-screen__poster" src="{{ $poster }}" alt="">
                    @endif

                    @if($hasVideo)
                        <video id="lv-player" controls autoplay playsinline muted @if($poster) poster="{{ $poster }}" @endif data-src="{{ $stream->hls_url }}"></video>
                    @endif

                    <div class="lv-screen__top">
                        @if($isLive)
                            <span class="lv-badge" data-lv-live-badge><span class="lv-dot"></span>{{ __('LIVE') }}</span>
                            <span class="lv-badge lv-badge--dim lv-timer" data-lv-live-badge data-lv-timer="{{ optional($stream->started_at)->toIso8601String() }}">0:00</span>
                        @endif
                        <span class="lv-spacer"></span>
                        @if($isLive)
                            <span class="lv-badge lv-badge--dim">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span data-lv-viewers>{{ number_format((int) $stream->viewer_count) }}</span>
                            </span>
                        @endif
                    </div>

                    @if($locked)
                        <div class="lv-screen__msg">
                            <img class="lv-face" src="{{ $u->avatar }}" alt="">
                            <h2>{{ __('Subscribers only') }}</h2>
                            <p>{{ __('Subscribe to :name to watch and chat.', ['name' => $u->name]) }}</p>
                            <a href="{{ route('profile', ['username' => $u->username]) }}#profile-subscribe" class="dx-btn dx-btn--primary">{{ __('Subscribe') }}</a>
                        </div>
                    @elseif(!$hasVideo)
                        {{-- Live, but no video service yet: say so rather than show a spinner forever. --}}
                        <div class="lv-screen__msg is-live" data-lv-state="live" @unless($isLive) hidden @endunless>
                            <img class="lv-face" src="{{ $u->avatar }}" alt="">
                            <h2>{{ __(':name is live', ['name' => $u->name]) }}</h2>
                            <p>{{ __('Live video is being switched on. The chat is open — say hi.') }}</p>
                        </div>
                    @endif

                    <div class="lv-screen__msg" data-lv-state="ended" @if($isLive) hidden @endif>
                        <img class="lv-face" src="{{ $u->avatar }}" alt="">
                        <h2>{{ $stream->status === 'ended' ? __('This stream has ended') : __('Not live yet') }}</h2>
                        <p>{{ $stream->status === 'ended' ? __('Follow :name to hear when they go live next.', ['name' => $u->name]) : __(':name hasn\'t started this stream yet.', ['name' => $u->name]) }}</p>
                        <a href="{{ route('profile', ['username' => $u->username]) }}" class="dx-btn dx-btn--ghost">{{ __('View profile') }}</a>
                    </div>
                </div>

                <div class="lv-info">
                    <h1 class="lv-info__title">{{ $stream->title }}</h1>
                    <a href="{{ route('profile', ['username' => $u->username]) }}" class="lv-creator">
                        <img src="{{ $u->avatar }}" alt="" class="dx-avatar">
                        <span>
                            <span class="lv-creator__name">{{ $u->name }}</span>
                            <span class="lv-creator__meta d-block">
                                {{ '@' . $u->username }}
                                @if($isLive && $stream->started_at) · {{ __('started') }} {{ $stream->started_at->diffForHumans() }}
                                @elseif($stream->ended_at) · {{ __('ended') }} {{ $stream->ended_at->diffForHumans() }}@endif
                            </span>
                        </span>
                    </a>
                    @if($stream->description)
                        <p class="lv-info__desc">{{ $stream->description }}</p>
                    @endif
                    <div class="lv-actions">
                        <a href="{{ route('profile', ['username' => $u->username]) }}" class="dx-btn dx-btn--ghost">{{ __('View profile') }}</a>
                        <button type="button" class="dx-btn dx-btn--ghost" data-lv-copy data-lv-share data-text="{{ route('streams.watch', $stream) }}">{{ __('Share') }}</button>
                        @if($isOwner)
                            <a href="{{ route('streams.broadcast', $stream) }}" class="dx-btn dx-btn--primary">{{ __('Open studio') }}</a>
                        @endif
                    </div>
                </div>
            </div>

            <aside class="lv-chat" data-lv-chat data-live="{{ $isLive ? '1' : '0' }}"
                   data-fetch="{{ route('streams.messages', $stream) }}" data-post="{{ route('streams.messages.store', $stream) }}">
                <div class="lv-chat__head">{{ __('Live chat') }}</div>
                <div class="lv-chat__list" data-lv-chat-list>
                    <p class="lv-chat__empty" data-lv-chat-empty>{{ $isLive ? __('No messages yet. Be the first to say something.') : __('Chat is closed.') }}</p>
                </div>
                @if($isLive && !$locked)
                    <form class="lv-chat__form" data-lv-chat-form>
                        <input type="text" class="lv-chat__input" maxlength="500" placeholder="{{ __('Say something…') }}" aria-label="{{ __('Chat message') }}" autocomplete="off" enterkeyhint="send">
                        <button type="submit" class="lv-chat__send" aria-label="{{ __('Send') }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6l2.8 8.4H13v0H6.2z"/></svg>
                        </button>
                    </form>
                @elseif($locked)
                    <p class="lv-chat__closed">{{ __('Subscribe to join the chat.') }}</p>
                @endif
            </aside>
        </div>
    </main>
</div>
@if($hasVideo)
    {{-- hls.js for browsers without native HLS (everything but Safari). --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/hls.js/1.5.13/hls.min.js" defer></script>
    <script>
    window.addEventListener('load', function () {
        var v = document.getElementById('lv-player');
        if (!v) return;
        var src = v.getAttribute('data-src');
        if (v.canPlayType('application/vnd.apple.mpegurl')) { v.src = src; }
        else if (window.Hls && Hls.isSupported()) { var h = new Hls(); h.loadSource(src); h.attachMedia(v); }
    });
    </script>
@endif
<script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
<script src="{{ asset('js/pages/live.js') }}?v=20260924a" defer></script>
@stop
