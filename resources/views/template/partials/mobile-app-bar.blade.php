@php
    $appBarDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
    $appBarLogo = asset($appBarDark ? getSetting('site.dark_logo') : getSetting('site.light_logo'));
    $appBarUnread = Auth::check() ? NotificationsHelper::getUnreadNotifications()->total : 0;
    $appBarMessengerPage = Route::currentRouteName() === 'my.messenger.get';
    $appBarNotificationsPage = Route::currentRouteName() === 'my.notifications';
    $appBarBookmarksPage = Route::currentRouteName() === 'my.bookmarks';
    $appBarStreamsPage = Route::currentRouteName() === 'streams.index';
    $appBarMarketplacePage = Route::currentRouteName() === 'custom-requests.marketplace';
    $appBarNftMarketPage = Route::currentRouteName() === 'nft.marketplace';
    $appBarWalletHubPage = Route::currentRouteName() === 'wallet.hub';
    $appBarListsPage = Route::currentRouteName() === 'my.lists.all';
    $appBarListShowPage = Route::currentRouteName() === 'my.lists.show';
    $appBarCryptoPage = Route::currentRouteName() === 'cryptocurrency.index';
    $appBarCryptoWalletPage = Route::currentRouteName() === 'cryptocurrency.wallet';
    $appBarCryptoTxnPage = Route::currentRouteName() === 'cryptocurrency.transactions';
    $appBarCryptoMarketPage = Route::currentRouteName() === 'cryptocurrency.marketplace';
    $appBarCryptoShowPage = Route::currentRouteName() === 'cryptocurrency.show';
    $appBarCreatorPage = str_starts_with(Route::currentRouteName() ?? '', 'creator.');
    $appBarCreatorTitles = [
        'creator.dashboard' => __('Creator Dashboard'),
        'creator.videos' => __('My Videos'),
        'creator.streams' => __('Livestreams'),
        'creator.analytics' => __('Analytics'),
        'creator.settings' => __('Settings'),
    ];
    $appBarCreatorTitle = $appBarCreatorTitles[Route::currentRouteName() ?? ''] ?? __('Creator Dashboard');
    $appBarPublicPage = Route::currentRouteName() === 'pages.get';
    $appBarPublicPageTitle = $appBarPublicPage ? ($appBarPublicPageTitle ?? '') : '';
    $appBarSettingsPage = Route::currentRouteName() === 'my.settings';
    $appBarSettingsTab = $appBarSettingsPage ? (request()->route('type') ?? 'profile') : null;
    $appBarSettingsTitles = [
        'profile' => __('Profile'),
        'account' => __('Account'),
        'wallet' => __('Wallet'),
        'payments' => __('Payments'),
        'rates' => __('Rates'),
        'notifications' => __('Notifications'),
        'privacy' => __('Privacy'),
        'verify' => __('Verify'),
        'subscriptions' => __('Subscriptions'),
        'referrals' => __('Referrals'),
    ];
    $appBarSettingsTitle = $appBarSettingsTab ? ($appBarSettingsTitles[$appBarSettingsTab] ?? ucfirst(__($appBarSettingsTab))) : '';
    $appBarSubscriptionsPage = $appBarSettingsPage && $appBarSettingsTab === 'subscriptions';
    $appBarPageTitleMode = $appBarMessengerPage
        || $appBarNotificationsPage
        || $appBarBookmarksPage
        || $appBarStreamsPage
        || $appBarMarketplacePage
        || $appBarNftMarketPage
        || $appBarWalletHubPage
        || $appBarListsPage
        || $appBarListShowPage
        || $appBarCryptoPage
        || $appBarCryptoWalletPage
        || $appBarCryptoTxnPage
        || $appBarCryptoMarketPage
        || $appBarCryptoShowPage
        || $appBarPublicPage
        || $appBarCreatorPage
        || $appBarSettingsPage;

    if ($appBarMessengerPage) {
        $appBarPageTitle = __('Messages');
    } elseif ($appBarNotificationsPage) {
        $appBarPageTitle = __('Notifications');
    } elseif ($appBarBookmarksPage) {
        $appBarPageTitle = __('Bookmarks');
    } elseif ($appBarStreamsPage) {
        $appBarPageTitle = __('Live Streams');
    } elseif ($appBarMarketplacePage) {
        $appBarPageTitle = __('Challenges');
    } elseif ($appBarNftMarketPage) {
        $appBarPageTitle = __('NFT Market');
    } elseif ($appBarWalletHubPage) {
        $appBarPageTitle = __('Wallet');
    } elseif ($appBarListsPage) {
        $appBarPageTitle = __('Lists');
    } elseif ($appBarListShowPage) {
        $appBarPageTitle = $appBarListShowTitle ?? __('Lists');
    } elseif ($appBarCryptoShowPage) {
        $appBarPageTitle = __('Coin');
    } elseif ($appBarCryptoMarketPage) {
        $appBarPageTitle = __('Coins');
    } elseif ($appBarCryptoTxnPage) {
        $appBarPageTitle = __('Coin activity');
    } elseif ($appBarCryptoWalletPage) {
        $appBarPageTitle = __('My Wallet');
    } elseif ($appBarCryptoPage) {
        $appBarPageTitle = __('Coins');
    } elseif ($appBarPublicPage) {
        $appBarPageTitle = $appBarPublicPageTitle;
    } elseif ($appBarCreatorPage) {
        $appBarPageTitle = $appBarCreatorTitle;
    } elseif ($appBarSettingsPage) {
        $appBarPageTitle = $appBarSettingsTitle;
    } else {
        $appBarPageTitle = '';
    }

    $appBarSubPage = $appBarNotificationsPage
        || $appBarBookmarksPage
        || $appBarListShowPage
        || $appBarCryptoPage
        || $appBarCryptoWalletPage
        || $appBarCryptoTxnPage
        || $appBarCryptoMarketPage
        || $appBarCryptoShowPage;

    if ($appBarListShowPage) {
        $appBarBackUrl = route('my.lists.all');
    } elseif ($appBarCryptoTxnPage) {
        $appBarBackUrl = route('cryptocurrency.wallet');
    } elseif ($appBarCryptoShowPage) {
        $appBarBackUrl = route('cryptocurrency.marketplace');
    } elseif ($appBarCryptoWalletPage || $appBarCryptoMarketPage) {
        $appBarBackUrl = route('cryptocurrency.index');
    } else {
        $appBarBackUrl = route('home');
    }
@endphp
<header class="mobile-app-bar mobile-app-bar--{{ $appBarDark ? 'dark' : 'light' }}{{ $appBarPageTitleMode ? ' mobile-app-bar--page-title' : '' }}{{ $appBarSubPage ? ' mobile-app-bar--subpage' : '' }} d-md-none" role="banner">

    <div class="mobile-app-bar__side mobile-app-bar__side--left">
        @if($appBarSubPage)
            <a href="{{ $appBarBackUrl }}" class="mobile-app-bar__icon-btn" aria-label="{{ __('Back') }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 6-6 6 6 6"/>
                </svg>
            </a>
        @else
        <button type="button" class="mobile-app-bar__icon-btn" id="mobile-app-bar-search-open" data-mobile-search-open aria-label="{{ __('Search') }}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7.5"/>
                <path d="m20 20-4.2-4.2"/>
            </svg>
        </button>
        @endif
    </div>

    @if($appBarPageTitleMode)
        <h1 class="mobile-app-bar__page-title">{{ $appBarPageTitle }}</h1>
    @else
    <a href="{{ route('home') }}" class="mobile-app-bar__brand-center">
        <span class="mobile-app-bar__verified-badge" aria-hidden="true">
            <span class="mobile-app-bar__verified-ring">
                <img src="{{ $appBarLogo }}" alt="" class="mobile-app-bar__verified-logo">
            </span>
            <svg class="mobile-app-bar__verified-check" viewBox="0 0 12 12" width="10" height="10" aria-hidden="true">
                <path fill="currentColor" d="M4.8 8.1 2.6 5.9l-.9.9 3.1 3.1 6.4-6.4-.9-.9-5.5 5.5z"/>
            </svg>
        </span>
        <span class="mobile-app-bar__brand-text">{{ strtoupper(getSetting('site.name')) }}</span>
    </a>
    @endif

    <div class="mobile-app-bar__side mobile-app-bar__side--right">
        @auth
            @unless($appBarSubPage)
            @if((Auth::user()->streak_count ?? 0) > 0)
                <a href="{{ route('feed') }}" class="mobile-app-bar__streak" title="{{ Auth::user()->streak_count }} {{ __('day streak') }}">
                    <span aria-hidden="true">🔥</span>{{ Auth::user()->streak_count }}
                </a>
            @endif
            <a href="{{ route('my.notifications') }}" class="mobile-app-bar__icon-btn mobile-app-bar__icon-btn--notify" aria-label="{{ __('Notifications') }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                @if($appBarUnread > 0)
                    <span class="mobile-app-bar__badge">{{ $appBarUnread > 9 ? '9+' : $appBarUnread }}</span>
                @endif
            </a>
            @endunless
        @endauth
        <button type="button" class="mobile-app-bar__icon-btn mobile-app-bar__icon-btn--menu navbar-mobile-trigger" id="mobile-sidebar-open" aria-label="{{ __('Open menu') }}">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" aria-hidden="true">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>
    </div>
</header>
