<div class="mobile-top-nav border-bottom z-index-3 neutral-bg">
    <div class="d-flex justify-content-between align-items-center w-100 py-2 px-3">
        {{-- Logo --}}
        <a href="{{Auth::check() ? route('feed') : route('home')}}" class="d-flex align-items-center">
            <img src="{{asset( (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')) : (Cookie::get('app_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo'))) )}}" alt="{{__('Site logo')}}" style="height:30px;width:auto;">
        </a>

        {{-- Streak flame (gamification) --}}
        @auth
            @if((Auth::user()->streak_count ?? 0) > 0)
                <a href="{{ Auth::check() ? route('feed') : route('home') }}" class="d-flex align-items-center" style="text-decoration:none;font-weight:700;color:#830866;font-size:1rem;" title="{{ Auth::user()->streak_count }} day streak">
                    <span style="margin-right:3px;">🔥</span>{{ Auth::user()->streak_count }}
                </a>
            @endif
        @endauth

        {{-- Hamburger menu (opens the slide-out side menu) --}}
        <a href="javascript:void(0)" class="open-menu d-flex align-items-center justify-content-center position-relative" aria-label="{{__('Menu')}}" style="width:42px;height:42px;">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M3 6h18M3 12h18M3 18h18" stroke="#830866" stroke-width="2.2" stroke-linecap="round"/>
            </svg>
            @auth
                @php($menuUnread = NotificationsHelper::getUnreadNotifications()->total + NotificationsHelper::getUnreadMessages())
                <span class="menu-notification-badge {{ $menuUnread > 0 ? '' : 'd-none' }}" style="position:absolute;top:2px;right:2px;">{{ $menuUnread }}</span>
            @endauth
        </a>
    </div>
</div>
