{{-- Modern Professional Header --}}
<nav class="modern-navbar {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? 'dark-theme' : 'light-theme') : (Cookie::get('app_theme') == 'dark' ? 'dark-theme' : 'light-theme'))}}">
    <div class="container-fluid">
        <div class="navbar-content">
            {{-- Logo Section --}}
            <div class="navbar-brand-section">
                <a href="{{ route('home') }}" class="brand-link">
                    <img src="{{asset( (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')) : (Cookie::get('app_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo'))) )}}" class="brand-logo" alt="{{__("Site logo")}}">
                </a>
            </div>

            <div class="navbar-end navbar-desktop-only">
            {{-- Main Navigation --}}
            <div class="navbar-navigation">
                <div class="nav-links nav-links--text">
                    @guest
                        <a href="{{ route('feed') }}" class="nav-text-link {{ Route::currentRouteName() === 'feed' ? 'active' : '' }}">{{ __('Feed') }}</a>
                        <a href="{{ route('custom-requests.marketplace') }}" class="nav-text-link {{ Route::currentRouteName() == 'custom-requests.marketplace' ? 'active' : '' }}">{{ __('Marketplace') }}</a>
                        <a href="{{ route('contact') }}" class="nav-text-link {{ Route::currentRouteName() === 'contact' ? 'active' : '' }}">{{ __('Support') }}</a>
                    @else
                        @if(!getSetting('site.hide_create_post_menu'))
                            <a href="{{ route('posts.create') }}" class="nav-text-link {{ Route::currentRouteName() === 'posts.create' ? 'active' : '' }}">{{ __('Create') }}</a>
                        @endif
                        <a href="{{ route('feed') }}" class="nav-text-link {{ Route::currentRouteName() === 'feed' ? 'active' : '' }}">{{ __('Feed') }}</a>
                        <a href="{{ route('custom-requests.marketplace') }}" class="nav-text-link {{ Route::currentRouteName() == 'custom-requests.marketplace' ? 'active' : '' }}">{{ __('Marketplace') }}</a>
                        <a href="{{ route('creator.dashboard') }}" class="nav-text-link {{ str_starts_with(Route::currentRouteName() ?? '', 'creator.') ? 'active' : '' }}">{{ __('Creator') }}</a>
                    @endguest
                </div>
            </div>

            {{-- Search Bar --}}
            @if(Auth::check())
            <div class="search-section">
                <div class="search-container">
                    <div class="search-icon-wrapper">
                        <svg class="search-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 21L16.514 16.506L21 21ZM19 10.5C19 15.194 15.194 19 10.5 19C5.806 19 2 15.194 2 10.5C2 5.806 5.806 2 10.5 2C15.194 2 19 5.806 19 10.5Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <input type="text" 
                           class="search-input" 
                           placeholder="{{__('Search creators, posts, tokens...')}}"
                           id="global-search">
                </div>
            </div>
            @endif

            {{-- Right Actions --}}
            <div class="navbar-actions">
                @guest
                    <div class="auth-links">
                        @if (Route::has('register') && Route::currentRouteName() !== 'profile')
                            <a href="{{ route('register') }}" class="auth-link register-link">
                                {{ __('Sign Up') }}
                                <span class="auth-link__icon" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        @endif
                        @if(Route::currentRouteName() !== 'profile')
                            <a href="{{ route('login') }}" class="auth-link login-link">
                                {{ __('Login') }}
                                <span class="auth-link__icon auth-link__icon--outline" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            </a>
                        @endif
                    </div>
                @else
                    {{-- Quick Actions --}}
                    <div class="quick-actions">
                        {{-- Wallet Balance --}}
                        <a href="{{ route('cryptocurrency.wallet') }}" class="quick-action wallet-action" title="{{__('My Wallet')}}">
                            <div class="action-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 12V7H5a2 2 0 0 1-2-2 2 2 0 0 1 2-2h14v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M3 5v14a2 2 0 0 0 2 2h16v-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M18 12a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h2v-5h-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <span class="wallet-balance">${{ number_format(Auth::user()->wallet->total_balance ?? Auth::user()->wallet->balance ?? 0, 2) }}</span>
                        </a>

                        {{-- Notifications --}}
                        <a href="{{route('my.notifications')}}" class="quick-action notification-action" title="{{__('Notifications')}}">
                            <div class="action-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            @if(NotificationsHelper::getUnreadNotifications()->total > 0)
                                <span class="notification-badge">{{ NotificationsHelper::getUnreadNotifications()->total }}</span>
                            @endif
                        </a>

                        {{-- Messages/Chat --}}
                        <a href="{{route('my.messenger.get')}}" class="quick-action message-action" title="{{__('Messages')}}">
                            <div class="action-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            @if(NotificationsHelper::getUnreadMessages() > 0)
                                <span class="notification-badge message-badge">{{ NotificationsHelper::getUnreadMessages() }}</span>
                            @endif
                        </a>
                    </div>

                    {{-- Tokens Dropdown --}}
                    <div class="dropdown tokens-dropdown">
                        <button class="dropdown-toggle tokens-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <div class="tokens-icon">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                    <path d="M8 12h8M12 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <span class="tokens-label">{{ __('Tokens') }}</span>
                            <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu tokens-menu">
                            <div class="dropdown-header">
                                <div class="dropdown-title">
                                    <div class="title-icon">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                            <path d="M8 12h8M12 8v8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <div class="title-text">
                                        <div class="main-title">{{ __('Cryptocurrency') }}</div>
                                        <div class="sub-title">{{ __('Manage your digital assets') }}</div>
                                    </div>
                                </div>
                            </div>
                            
                            <a class="dropdown-item {{ in_array(Route::currentRouteName(), ['cryptocurrency.marketplace', 'cryptocurrency.buy', 'cryptocurrency.sell']) ? 'active' : '' }}" href="{{ route('cryptocurrency.marketplace') }}">
                                <div class="item-icon" style="color: #F7931A;">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 6v12M9 9l3-3 3 3M9 15l3 3 3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="8" r="1.5" fill="currentColor"/>
                                        <circle cx="12" cy="16" r="1.5" fill="currentColor"/>
                                    </svg>
                                </div>
                                <div class="item-content">
                                    <span class="item-title" style="color: #F7931A; font-weight: 600;">{{ __('Cryptocurrency') }}</span>
                                    <span class="item-description">{{ __('Explore digital assets') }}</span>
                                </div>
                                <div class="item-arrow">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </a>
                            
                            <a class="dropdown-item {{ Route::currentRouteName() == 'custom-requests.marketplace' ? 'active' : '' }}" href="{{ route('custom-requests.marketplace') }}">
                                <div class="item-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <polyline points="9 22 9 12 15 12 15 22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">{{ __('Marketplace') }}</span>
                                    <span class="item-description">{{ __('Buy & sell tokens') }}</span>
                                </div>
                                <div class="item-arrow">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </a>
                            
                            <a class="dropdown-item {{ in_array(Route::currentRouteName(), ['cryptocurrency.wallet', 'cryptocurrency.transactions', 'cryptocurrency.deposit', 'cryptocurrency.withdraw']) ? 'active' : '' }}" href="{{ route('cryptocurrency.wallet') }}">
                                <div class="item-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M21 12V7H5a2 2 0 0 1-2-2 2 2 0 0 1 2-2h14v4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M3 5v14a2 2 0 0 0 2 2h16v-9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18 12a2 2 0 0 0-2 2v1a2 2 0 0 0 2 2h2v-5h-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">{{ __('My Wallet') }}</span>
                                    <span class="item-description">{{ __('View balance & history') }}</span>
                                </div>
                                <div class="item-arrow">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </a>
                            
                            <div class="dropdown-divider"></div>
                            
                            <a class="dropdown-item {{ Route::currentRouteName() == 'cryptocurrency.create' ? 'active' : '' }}" href="{{ route('cryptocurrency.create') }}">
                                <div class="item-icon">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 8v8M8 12h8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <div class="item-content">
                                    <span class="item-title">{{ __('Create Token') }}</span>
                                    <span class="item-description">{{ __('Launch your own token') }}</span>
                                </div>
                                <div class="item-arrow">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M5 12h14M12 5l7 7-7 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                            </a>
                        </div>
                    </div>

                    {{-- User Profile Dropdown --}}
                    <div class="dropdown user-dropdown">
                        <button class="user-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <img src="{{Auth::user()->avatar}}" class="user-avatar" alt="{{Auth::user()->name}}">
                            <div class="user-info">
                                <span class="user-name">{{ Auth::user()->name }}</span>
                            </div>
                            <svg class="dropdown-arrow" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9"></polyline>
                            </svg>
                        </button>
                        <div class="dropdown-menu user-menu">
                            <div class="user-profile-header">
                                <img src="{{Auth::user()->avatar}}" class="dropdown-avatar" alt="{{Auth::user()->name}}">
                                <div class="user-profile-info">
                                    <div class="user-profile-name">{{ Auth::user()->name }}</div>
                                    <div class="user-profile-handle">{{ Auth::user()->username }}</div>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{route('profile',['username'=>Auth::user()->username])}}">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="12" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{__('My Profile')}}</span>
                            </a>
                            <a class="dropdown-item dropdown-item--creator" href="{{route('creator.dashboard')}}">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M3 3v18h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M18 9l-5 5-4-4-3 3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{__('Creator Dashboard')}}</span>
                            </a>
                            <a class="dropdown-item" href="{{route('my.settings')}}">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{__('Settings')}}</span>
                            </a>
                            <a class="dropdown-item" href="{{route('my.settings',['type'=>'subscriptions'])}}">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <circle cx="9" cy="7" r="4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{__('Subscriptions')}}</span>
                            </a>
                            <a class="dropdown-item" href="{{route('my.settings',['type'=>'payments'])}}">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <rect x="1" y="4" width="22" height="16" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="1" y1="10" x2="23" y2="10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{__('Payments')}}</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="{{ route('dmca.index') }}">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{__('DMCA & Copyright')}}</span>
                            </a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item logout-item" href="{{ route('logout') }}"
                               onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <div class="item-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <polyline points="16 17 21 12 16 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <line x1="21" y1="12" x2="9" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </div>
                                <span>{{ __('Logout') }}</span>
                            </a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form>
                        </div>
                    </div>
                @endguest
            </div>
            </div>

            <button type="button" class="navbar-mobile-trigger" aria-label="{{ __('Open menu') }}">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
            </button>
        </div>
    </div>
</nav>
<script>
// Enhanced dropdown functionality
document.addEventListener('DOMContentLoaded', function() {
    const dropdowns = document.querySelectorAll('.dropdown');
    const viewportPad = 12;

    function resetDropdownPosition(menu) {
        if (!menu) return;
        menu.classList.remove('is-fixed');
        menu.style.removeProperty('top');
        menu.style.removeProperty('left');
        menu.style.removeProperty('right');
        menu.style.removeProperty('--dropdown-shift-x');
    }

    function fitDropdownToViewport(dropdown) {
        const menu = dropdown.querySelector('.dropdown-menu');
        const toggle = dropdown.querySelector('.dropdown-toggle, .user-toggle');
        if (!menu || !toggle) return;

        resetDropdownPosition(menu);

        requestAnimationFrame(function() {
            const toggleRect = toggle.getBoundingClientRect();
            const menuWidth = menu.offsetWidth;
            const menuHeight = menu.offsetHeight;
            let left = toggleRect.right - menuWidth;
            let top = toggleRect.bottom + 8;

            if (left < viewportPad) {
                left = viewportPad;
            }
            if (left + menuWidth > window.innerWidth - viewportPad) {
                left = window.innerWidth - viewportPad - menuWidth;
            }
            if (top + menuHeight > window.innerHeight - viewportPad) {
                top = Math.max(viewportPad, toggleRect.top - menuHeight - 8);
            }

            menu.classList.add('is-fixed');
            menu.style.top = top + 'px';
            menu.style.left = left + 'px';
            menu.style.right = 'auto';
        });
    }

    function closeAllDropdowns() {
        dropdowns.forEach(function(dropdown) {
            dropdown.classList.remove('show');
            resetDropdownPosition(dropdown.querySelector('.dropdown-menu'));
        });
    }

    document.addEventListener('click', function(event) {
        dropdowns.forEach(function(dropdown) {
            const isClickInside = dropdown.contains(event.target);
            const isDropdownToggle = event.target.closest('.dropdown-toggle, .user-toggle');

            if (!isClickInside && !isDropdownToggle) {
                dropdown.classList.remove('show');
                resetDropdownPosition(dropdown.querySelector('.dropdown-menu'));
            }
        });
    });

    const dropdownToggles = document.querySelectorAll('.dropdown-toggle, .user-toggle');
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const dropdown = this.closest('.dropdown');
            const isCurrentlyOpen = dropdown.classList.contains('show');

            dropdowns.forEach(function(otherDropdown) {
                if (otherDropdown !== dropdown) {
                    otherDropdown.classList.remove('show');
                    resetDropdownPosition(otherDropdown.querySelector('.dropdown-menu'));
                }
            });

            if (isCurrentlyOpen) {
                dropdown.classList.remove('show');
                resetDropdownPosition(dropdown.querySelector('.dropdown-menu'));
            } else {
                dropdown.classList.add('show');
                fitDropdownToViewport(dropdown);
            }
        });
    });

    window.addEventListener('resize', closeAllDropdowns);
    window.addEventListener('scroll', closeAllDropdowns, true);

    document.addEventListener('click', function(event) {
        const dropdownItem = event.target.closest('.dropdown-item');
        if (dropdownItem && !dropdownItem.classList.contains('logout-item')) {
            const dropdown = dropdownItem.closest('.dropdown');
            if (dropdown) {
                dropdown.classList.remove('show');
                resetDropdownPosition(dropdown.querySelector('.dropdown-menu'));
            }
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeAllDropdowns();
        }
    });

    // Search functionality
    const searchInput = document.getElementById('global-search');
    
    if (searchInput) {
        // Enter key search
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && this.value.trim()) {
                window.location.href = '/' + encodeURIComponent(this.value.trim().replace(/^@/, ''));
            }
        });
    }

    // Handle header scroll effect
    let lastScroll = 0;
    const navbar = document.querySelector('.modern-navbar');
    
    window.addEventListener('scroll', function() {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > lastScroll && currentScroll > 72) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }
        
        lastScroll = currentScroll;
    });
});
</script>
