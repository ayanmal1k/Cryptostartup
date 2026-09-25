@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
    $logo = asset(
        $isDarkTheme ? getSetting('site.dark_logo') : getSetting('site.light_logo')
    );
    $route = Route::currentRouteName() ?? '';
    $unreadNotifications = Auth::check() ? NotificationsHelper::getUnreadNotifications()->total : 0;
    $unreadMessages = Auth::check() ? NotificationsHelper::getUnreadMessages() : 0;
@endphp

<div class="mobile-sidebar mobile-sidebar--{{ $isDarkTheme ? 'dark' : 'light' }}" id="mobile-sidebar" aria-hidden="true">
    <div class="mobile-sidebar__backdrop" data-mobile-sidebar-close></div>
    <aside class="mobile-sidebar__panel" role="dialog" aria-label="{{ __('Menu') }}">
        <div class="mobile-sidebar__header">
            <div class="mobile-sidebar__brand">
                <a href="{{ route('home') }}" class="mobile-sidebar__brand-link">
                    <span class="mobile-sidebar__logo-wrap">
                        <img src="{{ $logo }}" alt="{{ getSetting('site.name') }}" class="mobile-sidebar__logo">
                    </span>
                    @php
                        $siteName = getSetting('site.name') ?: 'Justfans';
                        $brandAccent = 'fans';
                        $brandBase = \Illuminate\Support\Str::endsWith(strtolower($siteName), $brandAccent)
                            ? substr($siteName, 0, -strlen($brandAccent))
                            : $siteName;
                        $brandSuffix = \Illuminate\Support\Str::endsWith(strtolower($siteName), $brandAccent)
                            ? substr($siteName, -strlen($brandAccent))
                            : '';
                    @endphp
                    <span class="mobile-sidebar__brand-text">
                        <span class="mobile-sidebar__brand-base">{{ $brandBase }}</span>@if($brandSuffix)<span class="mobile-sidebar__brand-accent">{{ $brandSuffix }}</span>@endif
                    </span>
                </a>
                <button type="button" class="mobile-sidebar__close" data-mobile-sidebar-close aria-label="{{ __('Close') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" aria-hidden="true">
                        <line x1="6" y1="6" x2="18" y2="18"/>
                        <line x1="18" y1="6" x2="6" y2="18"/>
                    </svg>
                </button>
            </div>

            <div class="mobile-sidebar__search-wrap">
                <button type="button" class="mobile-sidebar__search" id="mobile-sidebar-search-open" aria-label="{{ __('Search') }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <span>{{ __('Search') }}</span>
                </button>
            </div>
        </div>

        @include('template.partials.mobile-sidebar-nav', [
            'route' => $route,
            'unreadNotifications' => $unreadNotifications,
            'unreadMessages' => $unreadMessages,
        ])

    </aside>
</div>

<div class="mobile-search mobile-search--{{ $isDarkTheme ? 'dark' : 'light' }}" id="mobile-search" aria-hidden="true">
    <div class="mobile-search__backdrop" data-mobile-search-close></div>
    <aside class="mobile-search__panel" role="dialog" aria-label="{{ __('Search') }}">
        <div class="mobile-search__header">
            <div class="mobile-search__bar">
                <div class="mobile-search__input-wrap">
                    <span class="mobile-search__search-icon" aria-hidden="true">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </span>
                    <input type="search" class="mobile-search__input" id="mobile-search-input" placeholder="{{ __('Search creators...') }}" autocomplete="off" enterkeyhint="search">
                </div>
                <button type="button" class="mobile-search__close" id="mobile-search-close" aria-label="{{ __('Close') }}">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18M6 6l12 12"/></svg>
                </button>
            </div>
        </div>

        <div class="mobile-search__body" id="mobile-search-body">
            <div id="mobile-search-browse">

                <div class="mobile-search__promo">
                    <div class="mobile-search__promo-text">
                        <h4>{{ __('Start discovering the best of') }} {{ strtoupper(getSetting('site.name')) }}</h4>
                        <p>{{ __('Search for a creator or keyword') }}</p>
                    </div>
                    <div class="mobile-search__promo-icon" aria-hidden="true">
                        <svg width="26" height="26" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l2.09 6.26L20 9.27l-5 3.64L16.18 20 12 16.77 7.82 20 9 12.91l-5-3.64 5.91-.91L12 2z"/></svg>
                    </div>
                </div>

                <h3 class="mobile-search__section-title">{{ __('Creators you might like') }}</h3>
                <div class="mobile-search__creators-scroll" id="mobile-search-suggestions"></div>
            </div>

            <div class="mobile-search__results" id="mobile-search-results" hidden></div>
        </div>
    </aside>
</div>
