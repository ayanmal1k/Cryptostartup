{{-- Modern Premium Header Design --}}
<nav class="modern-header {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? 'dark-theme' : 'light-theme') : (Cookie::get('app_theme') == 'dark' ? 'dark-theme' : 'light-theme'))}}">
    <div class="container-fluid px-4">
        <div class="header-content">
            {{-- Logo Section --}}
            <div class="header-logo">
                <a href="{{ route('home') }}" class="logo-link">
                    <img src="{{asset( (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')) : (Cookie::get('app_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo'))) )}}" alt="{{__("Site logo")}}" class="site-logo">
                </a>
            </div>

            {{-- Search Bar (Center) --}}
            @if(Auth::check())
            <div class="header-search">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" 
                           class="search-input" 
                           placeholder="{{__('Search creators, posts, tokens...')}}"
                           id="global-search">
                </div>
            </div>
            @endif

            {{-- Right Section - Actions & User --}}
            <div class="header-actions">
                @guest
                    <a href="{{ route('login') }}" class="btn-modern btn-ghost">
                        {{__('Login')}}
                    </a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="btn-modern btn-primary">
                            {{__('Sign Up')}}
                        </a>
                    @endif
                @else
                    {{-- Quick Actions --}}
                    <div class="action-icons">
                        {{-- Wallet Balance --}}
                        <a href="{{ route('cryptocurrency.wallet') }}" class="action-item wallet-balance" title="{{__('My Wallet')}}">
                            <i class="fas fa-wallet"></i>
                            <span class="balance-text">${{ number_format(Auth::user()->wallet->total ?? Auth::user()->wallet->total_balance ?? 0, 2) }}</span>
                        </a>

                        {{-- Create Post --}}
                        @if(!getSetting('site.hide_create_post_menu'))
                            <a href="{{ route('posts.create') }}" class="action-item" title="{{__('Create Post')}}">
                                <i class="fas fa-plus-circle"></i>
                            </a>
                        @endif

                        {{-- Notifications --}}
                        <div class="action-item notification-bell" title="{{__('Notifications')}}">
                            <a href="{{route('my.notifications')}}">
                                <i class="fas fa-bell"></i>
                                @if(NotificationsHelper::getUnreadNotifications()->total > 0)
                                    <span class="notification-badge">{{ NotificationsHelper::getUnreadNotifications()->total }}</span>
                                @endif
                            </a>
                        </div>

                        {{-- Messages --}}
                        <div class="action-item message-icon" title="{{__('Messages')}}">
                            <a href="{{route('my.messenger.get')}}">
                                <i class="fas fa-comment-alt"></i>
                                @if(NotificationsHelper::getUnreadMessages() > 0)
                                    <span class="notification-badge">{{ NotificationsHelper::getUnreadMessages() }}</span>
                                @endif
                            </a>
                        </div>

                        {{-- Tokens Dropdown --}}
                        <div class="dropdown">
                            <button class="action-item dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{__('Crypto Tokens')}}">
                                <i class="fas fa-coins"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-modern dropdown-menu-right">
                                <div class="dropdown-header">
                                    <i class="fas fa-coins text-warning"></i> {{__('Cryptocurrency')}}
                                </div>
                                <a class="dropdown-item" href="{{ route('cryptocurrency.marketplace') }}">
                                    <i class="fas fa-coins"></i> {{__('Cryptocurrency')}}
                                </a>
                                <a class="dropdown-item" href="{{ route('custom-requests.marketplace') }}">
                                    <i class="fas fa-store"></i> {{__('Marketplace')}}
                                </a>
                                <a class="dropdown-item" href="{{ route('cryptocurrency.wallet') }}">
                                    <i class="fas fa-wallet"></i> {{__('My Wallet')}}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-primary" href="{{ route('cryptocurrency.create') }}">
                                    <i class="fas fa-plus-circle"></i> {{__('Create Token')}}
                                </a>
                            </div>
                        </div>

                        {{-- User Profile Dropdown --}}
                        <div class="dropdown user-dropdown">
                            <button class="user-avatar-btn" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <img src="{{Auth::user()->avatar}}" class="user-avatar" alt="{{Auth::user()->name}}">
                                <i class="fas fa-chevron-down avatar-arrow"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-modern dropdown-menu-right">
                                <div class="dropdown-header user-info">
                                    <img src="{{Auth::user()->avatar}}" class="dropdown-avatar" alt="{{Auth::user()->name}}">
                                    <div>
                                        <div class="user-name">{{ Auth::user()->name }}</div>
                                        <div class="user-username">@{{ Auth::user()->username }}</div>
                                    </div>
                                </div>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{route('profile',['username'=>Auth::user()->username])}}">
                                    <i class="fas fa-user"></i> {{__('My Profile')}}
                                </a>
                                <a class="dropdown-item" href="{{route('my.settings')}}">
                                    <i class="fas fa-cog"></i> {{__('Settings')}}
                                </a>
                                <a class="dropdown-item" href="{{route('my.settings',['type'=>'subscriptions'])}}">
                                    <i class="fas fa-users"></i> {{__('Subscriptions')}}
                                </a>
                                <a class="dropdown-item" href="{{route('my.settings',['type'=>'payments'])}}">
                                    <i class="fas fa-credit-card"></i> {{__('Payments')}}
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt"></i> {{ __('Logout') }}
                                </a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- Mobile Menu Toggle --}}
                    <button class="mobile-menu-toggle d-md-none" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                @endguest
            </div>
        </div>
    </div>
</nav>

<style>
/* Modern Header Styles */
.modern-header {
    position: sticky;
    top: 0;
    z-index: 1000;
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.modern-header.light-theme {
    background: rgba(255, 255, 255, 0.85);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.05);
}

.modern-header.dark-theme {
    background: rgba(26, 32, 44, 0.85);
    border-bottom-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 20px rgba(0, 0, 0, 0.3);
}

.header-content {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    gap: 2rem;
}

/* Logo */
.header-logo .site-logo {
    height: 40px;
    transition: transform 0.3s ease;
}

.header-logo .site-logo:hover {
    transform: scale(1.05);
}

/* Search Bar */
.header-search {
    flex: 1;
    max-width: 600px;
    display: none;
}

@media (min-width: 768px) {
    .header-search {
        display: block;
    }
}

.search-container {
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.75rem;
    border: none;
    border-radius: 50px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.light-theme .search-input {
    background: rgba(0, 0, 0, 0.05);
    color: #333;
}

.dark-theme .search-input {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
}

.search-input:focus {
    outline: none;
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    background: rgba(79, 70, 229, 0.1);
}

.search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #999;
    font-size: 0.95rem;
}

/* Action Icons */
.header-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.action-icons {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.action-item {
    position: relative;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-radius: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: none;
    background: transparent;
    color: inherit;
    text-decoration: none;
}

.action-item:hover {
    background: rgba(79, 70, 229, 0.1);
    transform: translateY(-2px);
}

.action-item i {
    font-size: 1.25rem;
}

/* Wallet Balance */
.wallet-balance {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-weight: 600;
}

.wallet-balance:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

.balance-text {
    font-size: 0.9rem;
    margin-left: 0.25rem;
}

/* Notification Badge */
.notification-badge {
    position: absolute;
    top: 4px;
    right: 4px;
    background: #ef4444;
    color: white;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 0.7rem;
    font-weight: 600;
    min-width: 18px;
    text-align: center;
}

/* User Avatar Button */
.user-avatar-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem;
    border-radius: 50px;
    border: 2px solid transparent;
    background: transparent;
    cursor: pointer;
    transition: all 0.3s ease;
    position: relative;
}

.user-avatar-btn:hover {
    border-color: rgba(79, 70, 229, 0.5);
    background: rgba(79, 70, 229, 0.05);
    transform: translateY(-1px);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(79, 70, 229, 0.3);
}

.avatar-arrow {
    font-size: 0.75rem;
    transition: transform 0.3s ease;
}

.dropdown.show .avatar-arrow {
    transform: rotate(180deg);
}

/* Modern Dropdown Menu */
.dropdown-menu-modern {
    border: none;
    border-radius: 16px;
    padding: 0.5rem;
    min-width: 280px;
    margin-top: 0.5rem;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
}

.light-theme .dropdown-menu-modern {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(20px);
}

.dark-theme .dropdown-menu-modern {
    background: rgba(26, 32, 44, 0.95);
    backdrop-filter: blur(20px);
}

.dropdown-header {
    padding: 0.75rem 1rem;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.light-theme .dropdown-header {
    color: #6b7280;
}

.dark-theme .dropdown-header {
    color: #9ca3af;
}

.dropdown-item {
    padding: 0.75rem 1rem;
    border-radius: 10px;
    margin: 0.125rem 0;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dropdown-item i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.dropdown-item:hover {
    background: rgba(79, 70, 229, 0.1);
    transform: translateX(4px);
}

.dropdown-divider {
    margin: 0.5rem 0;
}

/* User Info in Dropdown */
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background: linear-gradient(135deg, rgba(79, 70, 229, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
    border-radius: 12px;
    margin-bottom: 0.5rem;
}

.dropdown-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid rgba(79, 70, 229, 0.5);
}

.user-name {
    font-weight: 600;
    font-size: 0.95rem;
}

.user-username {
    font-size: 0.85rem;
    opacity: 0.7;
}

/* Buttons */
.btn-modern {
    padding: 0.5rem 1.25rem;
    border-radius: 50px;
    font-weight: 500;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    text-decoration: none;
    border: none;
    cursor: pointer;
}

.btn-ghost {
    background: transparent;
    color: inherit;
}

.btn-ghost:hover {
    background: rgba(79, 70, 229, 0.1);
    color: inherit;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white !important;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
}

/* Mobile Menu Toggle */
.mobile-menu-toggle {
    display: none;
    padding: 0.5rem;
    border: none;
    background: transparent;
    font-size: 1.5rem;
    cursor: pointer;
}

@media (max-width: 767px) {
    .mobile-menu-toggle {
        display: block;
    }
    
    .action-icons {
        display: none;
    }
    
    .wallet-balance .balance-text {
        display: none;
    }
}

/* Animations */
@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dropdown-menu-modern {
    animation: slideDown 0.2s ease;
}

/* Hover Effects */
.action-item a {
    color: inherit;
    text-decoration: none;
}

.light-theme .action-item a {
    color: #374151;
}

.dark-theme .action-item a {
    color: #e5e7eb;
}
</style>

<script>
// Add search functionality
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('global-search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                window.location.href = '{{ route("search.get") }}?q=' + encodeURIComponent(this.value);
            }
        });
    }
});
</script>

