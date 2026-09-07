<nav class="sidebar {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'light') : (Cookie::get('app_theme') == 'dark' ? '' : 'light'))}}">

    <!-- close sidebar menu -->
    <div class="col-12 pb-1">
        <div class="dismiss d-flex justify-content-center align-items-center flex-row">
            @include('elements.icon',['icon'=>'arrow-back','variant'=>'medium'])
        </div>
    </div>

    <div class="col-12 sidebar-wrapper">

        <div class="mb-4 d-flex flex-row-no-rtl">
            <div>
                @if(Auth::check())
                    <img src="{{Auth::user()->avatar}}" class="rounded-circle user-avatar">
                @else
                    <div class="avatar-placeholder">
                        @include('elements.icon',['icon'=>'person-circle','variant'=>'xlarge'])
                    </div>
                @endif
            </div>
            <div class="pl-2 d-flex justify-content-center flex-column">
                @if(Auth::check())
                    <div class=""><span class=""><span>@</span>{{Auth::check() ? Auth::user()->username : '@username'}}</span></div>
                    <small class="p-0 m-0">{{trans_choice('fans', Auth::user()->fansCount, ['number'=> count(ListsHelper::getUserFollowers(Auth::user()->id))])}} - {{trans_choice('following', Auth::user()->followingCount, ['number'=>Auth::user()->followingCount])}}</small>
                @endif
            </div>
        </div>

        {{-- Gamification: level + daily streak + XP progress --}}
        @auth
        <div class="px-1 mb-4">
            <div class="d-flex align-items-center justify-content-between" style="font-size:.9rem;font-weight:600;">
                <span>{{ __('Level') }} {{ Auth::user()->level ?? 1 }}</span>
                <span style="color:#830866;">🔥 {{ Auth::user()->streak_count ?? 0 }} {{ __('day streak') }}</span>
            </div>
            <div style="height:7px;background:rgba(0,0,0,.08);border-radius:6px;overflow:hidden;margin-top:6px;">
                <div style="height:100%;width:{{ (int)(Auth::user()->xp ?? 0) % 100 }}%;background:linear-gradient(135deg,#830866,#a10a7f);border-radius:6px;"></div>
            </div>
            <div style="font-size:.72rem;color:#888;margin-top:3px;">{{ (int)(Auth::user()->xp ?? 0) % 100 }}/100 XP {{ __('to next level') }}</div>
        </div>
        @endauth
    </div>

    <ul class="list-unstyled menu-elements p-0">
        @if(GenericHelper::isEmailEnforcedAndValidated())
            <li class="{{Route::currentRouteName() == 'feed' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('feed')}}">
                    @include('elements.icon',['icon'=>'home-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Home')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'videos.reels' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('videos.reels')}}">
                    @include('elements.icon',['icon'=>'film-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Reels')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'engagement.hub' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('engagement.hub')}}">
                    @include('elements.icon',['icon'=>'gift-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Rewards')}}
                    @if(($gmRewardsBadge ?? 0) > 0)
                        <span class="badge badge-pill badge-danger ml-2">{{ $gmRewardsBadge }}</span>
                    @endif
                </a>
            </li>
            <li class="{{Route::currentRouteName() == 'gamification.achievements' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('gamification.achievements')}}">
                    @include('elements.icon',['icon'=>'trophy-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Achievements')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'gamification.leaderboard' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('gamification.leaderboard')}}">
                    @include('elements.icon',['icon'=>'podium-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Leaderboard')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'profile' && (request()->route("username") == Auth::user()->username) ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('profile',['username'=>Auth::user()->username])}}">
                    @include('elements.icon',['icon'=>'person-circle-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('My profile')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'my.notifications' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center justify-content-between" href="{{route('my.notifications')}}">
                    <span class="d-flex align-items-center">
                        @include('elements.icon',['icon'=>'notifications-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                        {{__('Notifications')}}
                    </span>
                    <span class="menu-notification-badge {{ NotificationsHelper::getUnreadNotifications()->total > 0 ? '' : 'd-none' }}">{{ NotificationsHelper::getUnreadNotifications()->total }}</span>
                </a>
            </li>
            <li class="{{Route::currentRouteName() == 'my.messenger.get' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center justify-content-between" href="{{route('my.messenger.get')}}">
                    <span class="d-flex align-items-center">
                        @include('elements.icon',['icon'=>'chatbubble-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                        {{__('Messages')}}
                    </span>
                    <span class="menu-notification-badge {{ NotificationsHelper::getUnreadMessages() > 0 ? '' : 'd-none' }}">{{ NotificationsHelper::getUnreadMessages() }}</span>
                </a>
            </li>
            <li class="{{Route::currentRouteName() == 'custom-requests.marketplace' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('custom-requests.marketplace')}}">
                    @include('elements.icon',['icon'=>'gift-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Custom requests')}}</a>
            </li>
            @if(!getSetting('site.hide_create_post_menu'))
                <li class="{{Route::currentRouteName() == 'posts.create' ? 'active' : ''}}">
                    <a class="scroll-link d-flex align-items-center" href="{{route('posts.create')}}">
                        @include('elements.icon',['icon'=>'add-circle-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                        {{__('Create post')}}</a>
                </li>
            @endif
            @if(getSetting('streams.allow_streams'))
                <li class="{{ in_array(Route::currentRouteName(), ['my.streams.get', 'public.stream.get', 'public.vod.get']) ? 'active' : ''}}">
                    <a class="scroll-link d-flex align-items-center" href="{{route('my.streams.get')}}">
                        @include('elements.icon',['icon'=>'play-circle-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                        {{__('Streams')}}</a>
                </li>
            @endif
            <li class="{{Route::currentRouteName() == 'my.bookmarks' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('my.bookmarks')}}">
                    @include('elements.icon',['icon'=>'bookmarks-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Bookmarks')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'my.lists.all' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('my.lists.all')}}">
                    @include('elements.icon',['icon'=>'list','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Lists')}}</a>
            </li>
            <li class="{{Route::currentRouteName() == 'my.settings' ? 'active' : ''}}">
                <a class="scroll-link d-flex align-items-center" href="{{route('my.settings')}}">
                    @include('elements.icon',['icon'=>'settings-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Settings')}}</a>
            </li>
            <div class="menu-divider"></div>
        @endif
        <li>
            <a class="scroll-link d-flex align-items-center" href="{{route('pages.get',['slug'=>'help'])}}">
                @include('elements.icon',['icon'=>'help-circle-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                {{__('Help and support')}}</a>
        </li>
        @if(getSetting('site.allow_theme_switch'))
            <li>
                <a class="scroll-link d-flex align-items-center dark-mode-switcher" href="#">
                    @if(Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark'))
                    @include('elements.icon',['icon'=>'contrast-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                        {{__('Light mode')}}
                    @else
                        @include('elements.icon',['icon'=>'contrast','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                        {{__('Dark mode')}}
                    @endif
                </a>
            </li>
        @endif
        @if(getSetting('site.allow_direction_switch'))
            <li>
                <a class="scroll-link d-flex align-items-center rtl-mode-switcher" href="#">
                    @include('elements.icon',['icon'=>'return-up-back','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__("RTL")}}</a>
            </li>
        @endif
        @if(getSetting('site.allow_language_switch'))
            <li>
                <a href="#otherSections" class="d-flex align-items-center" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle" role="button" aria-controls="otherSections">
                    @include('elements.icon',['icon'=>'language','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Language')}}
                </a>
                <ul class="collapse list-unstyled" id="otherSections">
                    @foreach(LocalesHelper::getAvailableLanguages() as $languageCode)
                        @if(LocalesHelper::getLanguageName($languageCode))
                            <li>
                                <a class="scroll-link d-flex align-items-center" href="{{route('language',['locale' => $languageCode])}}">{{ucfirst(__(LocalesHelper::getLanguageName($languageCode)))}}</a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </li>
        @endif
        <div class="menu-divider"></div>
        <li>
            @if(Auth::check())
                <a class="scroll-link d-flex align-items-center pointer-cursor" onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                    @include('elements.icon',['icon'=>'log-out-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    {{__('Log out')}}
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            @else
                <a class="scroll-link d-flex align-items-center" href="{{route('login')}}">
                    @include('elements.icon',['icon'=>'log-in-outline','variant'=>'medium','centered'=>false,'classes'=>'mr-2'])
                    </i> {{__('Login')}}</a>
            @endif
        </li>
    </ul>
</nav>

<!-- Dark overlay -->
<div class="overlay"></div>
