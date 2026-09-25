@extends('layouts.generic')

@section('page_title', __('Creator Dashboard'))

@php
    /*
     * Built on the discover shell (discover.css) and the challenge tiles (challenges.css),
     * so the dashboard reads like the NFT and Challenges pages: tab bar, content first,
     * no wall of zero-value stat tiles.
     */
    $compact = fn ($n) => $n >= 1000000 ? round($n / 1000000, 1) . 'M' : ($n >= 1000 ? round($n / 1000, 1) . 'K' : number_format($n));

    // The best-performing clip leads, like the spotlight on Challenges.
    $top = $videos->filter(fn ($v) => $v->video_url !== '')->sortByDesc(fn ($v) => (int) ($v->views_count ?? 0))->first();
    $rest = $top ? $videos->reject(fn ($v) => $v->is($top)) : $videos;

    $liveNow = $streams->firstWhere('is_live', true);
    $upload = ['label' => __('Upload'), 'href' => url('/create')];
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/challenges.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/creator-overview.css') }}?v=20260924a">
@endsection

@section('content')
<div class="dx cr">

    @include('elements.discover.subnav', [
        'tabs' => [
            ['label' => __('Overview'), 'href' => route('creator.dashboard'), 'active' => true],
            ['label' => __('Videos'), 'href' => route('creator.videos'), 'active' => false, 'count' => $totalVideos],
            ['label' => __('Streams'), 'href' => route('creator.streams'), 'active' => false, 'count' => $totalStreams],
            ['label' => __('Analytics'), 'href' => route('creator.analytics'), 'active' => false],
        ],
        'cta' => $upload,
    ])

    <main class="dx-main">

        {{-- ============ Who you are + the numbers that matter ============ --}}
        <section class="cr-me">
            <a href="{{ route('profile', ['username' => $user->username]) }}" class="cr-me__id">
                <img src="{{ $user->avatar }}" alt="" class="cr-me__avatar">
                <span>
                    <span class="cr-me__name">
                        {{ $user->name }}
                        @if($isVerified)
                            <svg class="cr-me__check" viewBox="0 0 24 24" aria-label="{{ __('Verified') }}" role="img"><path fill="currentColor" d="M12 2l2.4 2.1 3.2-.3.8 3.1 2.8 1.6-1.2 3 1.2 3-2.8 1.6-.8 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.8-3.1-2.8-1.6 1.2-3-1.2-3 2.8-1.6.8-3.1 3.2.3z"/><path d="m8.5 12.2 2.3 2.3 4.7-4.8" fill="none" stroke="#09090b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </span>
                    <span class="cr-me__handle">{{ '@' . $user->username }}</span>
                </span>
            </a>

            {{-- One headline number, then a single line of the rest. Only non-zero figures
                 are shown, the same rule as the pulse on Challenges. --}}
            <div class="cr-me__views">
                <strong>{{ $compact($totalViews) }}</strong>
                <span>{{ trans_choice('{1} view|[0,*] views', $totalViews) }}</span>
            </div>
            @if($totalLikes || $totalComments || $totalVideos || $totalStreams)
                <div class="dx-pulse">
                    @if($totalLikes)<span><strong>{{ $compact($totalLikes) }}</strong> {{ trans_choice('{1} like|[2,*] likes', $totalLikes) }}</span>@endif
                    @if($totalComments)<span><strong>{{ $compact($totalComments) }}</strong> {{ trans_choice('{1} comment|[2,*] comments', $totalComments) }}</span>@endif
                    @if($totalVideos)<span><strong>{{ $totalVideos }}</strong> {{ trans_choice('{1} video|[2,*] videos', $totalVideos) }}</span>@endif
                    @if($totalStreams)<span><strong>{{ $totalStreams }}</strong> {{ trans_choice('{1} stream|[2,*] streams', $totalStreams) }}</span>@endif
                </div>
            @endif

            {{-- Upload lives in the tab bar, as on the other discover pages. --}}
            @if($isVerified)
                <div class="cr-me__actions">
                    <a href="{{ route('streams.create') }}" class="dx-btn dx-btn--ghost">
                        <span class="cr-live-dot" aria-hidden="true"></span>
                        {{ __('Go live') }}
                    </a>
                </div>
            @endif
        </section>

        {{-- ============ Verification: one quiet row, not a red alarm ============ --}}
        @if(!$isVerified)
            @php
                $verify = [
                    'not_submitted' => [__('Verify your ID to go live'), __('It takes a couple of minutes and unlocks livestreaming.'), __('Verify')],
                    'pending' => [__('ID check in progress'), __('Usually done within 1–2 business days.'), null],
                    'rejected' => [__('ID check didn\'t go through'), __('Try again with a clear photo of a valid ID.'), __('Try again')],
                ][$verificationStatus] ?? null;
            @endphp
            @if($verify)
                <section class="cr-task {{ $verificationStatus === 'rejected' ? 'is-bad' : '' }}">
                    <span class="cr-task__icon" aria-hidden="true">
                        @if($verificationStatus === 'pending')
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M6 16c.6-1.3 1.7-2 3-2s2.4.7 3 2M15 10h3M15 13h2"/></svg>
                        @endif
                    </span>
                    <span class="cr-task__body">
                        <strong>{{ $verify[0] }}</strong>
                        <span>{{ $verify[1] }}</span>
                    </span>
                    @if($verify[2])
                        <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="dx-btn dx-btn--sm dx-btn--ghost">{{ $verify[2] }}</a>
                    @endif
                </section>
            @endif
        @endif

        {{-- ============ Live right now ============ --}}
        @if($liveNow)
            <a href="{{ route('creator.streams') }}" class="cr-task is-live">
                <span class="cr-live-dot" aria-hidden="true"></span>
                <span class="cr-task__body">
                    <strong>{{ __('You\'re live') }}: {{ $liveNow->title }}</strong>
                    <span>{{ trans_choice('{1} :n viewer watching|[0,*] :n viewers watching', (int) $liveNow->viewer_count, ['n' => number_format((int) $liveNow->viewer_count)]) }}</span>
                </span>
            </a>
        @endif

        {{-- ============ Top video, playing ============ --}}
        @if($top)
            @php $topViews = (int) ($top->views_count ?? 0); @endphp
            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Your top video') }}</h2>
                        <p class="dx-section__sub">{{ __('Most watched so far') }}</p>
                    </div>
                </div>
                <div class="ch-spot" data-dx-media>
                    <a href="{{ route('creator.videos') }}" class="ch-spot__media" tabindex="-1" aria-hidden="true">
                        <video data-dx-autoplay data-src="{{ $top->video_url }}"
                               @if($top->thumbnail_url) poster="{{ $top->thumbnail_url }}" @endif
                               muted loop playsinline preload="none"></video>
                    </a>
                    <span class="ch-spot__scrim"></span>
                    <span class="ch-spot__play" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M7 4v16l13-8z"/></svg></span>

                    <div class="ch-spot__top">
                        <span class="ch-chip ch-chip--win">#1</span>
                        <button type="button" class="ch-spot__sound" data-dx-sound hidden aria-pressed="false" aria-label="{{ __('Sound') }}">
                            <svg class="is-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="m23 9-6 6M17 9l6 6"/></svg>
                            <svg class="is-unmuted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M11 5 6 9H2v6h4l5 4V5z"/><path d="M15.5 8.5a5 5 0 0 1 0 7M19 5a10 10 0 0 1 0 14"/></svg>
                        </button>
                    </div>

                    <div class="ch-spot__info">
                        <h3 class="ch-spot__title"><a href="{{ route('creator.videos') }}">{{ $top->title }}</a></h3>
                        <div class="ch-spot__stats">
                            <span><strong>{{ number_format($topViews) }}</strong> {{ trans_choice('{1} view|[0,*] views', $topViews) }}</span>
                            @if((int) $top->likes_count)<span><strong>{{ number_format((int) $top->likes_count) }}</strong> {{ __('likes') }}</span>@endif
                            @if((int) $top->comments_count)<span><strong>{{ number_format((int) $top->comments_count) }}</strong> {{ __('comments') }}</span>@endif
                            <span>{{ $top->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                </div>
            </section>
        @endif

        {{-- ============ The rest of your videos ============ --}}
        <section>
            <div class="dx-section__head">
                <div>
                    <h2 class="dx-section__title">{{ $top ? __('More of your videos') : __('Your videos') }}</h2>
                    @if($totalVideos)
                        <p class="dx-section__sub">{{ __('Newest first') }}</p>
                    @endif
                </div>
                @if($totalVideos > 1)
                    <a href="{{ route('creator.videos') }}" class="dx-section__link">{{ __('Manage all') }}</a>
                @endif
            </div>

            @if($rest->count())
                <div class="ch-grid">
                    @foreach($rest->take(6) as $video)
                        @php
                            $thumb = $video->thumbnail_url;
                            $views = (int) ($video->views_count ?? 0);
                        @endphp
                        <a href="{{ route('creator.videos') }}" class="ch-tile">
                            <div class="ch-tile__media" @if($video->video_url) data-dx-preview="{{ $video->video_url }}" @endif>
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="" loading="lazy" decoding="async">
                                @elseif($video->video_url)
                                    {{-- No thumbnail on file: the first frame stands in for one. --}}
                                    <video class="cr-frame" src="{{ $video->video_url }}#t=0.5" muted playsinline preload="metadata" aria-hidden="true"></video>
                                @endif
                                <span class="ch-tile__scrim"></span>
                            </div>
                            <div class="ch-tile__top">
                                {{-- Only flag clips that aren't live in the feed. --}}
                                @if($video->status && $video->status !== 'published')
                                    <span class="ch-chip">{{ __($video->status_label) }}</span>
                                @endif
                            </div>
                            <div class="ch-tile__info">
                                <span class="ch-tile__title">{{ $video->title }}</span>
                                <span class="ch-tile__by">
                                    <span class="ch-tile__name">{{ $video->created_at->diffForHumans() }}</span>
                                    <span class="ch-tile__views">
                                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
                                        {{ $compact($views) }}
                                    </span>
                                </span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @elseif(!$top)
                <div class="dx-empty">
                    <span class="dx-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/></svg>
                    </span>
                    <h3>{{ __('Post your first video') }}</h3>
                    <p>{{ __('Short clips do best. Upload one and it goes straight into the feed.') }}</p>
                    <a href="{{ url('/create') }}" class="dx-btn dx-btn--primary">{{ __('Upload video') }}</a>
                </div>
            @endif
        </section>

        {{-- ============ Livestreams ============ --}}
        <section>
            <div class="dx-section__head">
                <div>
                    <h2 class="dx-section__title">{{ __('Livestreams') }}</h2>
                </div>
                @if($totalStreams)
                    <a href="{{ route('creator.streams') }}" class="dx-section__link">{{ __('See all') }}</a>
                @endif
            </div>

            @if($streams->count())
                <div class="dx-rail">
                    @foreach($streams->take(8) as $stream)
                        <a href="{{ route('creator.streams') }}" class="cr-stream">
                            <span class="cr-stream__thumb">
                                @if($stream->thumbnail)
                                    <img src="{{ $stream->thumbnail_url }}" alt="" loading="lazy">
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4.9 19.1a10 10 0 0 1 0-14.2M19.1 4.9a10 10 0 0 1 0 14.2M7.8 16.2a6 6 0 0 1 0-8.4M16.2 7.8a6 6 0 0 1 0 8.4"/><circle cx="12" cy="12" r="2"/></svg>
                                @endif
                                @if($stream->is_live)
                                    <span class="ch-chip cr-stream__live">{{ __('LIVE') }}</span>
                                @endif
                            </span>
                            <span class="cr-stream__body">
                                <span class="cr-stream__title">{{ $stream->title }}</span>
                                <span class="cr-stream__meta">
                                    {{ $stream->created_at->diffForHumans() }}
                                    @if((int) $stream->peak_viewer_count) · {{ number_format((int) $stream->peak_viewer_count) }} {{ __('peak') }}@endif
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="dx-empty">
                    <span class="dx-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4.9 19.1a10 10 0 0 1 0-14.2M19.1 4.9a10 10 0 0 1 0 14.2M7.8 16.2a6 6 0 0 1 0-8.4M16.2 7.8a6 6 0 0 1 0 8.4"/><circle cx="12" cy="12" r="2"/></svg>
                    </span>
                    @if($isVerified)
                        <h3>{{ __('Go live for the first time') }}</h3>
                        <p>{{ __('Your followers get told the moment you start.') }}</p>
                        <a href="{{ route('streams.create') }}" class="dx-btn dx-btn--primary">{{ __('Go live') }}</a>
                    @else
                        <h3>{{ __('Livestreams unlock after ID check') }}</h3>
                        <p>{{ __('Verify once and you can go live any time.') }}</p>
                        <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="dx-btn dx-btn--ghost">{{ __('Verify ID') }}</a>
                    @endif
                </div>
            @endif
        </section>

        {{-- ============ Shortcuts ============ --}}
        <section class="cr-links" aria-label="{{ __('Creator shortcuts') }}">
            <a href="{{ route('creator.analytics') }}" class="cr-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/></svg>
                <span>{{ __('Analytics') }}</span>
            </a>
            <a href="{{ route('custom-requests.marketplace') }}" class="cr-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                <span>{{ __('Challenges') }}</span>
            </a>
            <a href="{{ route('wallet.hub') }}" class="cr-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="6" width="20" height="14" rx="2"/><path d="M16 13h2M2 10h20"/></svg>
                <span>{{ __('Earnings') }}</span>
            </a>
            <a href="{{ route('my.settings', ['type' => 'profile']) }}" class="cr-link">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/></svg>
                <span>{{ __('Settings') }}</span>
            </a>
        </section>

    </main>
</div>
<script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
@endsection
