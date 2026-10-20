@php
    $brandTitle = Voyager::setting('admin.title', 'JustFans Admin');
    $adminLogo = Voyager::setting('admin.icon_image', '');

    $fallbackRoutes = [
        'Dashboard' => 'voyager.dashboard',
        'Tax Revenue' => 'voyager.revenue.index',
        'Token Earnings' => 'voyager.tokens.index',
        'Wallet Activity' => 'voyager.wallets.index',
        'Creator Tokens' => 'voyager.tokens.index',
        'Requests & Approvals' => 'voyager.user-verifies.index',
    ];

    $primaryNav = [
        ['title' => 'Dashboard', 'icon' => 'voyager-dashboard', 'route' => 'voyager.dashboard', 'active' => ['voyager.dashboard']],
        ['title' => 'Tokens', 'icon' => 'voyager-trophy', 'route' => 'voyager.tokens.index', 'active' => ['voyager.tokens.*']],
        ['title' => 'Wallets', 'icon' => 'voyager-wallet', 'route' => 'voyager.wallets.index', 'active' => ['voyager.wallets.*']],
        ['title' => 'Revenue', 'icon' => 'voyager-dollar', 'route' => 'voyager.revenue.index', 'active' => ['voyager.revenue.*']],
        ['title' => 'Users', 'icon' => 'voyager-people', 'route' => 'voyager.users.index', 'active' => ['voyager.users.*']],
        ['title' => 'Media', 'icon' => 'voyager-images', 'route' => 'voyager.media.index', 'active' => ['voyager.media.*']],
    ];

    $settingsRoute = Route::has('voyager.settings.index') ? 'voyager.settings.index' : null;
    $profileRoute = Route::has('voyager.profile') ? 'voyager.profile' : null;

    $renderedHrefs = [];
    $secondaryNav = [];
    $currentUrl = url()->current();
    $normalizeUrl = function ($url) {
        $path = parse_url($url, PHP_URL_PATH) ?: '/';
        $path = Str::start($path, '/');
        return rtrim($path, '/') ?: '/';
    };

    foreach ($primaryNav as $item) {
        if (!Route::has($item['route'])) {
            continue;
        }
        $renderedHrefs[] = $normalizeUrl(route($item['route']));
    }

    if ($settingsRoute) {
        $renderedHrefs[] = $normalizeUrl(route($settingsRoute));
    }

    foreach (menu('admin', '_json') as $menuItem) {
        $routeName = $fallbackRoutes[$menuItem->title] ?? $menuItem->route;
        $href = ($routeName && Route::has($routeName)) ? route($routeName) : ($menuItem->href ?? '#');
        if (!$href || $href === '#') {
            continue;
        }
        $path = $normalizeUrl($href);
        if (in_array($path, $renderedHrefs, true)) {
            continue;
        }
        $renderedHrefs[] = $path;
        $secondaryNav[] = [
            'title' => $menuItem->title,
            'icon' => $menuItem->icon_class ?: 'voyager-dot',
            'href' => $href,
            'active' => $normalizeUrl($currentUrl) === $path,
        ];
    }
@endphp

<aside class="side-menu jf-sidebar" id="jfSidebar" aria-label="Admin navigation">
    <div class="jf-sidebar__inner">
        <div class="jf-sidebar__head">
            <a href="{{ route('voyager.dashboard') }}" class="jf-sidebar__brand" title="{{ $brandTitle }}">
                <span class="jf-sidebar__brand-icon">
                    @if ($adminLogo == '')
                        <img src="{{ asset('/img/rounded-logo-white.svg') }}" alt="">
                    @else
                        <img src="{{ Voyager::image($adminLogo) }}" alt="">
                    @endif
                </span>
                <span class="jf-sidebar__brand-text">{{ $brandTitle }}</span>
            </a>
        </div>

        <nav class="jf-sidebar__nav" id="adminmenu">
            <ul class="jf-sidebar__list">
                @foreach ($primaryNav as $item)
                    @continue(!Route::has($item['route']))
                    @php
                        $href = route($item['route']);
                        $active = request()->routeIs(...$item['active']) || $normalizeUrl($currentUrl) === $normalizeUrl($href);
                    @endphp
                    <li>
                        <a href="{{ $href }}" class="jf-sidebar__link {{ $active ? 'is-active' : '' }}" title="{{ $item['title'] }}">
                            <span class="jf-sidebar__link-icon"><i class="{{ $item['icon'] }}"></i></span>
                            <span class="jf-sidebar__link-label">{{ $item['title'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>

            @if(!empty($secondaryNav))
                <div class="jf-sidebar__divider" role="separator"></div>
                <ul class="jf-sidebar__list">
                    @foreach ($secondaryNav as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="jf-sidebar__link {{ $item['active'] ? 'is-active' : '' }}" title="{{ $item['title'] }}">
                                <span class="jf-sidebar__link-icon"><i class="{{ $item['icon'] }}"></i></span>
                                <span class="jf-sidebar__link-label">{{ $item['title'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </nav>

        <div class="jf-sidebar__foot">
            <div class="jf-sidebar__user-card">
                @if ($profileRoute)
                    <a href="{{ route($profileRoute) }}"
                       class="jf-sidebar__user-main {{ request()->routeIs('voyager.profile', 'voyager.users.edit') ? 'is-active' : '' }}"
                       title="{{ __('voyager::generic.profile') }}">
                        <img src="{{ $user_avatar }}" class="jf-sidebar__avatar" alt="{{ Auth::user()->name }}">
                        <div class="jf-sidebar__user-meta">
                            <strong>{{ ucwords(Auth::user()->name) }}</strong>
                            <span>{{ __('Administrator') }}</span>
                        </div>
                    </a>
                @else
                    <div class="jf-sidebar__user-main">
                        <img src="{{ $user_avatar }}" class="jf-sidebar__avatar" alt="{{ Auth::user()->name }}">
                        <div class="jf-sidebar__user-meta">
                            <strong>{{ ucwords(Auth::user()->name) }}</strong>
                            <span>{{ __('Administrator') }}</span>
                        </div>
                    </div>
                @endif

                @if ($profileRoute || $settingsRoute || Route::has('voyager.logout'))
                    <div class="jf-sidebar__user-menu">
                        <button type="button"
                                class="jf-sidebar__settings-btn {{ request()->routeIs('voyager.settings.*') ? 'is-active' : '' }}"
                                title="{{ __('voyager::generic.settings') }}"
                                aria-haspopup="true"
                                aria-expanded="false">
                            <svg class="jf-sidebar__settings-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Z"/>
                                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33h.09a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82v.09a1.65 1.65 0 0 0 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z"/>
                            </svg>
                        </button>
                        <ul class="jf-sidebar__user-dropdown" role="menu">
                            @if ($profileRoute)
                                <li role="none">
                                    <a href="{{ route($profileRoute) }}"
                                       class="jf-sidebar__user-dropdown-link {{ request()->routeIs('voyager.profile', 'voyager.users.edit') ? 'is-active' : '' }}"
                                       role="menuitem">
                                        <i class="voyager-person"></i>
                                        <span>{{ __('voyager::generic.profile') }}</span>
                                    </a>
                                </li>
                            @endif
                            <li role="none">
                                <a href="{{ url('/') }}"
                                   class="jf-sidebar__user-dropdown-link"
                                   role="menuitem"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <i class="voyager-home"></i>
                                    <span>{{ __('voyager::generic.home') }}</span>
                                </a>
                            </li>
                            @if ($settingsRoute)
                                <li role="none">
                                    <a href="{{ route($settingsRoute) }}"
                                       class="jf-sidebar__user-dropdown-link {{ request()->routeIs('voyager.settings.*') ? 'is-active' : '' }}"
                                       role="menuitem">
                                        <i class="voyager-settings"></i>
                                        <span>{{ __('View all settings') }}</span>
                                    </a>
                                </li>
                            @endif
                            @if (Route::has('voyager.logout'))
                                <li class="jf-sidebar__user-dropdown-sep" role="separator"></li>
                                <li class="jf-sidebar__user-dropdown-logout" role="none">
                                    <form action="{{ route('voyager.logout') }}" method="POST" class="jf-sidebar__user-dropdown-logout-form">
                                        @csrf
                                        <button type="submit" class="jf-sidebar__user-dropdown-logout-btn" role="menuitem">
                                            <i class="voyager-power"></i>
                                            <span>{{ __('voyager::generic.logout') }}</span>
                                        </button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</aside>
