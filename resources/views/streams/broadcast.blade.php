@extends('layouts.generic')

@section('page_title', __('Studio'))

@section('styles')
    @include('streams.partials.head')
@stop

@php
    $isLive = (bool) $stream->is_live;
    $ended = !$isLive && $stream->status === 'ended';
    $watchUrl = route('streams.watch', $stream);
@endphp

@section('content')
<div class="dx">
    @include('streams.partials.subnav', ['active' => 'mine'])

    <main class="dx-main">
        <div class="lv-stage">
            <div>
                {{-- Camera preview. Local only: nothing leaves the device until a video
                     service is connected. --}}
                <div class="lv-screen lv-screen--portrait" data-lv-studio>
                    <video data-lv-camera autoplay muted playsinline hidden></video>

                    <div class="lv-screen__top">
                        @if($isLive)
                            <span class="lv-badge"><span class="lv-dot"></span>{{ __('LIVE') }}</span>
                            <span class="lv-badge lv-badge--dim lv-timer" data-lv-timer="{{ optional($stream->started_at)->toIso8601String() }}">0:00</span>
                        @elseif($ended)
                            <span class="lv-badge lv-badge--dim">{{ __('Ended') }}</span>
                        @else
                            <span class="lv-badge lv-badge--dim">{{ __('Offline') }}</span>
                        @endif
                        <span class="lv-spacer"></span>
                        @if($isLive)
                            <span class="lv-badge lv-badge--dim">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>
                                <span data-lv-viewers>{{ number_format((int) $stream->viewer_count) }}</span>
                            </span>
                        @endif
                    </div>

                    <div class="lv-screen__msg" data-lv-camera-msg>
                        <img class="lv-face" src="{{ $stream->user->avatar }}" alt="">
                        <h2>{{ $ended ? __('This stream has ended') : ($isLive ? __('You\'re live') : __('Check your camera')) }}</h2>
                        <p>{{ $ended ? __('Set up a new stream whenever you\'re ready.') : ($isLive ? __('Keep an eye on chat. Turn your camera on to see yourself; the preview stays on your device.') : __('See how you look before going live. The preview stays on your device.')) }}</p>
                        @unless($ended)
                            <button type="button" class="dx-btn dx-btn--ghost" data-lv-open-camera>{{ __('Turn on camera') }}</button>
                        @endunless
                    </div>

                    <div class="lv-screen__ctrls">
                        <button type="button" class="lv-round" data-lv-toggle="audio" aria-pressed="false" aria-label="{{ __('Mute microphone') }}" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="9" y="2" width="6" height="12" rx="3"/><path d="M5 10a7 7 0 0 0 14 0M12 17v5"/></svg>
                        </button>
                        <button type="button" class="lv-round" data-lv-toggle="video" aria-pressed="false" aria-label="{{ __('Turn camera off') }}" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="14" height="12" rx="2"/><path d="m16 10 6-3v10l-6-3"/></svg>
                        </button>
                        <button type="button" class="lv-round" data-lv-flip aria-label="{{ __('Switch camera') }}" hidden>
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 7h-3l-2-3H9L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2z"/><path d="m15 12-3-2.5L9 12M9 15l3 2.5 3-2.5"/></svg>
                        </button>
                    </div>
                </div>

                <div class="lv-info">
                    <h1 class="lv-info__title">{{ $stream->title }}</h1>
                    @if($stream->description)
                        <p class="lv-info__desc">{{ $stream->description }}</p>
                    @endif

                    <div class="lv-actions">
                        @if($isLive)
                            <button type="button" class="dx-btn dx-btn--ghost" data-lv-end="{{ route('streams.end', $stream) }}" data-after="{{ route('creator.streams') }}">
                                {{ __('End stream') }}
                            </button>
                            <button type="button" class="dx-btn dx-btn--ghost" data-lv-copy data-lv-share data-text="{{ $watchUrl }}">
                                {{ __('Share link') }}
                            </button>
                        @elseif($ended)
                            <a href="{{ route('streams.create') }}" class="dx-btn dx-btn--primary">{{ __('Set up a new stream') }}</a>
                        @else
                            <button type="button" class="dx-btn dx-btn--live" data-lv-start="{{ route('streams.start', $stream) }}">
                                <span class="lv-dot" aria-hidden="true"></span>
                                {{ __('Go live') }}
                            </button>
                        @endif
                    </div>

                    @unless($ended)
                        @include('streams.partials.video-note')

                        <details class="lv-details">
                            <summary>{{ __('Streaming from OBS or other software') }}</summary>
                            <div class="lv-details__body">
                                <span class="lv-field__hint">{{ __('Your stream key. Keep it private — anyone with it could stream as you.') }}</span>
                                <div class="lv-key">
                                    <input type="password" id="lv-stream-key" value="{{ $stream->stream_key }}" readonly aria-label="{{ __('Stream key') }}"
                                           onfocus="this.type='text'" onblur="this.type='password'">
                                    <button type="button" class="dx-btn dx-btn--sm dx-btn--ghost" data-lv-copy="lv-stream-key">{{ __('Copy') }}</button>
                                </div>
                                <span class="lv-field__hint">{{ __('The server address appears here once live video is connected.') }}</span>
                            </div>
                        </details>
                    @endunless
                </div>
            </div>

            {{-- Chat --}}
            <aside class="lv-chat" data-lv-chat data-live="{{ $isLive ? '1' : '0' }}"
                   data-fetch="{{ route('streams.messages', $stream) }}" data-post="{{ route('streams.messages.store', $stream) }}">
                <div class="lv-chat__head">{{ __('Live chat') }} <span>{{ $isLive ? __('Viewers see this') : __('Opens when you go live') }}</span></div>
                <div class="lv-chat__list" data-lv-chat-list>
                    <p class="lv-chat__empty" data-lv-chat-empty>{{ $isLive ? __('No messages yet. Say hi to get things going.') : __('Chat opens the moment you go live.') }}</p>
                </div>
                @if($isLive)
                    <form class="lv-chat__form" data-lv-chat-form>
                        <input type="text" class="lv-chat__input" maxlength="500" placeholder="{{ __('Say something…') }}" aria-label="{{ __('Chat message') }}" autocomplete="off" enterkeyhint="send">
                        <button type="submit" class="lv-chat__send" aria-label="{{ __('Send') }}">
                            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6l2.8 8.4H13v0H6.2z"/></svg>
                        </button>
                    </form>
                @endif
            </aside>
        </div>
    </main>
</div>
<script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
<script src="{{ asset('js/pages/live.js') }}?v=20260924a" defer></script>
@stop
