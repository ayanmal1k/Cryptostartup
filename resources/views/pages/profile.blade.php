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
            '/css/pages/profile.css',
            '/css/pages/lists.css',
            '/css/posts/post.css',
            '/css/frames.css'
         ])->withFullUrl()
    !!}
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
@endphp

@section('content')
    <div class="row profile-page-v2 {{ $isDarkTheme ? 'profile-page-v2--dark' : 'profile-page-v2--light' }}">
        <div class="min-vh-100 col-12 col-md-8 border-right pr-md-0">

            <div class="">
                <div class="profile-cover-bg">
                    <img class="card-img-top centered-and-cropped" src="{{$user->cover}}">
                </div>
            </div>

            <div class="container d-flex justify-content-between align-items-center">
                {{-- is-online drives the presence dot, and is only set when DMs are genuinely
                     reachable for this viewer — not a decorative always-green badge. --}}
                <div class="z-index-3 avatar-holder {{ ($hasSub || $viewerHasChatAccess) ? 'is-online' : '' }}">
                    {{-- A frame from the rewards shop or a drop, when the user has one on. --}}
                    @php
                        $profileFrame = \App\Services\PerkShopService::frameClass($user);
                    @endphp
                    @if($profileFrame)
                        <span class="{{ $profileFrame }}"><img src="{{$user->avatar}}" class="rounded-circle"></span>
                    @else
                        <img src="{{$user->avatar}}" class="rounded-circle">
                    @endif
                </div>
                <div>
                    @if(!Auth::check() || Auth::user()->id !== $user->id)
                        <div class="d-flex flex-row">
                            @if(Auth::check())
                                <div class="">
                                <span class="p-pill ml-2 pointer-cursor to-tooltip"
                                      @if(!Auth::user()->email_verified_at && getSetting('site.enforce_email_validation'))
                                      data-placement="top"
                                      title="{{__('Please verify your account')}}"
                                      @elseif(!\App\Providers\GenericHelperServiceProvider::creatorCanEarnMoney($user))
                                      data-placement="top"
                                      title="{{__('This creator cannot earn money yet')}}"
                                      @else
                                      data-placement="top"
                                      title="{{__('Send a tip')}}"
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
                                >
                                 @include('elements.icon',['icon'=>'cash-outline'])
                                </span>
                                </div>
                                <div class="">
                                    @if($hasSub || $viewerHasChatAccess)
                                        <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('Send a message')}}" onclick="messenger.showNewMessageDialog()">
                                            @include('elements.icon',['icon'=>'chatbubbles-outline'])
                                        </span>
                                    @else
                                        <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('DMs unavailable without subscription')}}">
                                        @include('elements.icon',['icon'=>'chatbubbles-outline'])
                                    </span>
                                    @endif
                                </div>
                                <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('Add to your lists')}}" onclick="Lists.showListAddModal();">
                                 @include('elements.icon',['icon'=>'list-outline'])
                            </span>
                            @endif
                            @if(getSetting('profiles.allow_profile_qr_code'))
                                <div>
                                    <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('Get profile QR code')}}" onclick="Profile.getProfileQRCode()">
                                        @include('elements.icon',['icon'=>'qr-code-outline'])
                                    </span>
                                </div>
                            @endif
                            <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('Share profile link')}}" onclick="shareOrCopyLink()">
                                 @include('elements.icon',['icon'=>'share-social-outline'])
                            </span>
                        </div>
                    @else
                        <div class="d-flex flex-row">
                            <div class="mr-2">
                                <a href="{{route('my.settings')}}" class="p-pill p-pill-text ml-2 pointer-cursor">
                                    @include('elements.icon',['icon'=>'settings-outline','classes'=>'mr-1'])
                                    <span class="d-none d-md-block">{{__('Edit profile')}}</span>
                                    <span class="d-block d-md-none">{{__('Edit')}}</span>
                                </a>
                            </div>
                            @if(getSetting('profiles.allow_profile_qr_code'))
                                <div>
                                    <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('Get profile QR code')}}" onclick="Profile.getProfileQRCode()">
                                        @include('elements.icon',['icon'=>'qr-code-outline'])
                                    </span>
                                </div>
                            @endif
                            <div>
                                <span class="p-pill ml-2 pointer-cursor" data-toggle="tooltip" data-placement="top" title="{{__('Share profile link')}}" onclick="shareOrCopyLink()">
                                    @include('elements.icon',['icon'=>'share-social-outline'])
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="container pt-2 pl-0 pr-0">

                <div class="pt-2 pl-4 pr-4 profile-identity">
                    <h5 class="text-bold d-flex align-items-center">
                        <span>{{$user->name}}</span>
                        @if($user->email_verified_at && $user->birthdate && ($user->verification && $user->verification->status == 'verified'))
                            <span data-toggle="tooltip" data-placement="top" title="{{__('Verified user')}}">
                                @include('elements.icon',['icon'=>'checkmark-circle-outline','centered'=>true,'classes'=>'ml-1 text-primary'])
                            </span>
                        @endif
                        @if($hasActiveStream)
                            <span data-toggle="tooltip" data-placement="right" title="{{__('Live streaming')}}">
                            <div class="blob red ml-3"></div>
                            </span>
                        @endif
                    </h5>
                    <h6 class="text-muted"><span class="text-bold"><span>@</span>{{$user->username}}</span> {{--- Last seen X time ago--}}</h6>

                    {{-- "Available to chat" is only claimed when DMs are actually reachable for
                         this viewer, so it never promises something the profile can't honour. --}}
                    @if($hasSub || $viewerHasChatAccess)
                        <span class="profile-status">
                            <span class="profile-status__dot" aria-hidden="true"></span>
                            {{ __('Available to chat') }}
                        </span>
                    @endif
                </div>

                <div class="pt-2 pb-2 pl-4 pr-4 profile-description-holder">
                    <div class="description-content {{$user->bio && !getSetting('profiles.disable_profile_bio_excerpt') ? 'line-clamp-3' : ''}}">
                        @if($user->bio)
                            @if(getSetting('profiles.allow_profile_bio_markdown'))
                                {!!  GenericHelper::parseProfileMarkdownBio($user->bio) !!}
                            @else
                                {!!GenericHelper::parseSafeHTML($user->bio)!!}
                            @endif
                        @else
                            {{__('No description available.')}}
                        @endif
                    </div>
                    @if($user->bio && !getSetting('profiles.disable_profile_bio_excerpt'))
                        <span class="text-primary pointer-cursor show-more-actions d-none" onclick="Profile.toggleFullDescription()">
                            <span class="label-more">{{__('More info')}}</span>
                            <span class="label-less d-none">{{__('Show less')}}</span>
                        </span>
                    @endif
                </div>

                {{-- Follow / Subscribe pair, as in the reference. Subscribe deliberately jumps
                     to the existing .subscription-holder below instead of duplicating a
                     subscribe button — those carry the checkout data attributes that drive
                     payment, and cloning them would risk the payment flow. --}}
                @if(!Auth::check() || Auth::user()->id !== $user->id)
                    <div class="profile-hero-actions">
                        @if(Auth::check())
                            <button type="button" class="pf-btn pf-btn--follow manage-follow-button"
                                    onclick="Lists.manageFollowsAction('{{$user->id}}')">
                                <span class="manage-follows-text">{{ \App\Providers\ListsHelperServiceProvider::getUserFollowingType($user->id, true) }}</span>
                            </button>
                        @else
                            <button type="button" class="pf-btn pf-btn--follow"
                                    data-toggle="modal" data-target="#login-dialog">{{ __('Follow') }}</button>
                        @endif

                        @if($hasSub)
                            <span class="pf-btn pf-btn--subscribe">{{ __('Subscribed') }}</span>
                        @elseif($user->paid_profile)
                            <a href="#profile-subscribe" class="pf-btn pf-btn--subscribe">{{ __('Subscribe') }}</a>
                        @else
                            <a href="#profile-subscribe" class="pf-btn pf-btn--subscribe">{{ __('Support') }}</a>
                        @endif
                    </div>

                    {{-- Inline composer. Opens the existing messenger dialog rather than
                         posting on its own, so message sending keeps one code path. --}}
                    @if(Auth::check() && ($hasSub || $viewerHasChatAccess))
                        <div class="profile-compose">
                            <input type="text" class="profile-compose__input" id="profile-compose-input"
                                   placeholder="{{ __('Hi') }} {{ Str::limit($user->name, 18, '') }}!"
                                   aria-label="{{ __('Send a message') }}">
                            <button type="button" class="profile-compose__send" id="profile-compose-send"
                                    aria-label="{{ __('Send') }}">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4 20-7z"/></svg>
                            </button>
                        </div>
                    @endif
                @endif

                @isset($posts)
                    <div class="profile-postcount">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                        {{ number_format($posts->total()) }} {{ trans_choice('post|posts', $posts->total()) }}
                    </div>
                @endisset

                <div class="d-flex flex-column flex-md-row justify-content-md-between pb-2 pl-4 pr-4 mb-3 mt-1">

                    <div class="d-flex align-items-center mr-2 text-truncate mb-0 mb-md-0">
                        @include('elements.icon',['icon'=>'calendar-clear-outline','centered'=>false,'classes'=>'mr-1'])
                        <div class="text-truncate ml-1">
                            {{ucfirst($user->created_at->translatedFormat('F d'))}}
                        </div>
                    </div>
                    @if($user->location)
                        <div class="d-flex align-items-center mr-2 text-truncate mb-0 mb-md-0">
                            @include('elements.icon',['icon'=>'location-outline','centered'=>false,'classes'=>'mr-1'])
                            <div class="text-truncate ml-1">
                                {{$user->location}}
                            </div>
                        </div>
                    @endif
                    @if(!getSetting('profiles.disable_website_link_on_profile'))
                        @if($user->website)
                            <div class="d-flex align-items-center mr-2 text-truncate mb-0 mb-md-0">
                                @include('elements.icon',['icon'=>'globe-outline','centered'=>false,'classes'=>'mr-1'])
                                <div class="text-truncate ml-1">
                                    <a href="{{$user->website}}" target="_blank" rel="nofollow">
                                        {{str_replace(['https://','http://','www.'],'',$user->website)}}
                                    </a>
                                </div>
                            </div>
                        @endif
                    @endif
                    @if(getSetting('profiles.allow_gender_pronouns'))
                        @if($user->gender_pronoun)
                            <div class="d-flex align-items-center mr-2 text-truncate mb-0 mb-md-0">
                                @include('elements.icon',['icon'=>'male-female-outline','centered'=>false,'classes'=>'mr-1'])
                                <div class="text-truncate ml-1">
                                    {{$user->gender_pronoun}}
                                </div>
                            </div>
                        @endif
                    @endif

                </div>

                <div class="bg-separator border-top border-bottom"></div>

                @include('elements.message-alert',['classes'=>'px-2 pt-4'])
                @if($user->paid_profile && (!getSetting('profiles.allow_users_enabling_open_profiles') || (getSetting('profiles.allow_users_enabling_open_profiles') && !$user->open_profile)))
                    @if( (!Auth::check() || Auth::user()->id !== $user->id) && !$hasSub)
                        <div class="p-4 subscription-holder" id="profile-subscribe">
                            <h6 class="font-weight-bold text-uppercase mb-3">{{__('Subscription')}}</h6>
                            @if(count($offer) && $offer['discountAmount']['30'] > 0)
                                <h5 class="m-0 text-bold">{{__('Limited offer main label',['discount'=> round($offer['discountAmount']['30']), 'days_remaining'=> $offer['daysRemaining'] ])}}</h5>
                                <small class="">{{__('Offer ends label',['date'=>$offer['expiresAt']->format('d M')])}}</small>
                            @endif
                            @if($hasSub)
                                <button class="btn btn-round btn-lg btn-primary btn-block mt-3 mb-2 text-center">
                                    <span>{{__('Subscribed')}}</span>
                                </button>
                            @else

                                @if(Auth::check())
                                    @if(!GenericHelper::isEmailEnforcedAndValidated())
                                        <i>{{__('Your email address is not verified.')}} <a href="{{route('verification.notice')}}">{{__("Click here")}}</a> {{__("to re-send the confirmation email.")}}</i>
                                    @endif
                                @endif

                                @include('elements.checkout.subscribe-button-30')
                                <div class="d-flex justify-content-between">
                                    @if($user->profile_access_price_6_months || $user->profile_access_price_12_months)
                                        <small>
                                            <div class="pointer-cursor d-flex align-items-center" onclick="Profile.toggleBundles()">
                                                <div class="label-more">{{__('Subscriptions bundles')}}</div>
                                                <div class="label-less d-none">{{__('Hide bundles')}}</div>
                                                <div class="ml-1 label-icon">
                                                    @include('elements.icon',['icon'=>'chevron-down-outline','centered'=>false])
                                                </div>
                                            </div>
                                        </small>
                                    @endif
                                    @if(count($offer) && $offer['discountAmount']['30'] > 0)
                                        <small class="">{{__('Regular price label',['currency'=> getSetting('payments.currency_code') ?? 'USD','amount'=>$user->offer->old_profile_access_price])}}</small>
                                    @endif
                                </div>

                                @if($user->profile_access_price_6_months || $user->profile_access_price_12_months || $user->profile_access_price_3_months)
                                    <div class="subscription-bundles d-none mt-4">
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
                            @endif
                        </div>
                        <div class="bg-separator border-top border-bottom"></div>
                    @endif
                @elseif(!Auth::check() || (Auth::check() && Auth::user()->id !== $user->id))
                    <div class=" p-4 subscription-holder" id="profile-subscribe">
                        <h6 class="font-weight-bold text-uppercase mb-3">{{__('Follow this creator')}}</h6>
                        @if(Auth::check())
                            <button class="btn btn-round btn-lg btn-primary btn-block mt-3 mb-0 manage-follow-button" onclick="Lists.manageFollowsAction('{{$user->id}}')">
                                <span class="manage-follows-text">{{\App\Providers\ListsHelperServiceProvider::getUserFollowingType($user->id, true)}}</span>
                            </button>
                        @else
                            <button class="btn btn-round btn-lg btn-primary btn-block mt-3 mb-0 text-center"
                                    data-toggle="modal"
                                    data-target="#login-dialog"
                            >
                                <span class="">{{__('Follow')}}</span>
                            </button>
                        @endif
                    </div>
                    <div class="bg-separator border-top border-bottom"></div>
                @endif
                
                {{-- TikTok-style Navigation Tabs --}}
                <div class="mt-3 tiktok-style-tabs">
                    <nav class="nav nav-pills nav-justified text-bold">
                        <a class="nav-item nav-link {{$activeFilter == 'videos' || $activeFilter == false ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username, 'filter' => 'videos'])}}">
                            @include('elements.icon',['icon'=>'videocam-outline'])
                        </a>
                        
                        <a class="nav-item nav-link {{$activeFilter == 'marketplace' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username, 'filter' => 'marketplace'])}}">
                            @include('elements.icon',['icon'=>'storefront-outline'])
                        </a>
                        
                        <a class="nav-item nav-link {{$activeFilter == 'reposts' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username, 'filter' => 'reposts'])}}">
                            @include('elements.icon',['icon'=>'repeat-outline'])
                        </a>
                    </nav>
                </div>

                {{-- Content based on active tab --}}
                <div class="justify-content-center align-items-center mt-4">
                    @if($activeFilter == 'videos' || $activeFilter == false)
                        {{-- Videos Tab Content - Instagram/TikTok Style Grid --}}
                        @php
                            // Get user's videos
                            $userVideos = collect();
                            try {
                                $userVideos = \App\Models\Video::where('user_id', $user->id)
                                    ->with(['user'])
                                    ->latest()
                                    ->get();
                            } catch (Exception $e) {
                                $userVideos = collect();
                            }
                        @endphp
                        
                        <div class="videos-grid-container">
                            @if($userVideos && $userVideos->count() > 0)
                                <div class="videos-grid">
                                    @foreach($userVideos as $video)
                                        <div class="video-grid-item" onclick="window.location.href='{{url('/feed?video=' . $video->id)}}'">
                                            <div class="video-thumbnail">
                                                <video preload="metadata" muted>
                                                    <source src="{{$video->video_url}}" type="video/mp4">
                                                </video>
                                                <div class="video-overlay-info">
                                                    <div class="video-stats">
                                                        <span class="views-count">
                                                            @include('elements.icon',['icon'=>'play-outline'])
                                                            {{number_format($video->views_count ?? rand(1000, 999999))}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="no-videos-placeholder">
                                    <div class="text-center p-5">
                                        @include('elements.icon',['icon'=>'videocam-outline','classes'=>'text-muted mb-3','style'=>'font-size: 4rem;'])
                                        <h5 class="text-muted mb-2">No videos yet</h5>
                                        <p class="text-muted">{{$user->name}} hasn't shared any videos</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @elseif($activeFilter == 'marketplace')
                        {{-- Marketplace Tab Content --}}
                        <div class="marketplace-content">
                            <div class="text-center p-4">
                                @include('elements.icon',['icon'=>'storefront-outline','classes'=>'text-muted mb-3','style'=>'font-size: 3rem;'])
                                <h5 class="text-muted">Marketplace</h5>
                                <!-- <p class="text-muted">Marketplace items will be displayed here</p> -->
                            </div>
                        </div>
                    @elseif($activeFilter == 'reposts')
                        {{-- Reposts Tab Content - Show Reposted Videos --}}
                        @php
                            // Get user's reposted videos using your video_reposts table
                            $repostedVideos = collect();
                            try {
                                $repostedVideos = \DB::table('video_reposts as vr')
                                    ->join('videos as v', 'vr.video_id', '=', 'v.id')
                                    ->join('users as u', 'v.user_id', '=', 'u.id')
                                    ->where('vr.user_id', $user->id)
                                    ->where('v.is_public', 1)
                                    ->where('v.status', 'published')
                                    ->select([
                                        'v.id',
                                        'v.title',
                                        'v.description', 
                                        'v.video_path',
                                        'v.views_count',
                                        'u.name as original_user_name',
                                        'u.username as original_username',
                                        'vr.reposted_at',
                                        \DB::raw('(SELECT COUNT(*) FROM video_likes WHERE video_id = v.id) as likes_count'),
                                        \DB::raw('(SELECT COUNT(*) FROM video_comments WHERE video_id = v.id) as comments_count')
                                    ])
                                    ->orderBy('vr.reposted_at', 'desc')
                                    ->get()
                                    ->map(function($video) {
                                        $video->video_url = asset('storage/' . $video->video_path);
                                        // Create a username if it doesn't exist
                                        if (!$video->original_username) {
                                            $video->original_username = strtolower(str_replace(' ', '', $video->original_user_name));
                                        }
                                        return $video;
                                    });
                            } catch (Exception $e) {
                                $repostedVideos = collect();
                            }
                        @endphp
                        
                        <div class="reposts-grid-container">
                            @if($repostedVideos && $repostedVideos->count() > 0)
                                <div class="videos-grid">
                                    @foreach($repostedVideos as $video)
                                        <div class="video-grid-item" onclick="window.location.href='{{url('/feed?video=' . $video->id)}}'">
                                            <div class="video-thumbnail">
                                                <video preload="metadata" muted>
                                                    <source src="{{$video->video_url}}" type="video/mp4">
                                                </video>
                                                
                                                {{-- Repost indicator --}}
                                                <div class="repost-indicator">
                                                    @include('elements.icon',['icon'=>'repeat-outline'])
                                                </div>
                                                
                                                {{-- Original creator info --}}
                                                <!-- <div class="original-creator">
                                                    <small>by @{{$video->original_username}}</small>
                                                </div> -->
                                                
                                                <div class="video-overlay-info">
                                                    <div class="video-stats">
                                                        <span class="views-count">
                                                            @include('elements.icon',['icon'=>'play-outline'])
                                                            {{number_format($video->views_count ?? 0)}}
                                                        </span>
                                                    </div>
                                                    <div class="video-actions">
                                                        <span class="likes-count">
                                                            @include('elements.icon',['icon'=>'heart-outline'])
                                                            {{number_format($video->likes_count ?? 0)}}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="no-videos-placeholder">
                                    <div class="text-center p-5">
                                        @include('elements.icon',['icon'=>'repeat-outline','classes'=>'text-muted mb-3','style'=>'font-size: 4rem;'])
                                        <h5 class="text-muted mb-2">No reposts yet</h5>
                                        <p class="text-muted">{{$user->name}} hasn't reposted any videos</p>
                                        @if(Auth::check() && Auth::user()->id === $user->id)
                                            <p class="text-muted mt-2">
                                                <small>Find videos you like and tap the repost button to share them here!</small>
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
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

    {{-- Video Modal for Full Screen Playback --}}
    <div class="video-modal" id="video-modal">
        <div class="video-modal-content">
            <button class="video-modal-close" onclick="closeVideoModal()">
                @include('elements.icon',['icon'=>'close-outline'])
            </button>
            <div class="video-modal-player">
                <video id="modal-video" controls autoplay>
                    <source id="modal-video-source" src="" type="video/mp4">
                </video>
            </div>
            <div class="video-modal-info">
                <h4 id="modal-video-title"></h4>
                <p id="modal-video-description"></p>
            </div>
        </div>
    </div>

    <style>
        /* TikTok-style tabs - icons only */
        .tiktok-style-tabs {
            border-bottom: 1px solid var(--pf-border, rgba(255,255,255,0.08));
        }

        .tiktok-style-tabs .nav-link {
            padding: 15px;
            font-size: 24px;
            border: none;
            border-bottom: 2px solid transparent;
            background: transparent;
            color: var(--pf-text-muted, #71717a);
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 58px;
        }
        
        .profile-page-v2 .tiktok-style-tabs .nav-link.active {
            color: var(--pf-accent, #cb0c9f);
            border-bottom: 2px solid var(--pf-accent, #cb0c9f);
            background: transparent;
        }
        
        .profile-page-v2 .tiktok-style-tabs .nav-link:hover {
            color: var(--pf-text, #f4f4f5);
            background: transparent;
        }
        
        .tiktok-style-tabs .nav-link ion-icon {
            font-size: 24px;
        }
        
        /* Videos Grid - Exact TikTok Style */
        .videos-grid-container,
        .reposts-grid-container {
            padding: 0;
            background: transparent;
        }
        
        .videos-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 4px;
            margin: 0;
            background: transparent;
        }
        
        .video-grid-item {
            position: relative;
            aspect-ratio: 9/16;
            background: var(--pf-surface-muted, #1c1c24);
            cursor: pointer;
            overflow: hidden;
            border-radius: 10px;
            transition: transform 0.2s ease;
            height: 0;
            padding-bottom: 177.78%; /* 16:9 aspect ratio = 56.25%, 9:16 = 177.78% */
        }
        
        .video-grid-item:hover {
            transform: translateY(-2px);
        }
        
        .video-thumbnail {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .video-thumbnail video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .video-overlay-info {
            position: absolute;
            bottom: 8px;
            left: 8px;
            right: 8px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .video-stats {
            display: flex;
            align-items: center;
            color: white;
            font-size: 13px;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
            background: rgba(0, 0, 0, 0.3);
            padding: 4px 8px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
        }
        
        .video-actions {
            display: flex;
            align-items: center;
            color: white;
            font-size: 13px;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
            background: rgba(0, 0, 0, 0.3);
            padding: 4px 8px;
            border-radius: 12px;
            backdrop-filter: blur(4px);
        }
        
        .views-count,
        .likes-count {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        /* Repost indicator */
        .repost-indicator {
            position: absolute;
            top: 8px;
            right: 8px;
            color: white;
            font-size: 16px;
            background: rgba(23, 191, 99, 0.8);
            padding: 4px;
            border-radius: 50%;
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            box-shadow: 0 2px 8px rgba(23, 191, 99, 0.3);
        }
        
        /* Original creator info */
        .original-creator {
            position: absolute;
            top: 8px;
            left: 8px;
            color: white;
            background: rgba(0, 0, 0, 0.6);
            padding: 2px 6px;
            border-radius: 8px;
            font-size: 10px;
            backdrop-filter: blur(4px);
            font-weight: 500;
        }
        
        .play-icon {
            position: absolute;
            bottom: 8px;
            right: 8px;
            color: white;
            font-size: 16px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.8);
        }
        
        /* No videos placeholder */
        .no-videos-placeholder {
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Video Modal */
        .video-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 2000;
            display: none;
            align-items: center;
            justify-content: center;
        }
        
        .video-modal.active {
            display: flex;
        }
        
        .video-modal-content {
            position: relative;
            max-width: 90%;
            max-height: 90%;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
        }
        
        .video-modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 24px;
            cursor: pointer;
            z-index: 2001;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        
        .video-modal-close:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .video-modal-player {
            position: relative;
        }
        
        .video-modal-player video {
            width: 100%;
            height: auto;
            max-height: 70vh;
            display: block;
        }
        
        .video-modal-info {
            padding: 20px;
            background: #000;
            color: white;
        }
        
        .video-modal-info h4 {
            margin: 0 0 10px 0;
            font-size: 18px;
            font-weight: 600;
        }
        
        .video-modal-info p {
            margin: 0;
            font-size: 14px;
            opacity: 0.8;
            line-height: 1.4;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .video-grid-item {
                padding-bottom: 177.78%; /* Maintain aspect ratio on mobile */
            }
            
            .video-stats,
            .video-actions {
                font-size: 11px;
                padding: 3px 6px;
            }
            
            .repost-indicator {
                width: 24px;
                height: 24px;
                font-size: 14px;
            }
            
            .original-creator {
                font-size: 9px;
                padding: 1px 4px;
            }
            
            .video-modal-content {
                max-width: 95%;
                max-height: 95%;
            }
            
            .video-modal-player video {
                max-height: 60vh;
            }
            
            .video-modal-info {
                padding: 15px;
            }
            
            .video-modal-info h4 {
                font-size: 16px;
            }
            
            .video-modal-info p {
                font-size: 13px;
            }
        }
        
        /* Very small screens */
        @media (max-width: 480px) {
            .video-grid-item {
                padding-bottom: 177.78%; /* Maintain aspect ratio on small screens */
            }
            
            .tiktok-style-tabs .nav-link {
                padding: 12px;
                font-size: 20px;
            }
            
            .video-stats,
            .video-actions {
                font-size: 10px;
                padding: 2px 5px;
            }
            
            .repost-indicator {
                width: 20px;
                height: 20px;
                font-size: 12px;
            }
            
            .original-creator {
                font-size: 8px;
                padding: 1px 3px;
            }
        }
        /* Hide bottom navigation completely */
.bottom-nav,
.bottom-navigation,
.nav-bottom,
.mobile-nav,
.mobile-navigation,
.fixed-bottom,
.navbar-bottom,
.tab-bar,
.bottom-bar,
.footer-nav,
nav[class*="bottom"],
.navigation-bottom,
.app-bottom-nav {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
}

/* Hide any fixed positioned elements at bottom */
.fixed-bottom,
[style*="position: fixed"][style*="bottom: 0"],
[style*="position:fixed"][style*="bottom:0"] {
    display: none !important;
}

/* Additional comprehensive bottom menu hiding */
.bottom-menu,
.menu-bottom,
.nav-bar-bottom,
.navbar-fixed-bottom,
.footer-fixed,
.sticky-bottom,
.bottom-tabs,
.tab-navigation,
.mobile-tabs,
.app-navigation,
.bottom-toolbar,
.toolbar-bottom {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    height: 0 !important;
    max-height: 0 !important;
    overflow: hidden !important;
}

/* Force hide any element with bottom positioning */
*[style*="bottom: 0"],
*[style*="bottom:0"],
*[class*="bottom"][class*="nav"],
*[class*="bottom"][class*="menu"],
*[id*="bottom"][id*="nav"],
*[id*="bottom"][id*="menu"] {
    display: none !important;
}

/* Remove bottom padding/margin that might be reserved for bottom nav */
body {
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}

/* Ensure main content uses full height */
.main-content,
.content,
.container {
    padding-bottom: 0 !important;
    margin-bottom: 0 !important;
}
    </style>

    <script>
        function openVideoModal(videoId, videoUrl, title, description) {
            const modal = document.getElementById('video-modal');
            const modalVideo = document.getElementById('modal-video');
            const modalVideoSource = document.getElementById('modal-video-source');
            const modalTitle = document.getElementById('modal-video-title');
            const modalDescription = document.getElementById('modal-video-description');
            
            modalVideoSource.src = videoUrl;
            modalVideo.load();
            modalTitle.textContent = title || 'Untitled Video';
            modalDescription.textContent = description || '';
            
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function closeVideoModal() {
            const modal = document.getElementById('video-modal');
            const modalVideo = document.getElementById('modal-video');
            
            modal.classList.remove('active');
            modalVideo.pause();
            modalVideo.currentTime = 0;
            document.body.style.overflow = '';
        }
        
        // Close modal when clicking outside
        document.getElementById('video-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeVideoModal();
            }
        });
        
        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeVideoModal();
            }
        });
        
        // Load video thumbnails on page load
        document.addEventListener('DOMContentLoaded', function() {
            const videoThumbnails = document.querySelectorAll('.video-thumbnail video');
            videoThumbnails.forEach(video => {
                video.addEventListener('loadedmetadata', function() {
                    this.currentTime = 1; // Seek to 1 second for thumbnail
                });
            });
        });
    </script>

@stop

<script>
(function () {
    var input = document.getElementById('profile-compose-input');
    var send = document.getElementById('profile-compose-send');
    if (!input || !send) return;

    function open() {
        // Hand off to the existing messenger dialog; the typed text is carried over
        // when the dialog exposes a field for it.
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
