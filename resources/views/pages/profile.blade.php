@extends('layouts.generic')

@section('page_title',  __("user_profile_title_label",['user' => $user->name]))
@section('share_url', route('profile',['username'=> $user->username]))
@section('share_title',  __("user_profile_title_label",['user' => $user->name]) . ' - ' .  getSetting('site.name'))
@section('share_description', $seo_description ?? getSetting('site.description'))
@section('share_type', 'article')
@section('share_img', $user->cover)

@section('scripts')
    {!!
        Minify::javascript(array_merge([
            '/js/PostsPaginator.js',
            '/js/CommentsPaginator.js',
            '/js/StreamsPaginator.js',
            '/js/Post.js',
            '/js/pages/profile.js',
            '/js/pages/lists.js',
            '/js/pages/checkout.js',
            '/libs/swiper/swiper-bundle.min.js',
            '/js/plugins/media/photoswipe.js',
            '/libs/photoswipe/dist/photoswipe-ui-default.min.js',
            '/js/plugins/media/mediaswipe.js',
            '/js/plugins/media/mediaswipe-loader.js',
            '/js/LoginModal.js',
            '/js/messenger/messenger.js',
         ],$additionalAssets))->withFullUrl()
    !!}
@stop

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/profile.css',
            '/css/pages/checkout.css',
            '/css/pages/lists.css',
            '/css/pages/auth.css',
            '/libs/swiper/swiper-bundle.min.css',
            '/libs/photoswipe/dist/photoswipe.css',
            '/libs/photoswipe/dist/default-skin/default-skin.css',
            '/css/posts/post.css',
            '/css/frames.css'
         ])->withFullUrl()
    !!}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
    <link rel="stylesheet" href="{{ asset('css/pages/profile-v3.css') }}?v=20260924a">
    @if(getSetting('feed.post_box_max_height'))
        @include('elements.feed.fixed-height-feed-posts', ['height' => getSetting('feed.post_box_max_height')])
    @endif
@stop

@section('meta')
    @if(getSetting('security.recaptcha_enabled') && !Auth::check())
        {!! NoCaptcha::renderJs() !!}
    @endif
    @if($activeFilter)
        <link rel="canonical" href="{{route('profile',['username'=> $user->username])}}" />
    @endif
@stop

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $isOwner = Auth::check() && Auth::user()->id === $user->id;
    $canChat = $hasSub || $viewerHasChatAccess;
    $isVerified = $user->email_verified_at && $user->birthdate && ($user->verification && $user->verification->status == 'verified');
    $profileFrame = \App\Services\PerkShopService::frameClass($user);

    // The hero is the cover photo. With no cover of their own, the avatar is blown up and
    // blurred behind them instead of the stock "Hello!" graphic.
    $hasCover = (bool) $user->getRawOriginal('cover');
    $hasAvatar = (bool) $user->getRawOriginal('avatar');

    // Live state comes from the Live pages (App\Models\Stream), not the old JustFans
    // streams the controller still checks, which nothing can start any more.
    $liveStream = \App\Models\Stream::where('user_id', $user->id)
        ->where('is_live', true)
        ->where('is_public', true)
        ->latest('started_at')
        ->first();
    $hasActiveStream = (bool) $liveStream;
    $liveUrl = $liveStream ? route('streams.watch', $liveStream) : null;

    $tab = in_array($activeFilter, ['reposts', 'shop'], true) ? $activeFilter : 'videos';

    $userVideos = \App\Models\Video::where('user_id', $user->id)
        ->when(!$isOwner, fn ($q) => $q->where('status', 'published'))
        ->latest()
        ->get();
    $videoLikes = (int) $userVideos->sum(fn ($v) => (int) ($v->likes_count ?? 0));

    $followers = \App\Model\UserListMember::join('user_lists', 'user_list_members.list_id', '=', 'user_lists.id')
        ->where('user_list_members.user_id', $user->id)
        ->where('user_lists.type', 'following')
        ->count();

    $shopItems = \App\Models\NFT::with(['creator', 'activeListing'])
        ->where('user_id', $user->id)
        ->where('status', '!=', \App\Models\NFT::STATUS_MINT_FAILED)
        ->latest()
        ->take(24)
        ->get();

    $repostCount = 0;
    try {
        $repostCount = \DB::table('video_reposts')->where('user_id', $user->id)->count();
    } catch (\Throwable $e) {
        $repostCount = 0;
    }

    $compact = fn ($n) => $n >= 1000000 ? round($n / 1000000, 1) . 'M' : ($n >= 1000 ? round($n / 1000, 1) . 'K' : number_format($n));
    $tabUrl = fn ($key) => route('profile', $key === 'videos' ? ['username' => $user->username] : ['username' => $user->username, 'filter' => $key]);
@endphp

@section('content')
    <div class="row profile-page-v2 pp {{ $isDarkTheme ? 'profile-page-v2--dark pp--dark' : 'profile-page-v2--light pp--light' }}">
        <div class="min-vh-100 col-12 col-md-8 border-right px-0">

            {{-- ============ Hero: the cover fills the top, everything sits on it ============ --}}
            <section class="pp-hero {{ $hasCover ? 'has-cover' : '' }}">
                <div class="pp-hero__bg" aria-hidden="true">
                    @if($hasCover)
                        <img src="{{ $user->cover }}" alt="">
                    @elseif($hasAvatar)
                        <img src="{{ $user->avatar }}" alt="" class="is-blurred">
                    @endif
                </div>
                <span class="pp-hero__scrim" aria-hidden="true"></span>

                {{-- Secondary actions float in the top corner, out of the way of the name. --}}
                <div class="pp-tools">
                    @if($isOwner)
                        <a href="{{ route('my.settings') }}" class="pp-tool pp-tool--text">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 1 1 3 3L7 19l-4 1 1-4z"/></svg>
                            {{ __('Edit profile') }}
                        </a>
                    @elseif(Auth::check())
                        <button type="button" class="pp-tool to-tooltip"
                              @if(!Auth::user()->email_verified_at && getSetting('site.enforce_email_validation'))
                              data-placement="bottom" title="{{__('Please verify your account')}}"
                              @elseif(!\App\Providers\GenericHelperServiceProvider::creatorCanEarnMoney($user))
                              data-placement="bottom" title="{{__('This creator cannot earn money yet')}}"
                              @else
                              data-placement="bottom" title="{{__('Send a tip')}}"
                              data-toggle="modal"
                              data-target="#checkout-center"
                              data-type="tip"
                              data-first-name="{{Auth::user()->first_name}}"
                              data-last-name="{{Auth::user()->last_name}}"
                              data-billing-address="{{Auth::user()->billing_address}}"
                              data-country="{{Auth::user()->country}}"
                              data-city="{{Auth::user()->city}}"
                              data-state="{{Auth::user()->state}}"
                              data-postcode="{{Auth::user()->postcode}}"
                              data-available-credit="{{Auth::user()->wallet ? Auth::user()->wallet->total : 0}}"
                              data-username="{{$user->username}}"
                              data-name="{{$user->name}}"
                              data-avatar="{{$user->avatar}}"
                              data-recipient-id="{{$user->id}}"
                              @endif
                              aria-label="{{ __('Send a tip') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                        </button>
                        <button type="button" class="pp-tool" onclick="Lists.showListAddModal();" aria-label="{{ __('Add to your lists') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
                        </button>
                    @endif
                    @if(getSetting('profiles.allow_profile_qr_code'))
                        <button type="button" class="pp-tool" onclick="Profile.getProfileQRCode()" aria-label="{{ __('Get profile QR code') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M14 21h7v-4"/></svg>
                        </button>
                    @endif
                    <button type="button" class="pp-tool" onclick="shareOrCopyLink()" aria-label="{{ __('Share profile link') }}">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 13.5 6.8 4M15.4 6.5l-6.8 4"/></svg>
                    </button>
                </div>

                <div class="pp-hero__body">
                    {{-- The dot means DMs are genuinely open to this viewer; the red ring means live. --}}
                    <div class="pp-avatar {{ $hasActiveStream ? 'is-live' : '' }} {{ $canChat ? 'is-online' : '' }}">
                        @if($profileFrame)
                            <span class="{{ $profileFrame }}"><img src="{{ $user->avatar }}" alt=""></span>
                        @else
                            <img src="{{ $user->avatar }}" alt="">
                        @endif
                        @if($hasActiveStream)
                            <span class="pp-avatar__live">{{ __('LIVE') }}</span>
                        @endif
                    </div>

                    <h1 class="pp-name">
                        <span>{{ $user->name }}</span>
                        @if($isVerified)
                            <svg class="pp-name__check" viewBox="0 0 24 24" role="img" aria-label="{{ __('Verified user') }}"><path fill="currentColor" d="M12 2l2.4 2.1 3.2-.3.8 3.1 2.8 1.6-1.2 3 1.2 3-2.8 1.6-.8 3.1-3.2-.3L12 22l-2.4-2.1-3.2.3-.8-3.1-2.8-1.6 1.2-3-1.2-3 2.8-1.6.8-3.1 3.2.3z"/><path d="m8.5 12.2 2.3 2.3 4.7-4.8" fill="none" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        @endif
                    </h1>
                    <div class="pp-handle">
                        <span>{{ '@' . $user->username }}</span>
                        @if($canChat)
                            <span class="pp-status"><span class="pp-status__dot" aria-hidden="true"></span>{{ __('Available to chat') }}</span>
                        @endif
                    </div>

                    @if($user->bio)
                        <div class="profile-description-holder pp-bio">
                            <div class="description-content {{ !getSetting('profiles.disable_profile_bio_excerpt') ? 'line-clamp-3' : '' }}">
                                @if(getSetting('profiles.allow_profile_bio_markdown'))
                                    {!! GenericHelper::parseProfileMarkdownBio($user->bio) !!}
                                @else
                                    {!! GenericHelper::parseSafeHTML($user->bio) !!}
                                @endif
                            </div>
                            @if(!getSetting('profiles.disable_profile_bio_excerpt'))
                                <button type="button" class="pp-more show-more-actions d-none" onclick="Profile.toggleFullDescription()">
                                    <span class="label-more">{{ __('More') }}</span>
                                    <span class="label-less d-none">{{ __('Less') }}</span>
                                </button>
                            @endif
                        </div>
                    @endif

                    {{-- Real counts only; a zero is left out rather than advertised. --}}
                    <div class="pp-stats">
                        @if($userVideos->count())
                            <a href="{{ $tabUrl('videos') }}"><strong>{{ $compact($userVideos->count()) }}</strong> {{ trans_choice('{1} video|[2,*] videos', $userVideos->count()) }}</a>
                        @endif
                        @if($followers)
                            <span><strong>{{ $compact($followers) }}</strong> {{ trans_choice('{1} follower|[2,*] followers', $followers) }}</span>
                        @endif
                        @if($videoLikes)
                            <span><strong>{{ $compact($videoLikes) }}</strong> {{ trans_choice('{1} like|[2,*] likes', $videoLikes) }}</span>
                        @endif
                        @if(isset($posts) && $posts->total())
                            <span><strong>{{ $compact($posts->total()) }}</strong> {{ trans_choice('{1} post|[2,*] posts', $posts->total()) }}</span>
                        @endif
                    </div>

                    <div class="pp-meta">
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            {{ __('Joined') }} {{ ucfirst($user->created_at->translatedFormat('M Y')) }}
                        </span>
                        @if($user->location)
                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                {{ $user->location }}
                            </span>
                        @endif
                        @if(!getSetting('profiles.disable_website_link_on_profile') && $user->website)
                            <a href="{{ $user->website }}" target="_blank" rel="nofollow noopener">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/></svg>
                                {{ Str::limit(str_replace(['https://','http://','www.'], '', $user->website), 28) }}
                            </a>
                        @endif
                        @if(getSetting('profiles.allow_gender_pronouns') && $user->gender_pronoun)
                            <span>{{ $user->gender_pronoun }}</span>
                        @endif
                    </div>

                    @if($hasActiveStream && !empty($liveUrl))
                        <a href="{{ $liveUrl }}" class="pp-live">
                            <span class="pp-live__dot" aria-hidden="true"></span>
                            <span><strong>{{ __('Live now') }}</strong> {{ __('Tap to watch') }}</span>
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                        </a>
                    @endif

                    @if(!$isOwner)
                        {{-- Subscribe jumps to the membership card below rather than cloning a
                             subscribe button: those carry the checkout data attributes. --}}
                        <div class="pp-actions">
                            @if(Auth::check())
                                <button type="button" class="pp-btn pp-btn--glass manage-follow-button" onclick="Lists.manageFollowsAction('{{ $user->id }}')">
                                    <span class="manage-follows-text">{{ \App\Providers\ListsHelperServiceProvider::getUserFollowingType($user->id, true) }}</span>
                                </button>
                            @else
                                <button type="button" class="pp-btn pp-btn--glass" data-toggle="modal" data-target="#login-dialog">{{ __('Follow') }}</button>
                            @endif

                            @if($hasSub)
                                <span class="pp-btn pp-btn--solid is-done">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12.5 4.5 4.5L19 7.5"/></svg>
                                    {{ __('Subscribed') }}
                                </span>
                            @else
                                <a href="#profile-subscribe" class="pp-btn pp-btn--solid">{{ $user->paid_profile ? __('Subscribe') : __('Support') }}</a>
                            @endif
                        </div>

                        {{-- Opens the existing messenger dialog, so sending keeps one code path. --}}
                        @if(Auth::check() && $canChat)
                            <div class="pp-compose">
                                <input type="text" class="pp-compose__input" id="profile-compose-input"
                                       placeholder="{{ __('Hi') }} {{ Str::limit(explode(' ', $user->name)[0], 18, '') }}!"
                                       aria-label="{{ __('Send a message') }}" enterkeyhint="send">
                                <button type="button" class="pp-compose__send" id="profile-compose-send" aria-label="{{ __('Send') }}">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.4 20.4 21 12 3.4 3.6l2.8 8.4zM6.2 12H13"/></svg>
                                </button>
                            </div>
                        @elseif(Auth::check() && !$hasSub)
                            <p class="pp-hint">{{ __('Subscribe to message :name', ['name' => explode(' ', $user->name)[0]]) }}</p>
                        @endif
                    @endif
                </div>
            </section>

            @include('elements.message-alert',['classes'=>'px-3 pt-3'])

            {{-- ============ Membership (keeps the original checkout buttons) ============ --}}
            @if($user->paid_profile && (!getSetting('profiles.allow_users_enabling_open_profiles') || (getSetting('profiles.allow_users_enabling_open_profiles') && !$user->open_profile)))
                @if(!$isOwner && !$hasSub)
                    <section class="pp-card subscription-holder" id="profile-subscribe">
                        <div class="pp-card__head">
                            <div>
                                <span class="pp-eyebrow">{{ __('Membership') }}</span>
                                <h2 class="pp-card__title">{{ __('Unlock everything :name posts', ['name' => explode(' ', $user->name)[0]]) }}</h2>
                            </div>
                            @if($user->profile_access_price)
                                <span class="pp-price"><strong>${{ number_format((float) $user->profile_access_price, 2) }}</strong>/{{ __('mo') }}</span>
                            @endif
                        </div>
                        <ul class="pp-perks">
                            <li>{{ __('Every post and video') }}</li>
                            <li>{{ __('Direct messages') }}</li>
                            <li>{{ __('Cancel any time') }}</li>
                        </ul>
                        @if(count($offer) && $offer['discountAmount']['30'] > 0)
                            <p class="pp-offer">
                                <strong>{{ __('Limited offer main label', ['discount' => round($offer['discountAmount']['30']), 'days_remaining' => $offer['daysRemaining']]) }}</strong>
                                <span>{{ __('Offer ends label', ['date' => $offer['expiresAt']->format('d M')]) }}</span>
                            </p>
                        @endif
                        @if(Auth::check() && !GenericHelper::isEmailEnforcedAndValidated())
                            <p class="pp-hint">{{ __('Your email address is not verified.') }} <a href="{{ route('verification.notice') }}">{{ __('Click here') }}</a> {{ __('to re-send the confirmation email.') }}</p>
                        @endif

                        @include('elements.checkout.subscribe-button-30')

                        <div class="pp-card__foot">
                            @if($user->profile_access_price_6_months || $user->profile_access_price_12_months || $user->profile_access_price_3_months)
                                <button type="button" class="pp-link" onclick="Profile.toggleBundles()">
                                    <span class="label-more">{{ __('Save with a bundle') }}</span>
                                    <span class="label-less d-none">{{ __('Hide bundles') }}</span>
                                    <span class="label-icon">@include('elements.icon',['icon'=>'chevron-down-outline','centered'=>false])</span>
                                </button>
                            @endif
                            @if(count($offer) && $offer['discountAmount']['30'] > 0)
                                <small>{{ __('Regular price label', ['currency' => getSetting('payments.currency_code') ?? 'USD', 'amount' => $user->offer->old_profile_access_price]) }}</small>
                            @endif
                        </div>

                        @if($user->profile_access_price_6_months || $user->profile_access_price_12_months || $user->profile_access_price_3_months)
                            <div class="subscription-bundles d-none">
                                @if($user->profile_access_price_3_months)
                                    @include('elements.checkout.subscribe-button-90')
                                @endif
                                @if($user->profile_access_price_6_months)
                                    @include('elements.checkout.subscribe-button-182')
                                @endif
                                @if($user->profile_access_price_12_months)
                                    @include('elements.checkout.subscribe-button-365')
                                @endif
                            </div>
                        @endif
                    </section>
                @endif
            @endif

            {{-- ============ Tabs ============ --}}
            <nav class="pp-tabs inline-border-tabs" aria-label="{{ __('Profile sections') }}">
                <a href="{{ $tabUrl('videos') }}" class="pp-tab {{ $tab === 'videos' ? 'is-active' : '' }}" @if($tab === 'videos') aria-current="page" @endif>
                    {{ __('Videos') }} @if($userVideos->count())<span class="pp-tab__count">{{ $userVideos->count() }}</span>@endif
                </a>
                <a href="{{ $tabUrl('reposts') }}" class="pp-tab {{ $tab === 'reposts' ? 'is-active' : '' }}" @if($tab === 'reposts') aria-current="page" @endif>
                    {{ __('Reposts') }} @if($repostCount)<span class="pp-tab__count">{{ $repostCount }}</span>@endif
                </a>
                <a href="{{ $tabUrl('shop') }}" class="pp-tab {{ $tab === 'shop' ? 'is-active' : '' }}" @if($tab === 'shop') aria-current="page" @endif>
                    {{ __('Shop') }} @if($shopItems->count())<span class="pp-tab__count">{{ $shopItems->count() }}</span>@endif
                </a>
            </nav>

            <div class="pp-panel">
                @if($tab === 'videos')
                    @if($userVideos->count())
                        <div class="pp-grid">
                            @foreach($userVideos as $video)
                                @include('elements.profile.video-tile', ['video' => $video, 'isOwner' => $isOwner])
                            @endforeach
                        </div>
                    @else
                        @include('elements.profile.empty', [
                            'title' => $isOwner ? __('Post your first video') : __('No videos yet'),
                            'text' => $isOwner ? __('Short clips do best. It goes straight into the feed.') : __(':name hasn\'t posted a video yet.', ['name' => explode(' ', $user->name)[0]]),
                            'cta' => $isOwner ? ['label' => __('Upload video'), 'href' => url('/create')] : null,
                        ])
                    @endif

                @elseif($tab === 'reposts')
                    @php
                        $reposts = collect();
                        try {
                            $reposts = \App\Models\Video::query()
                                ->join('video_reposts as vr', 'vr.video_id', '=', 'videos.id')
                                ->where('vr.user_id', $user->id)
                                ->where('videos.is_public', 1)
                                ->where('videos.status', 'published')
                                ->orderByDesc('vr.reposted_at')
                                ->select('videos.*')
                                ->get();
                        } catch (\Throwable $e) {
                            $reposts = collect();
                        }
                    @endphp
                    @if($reposts->count())
                        <div class="pp-grid">
                            @foreach($reposts as $video)
                                @include('elements.profile.video-tile', ['video' => $video, 'isRepost' => true])
                            @endforeach
                        </div>
                    @else
                        @include('elements.profile.empty', [
                            'title' => __('No reposts yet'),
                            'text' => $isOwner ? __('Tap repost on a video you love and it shows up here.') : __(':name hasn\'t reposted anything yet.', ['name' => explode(' ', $user->name)[0]]),
                            'cta' => null,
                        ])
                    @endif

                @else
                    @if($shopItems->count())
                        <div class="nx-grid pp-shop">
                            @foreach($shopItems as $nft)
                                @include('nft.partials.card', ['nft' => $nft, 'note' => $nft->isPendingMint() ? __('Being created') : null])
                            @endforeach
                        </div>
                    @else
                        @include('elements.profile.empty', [
                            'title' => __('Nothing in the shop yet'),
                            'text' => $isOwner ? __('Turn one of your posts into a collectible fans can buy.') : __(':name hasn\'t listed anything for sale yet.', ['name' => explode(' ', $user->name)[0]]),
                            'cta' => $isOwner ? ['label' => __('Create a collectible'), 'href' => route('nft.mintable')] : null,
                        ])
                    @endif
                @endif
            </div>
        </div>

        <div class="col-12 col-md-4 d-none d-md-block pt-3">
            @include('elements.profile.widgets')
        </div>
    </div>

    <div class="d-none">
        <ion-icon name="heart"></ion-icon>
        <ion-icon name="heart-outline"></ion-icon>
    </div>

    @if(Auth::check())
        @include('elements.lists.list-add-user-dialog',['user_id' => $user->id, 'lists' => ListsHelper::getUserLists()])
        @include('elements.checkout.checkout-box')
        @include('elements.messenger.send-user-message',['receiver'=>$user])
    @else
        @include('elements.modal-login')
    @endif

    @include('elements.profile.qr-code-dialog')

    <script>
    (function () {
        // Sticky tabs sit directly under the mobile app bar, whatever height it renders.
        var bar = document.querySelector('.mobile-app-bar');
        var root = document.querySelector('.pp');
        if (bar && root) root.style.setProperty('--pp-top', (getComputedStyle(bar).display === 'none' ? 0 : bar.offsetHeight) + 'px');
    })();
    (function () {
        var input = document.getElementById('profile-compose-input');
        var send = document.getElementById('profile-compose-send');
        if (!input || !send) return;

        function open() {
            // Hand off to the existing messenger dialog, carrying the typed text over.
            if (window.messenger && typeof messenger.showNewMessageDialog === 'function') {
                messenger.showNewMessageDialog();
                var text = input.value.trim();
                if (text) {
                    setTimeout(function () {
                        var box = document.querySelector('#new-message-dialog textarea, #new-message-dialog input[type="text"]');
                        if (box) { box.value = text; box.dispatchEvent(new Event('input', { bubbles: true })); }
                    }, 300);
                }
            }
        }

        send.addEventListener('click', open);
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') { e.preventDefault(); open(); }
        });
    })();
    </script>
@stop
