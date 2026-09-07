@php
    $route = $route ?? (Route::currentRouteName() ?? '');
    $isValidated = Auth::check() && GenericHelper::isEmailEnforcedAndValidated();
    $liveStreamsCount = $isValidated && getSetting('streams.allow_streams') ? StreamsHelper::getPublicLiveStreamsCount() : 0;
    $inProgressStream = $isValidated ? StreamsHelper::getUserInProgressStream() : null;
@endphp

<nav class="mobile-sidebar__nav" aria-label="{{ __('Main navigation') }}">
    {{-- Primary --}}
    <div class="mobile-sidebar__group">
        <p class="mobile-sidebar__group-label">{{ __('Menu') }}</p>

        <a href="{{ Auth::check() ? route('feed') : route('home') }}" class="mobile-sidebar__link {{ in_array($route, ['home', 'feed']) ? 'is-active' : '' }}">
            <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'home'])</span>
            <span>{{ __('Home') }}</span>
        </a>

        @auth
            @if($isValidated)
                <a href="{{ route('my.messenger.get') }}" class="mobile-sidebar__link {{ str_starts_with($route, 'my.messenger') ? 'is-active' : '' }}">
                    <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'chat'])</span>
                    <span>{{ __('Messages') }}</span>
                    @if($unreadMessages > 0)
                        <span class="mobile-sidebar__badge">{{ $unreadMessages }}</span>
                    @endif
                </a>

                <a href="{{ route('my.notifications') }}" class="mobile-sidebar__link {{ $route === 'my.notifications' ? 'is-active' : '' }}">
                    <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'notifications'])</span>
                    <span>{{ __('Notifications') }}</span>
                    @if($unreadNotifications > 0)
                        <span class="mobile-sidebar__badge">{{ $unreadNotifications }}</span>
                    @endif
                </a>

                <a href="{{ route('profile', ['username' => Auth::user()->username]) }}" class="mobile-sidebar__link {{ $route === 'profile' && request()->route('username') === Auth::user()->username ? 'is-active' : '' }}">
                    <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'person'])</span>
                    <span>{{ __('My profile') }}</span>
                </a>
            @endif
        @else
            <a href="{{ route('feed') }}" class="mobile-sidebar__link {{ $route === 'feed' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'film'])</span>
                <span>{{ __('Feed') }}</span>
            </a>
            <a href="{{ route('custom-requests.marketplace') }}" class="mobile-sidebar__link {{ in_array($route, ['custom-requests.marketplace', 'custom-requests.show', 'custom-requests.my-requests']) ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'gift'])</span>
                <span>{{ __('Marketplace') }}</span>
            </a>
        @endauth
    </div>

    {{-- Discover --}}
    <div class="mobile-sidebar__group">
        <p class="mobile-sidebar__group-label">{{ __('Discover') }}</p>

        <a href="{{ route('streams.index') }}" class="mobile-sidebar__link {{ $route === 'streams.index' ? 'is-active' : '' }}">
            <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'videocam'])</span>
            <span>{{ __('Livestreams') }}</span>
        </a>

        <a href="{{ route('custom-requests.marketplace') }}" class="mobile-sidebar__link {{ in_array($route, ['custom-requests.marketplace', 'custom-requests.show', 'custom-requests.my-requests']) ? 'is-active' : '' }}">
            <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'gift'])</span>
            <span>{{ __('Custom Requests') }}</span>
        </a>

        @if($isValidated && getSetting('streams.allow_streams'))
            <a href="{{ route('search.get') }}?filter=live" class="mobile-sidebar__link {{ $route === 'search.get' && request()->get('filter') === 'live' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'play'])</span>
                <span>{{ __('Streams') }}</span>
                @if($liveStreamsCount > 0)
                    <span class="mobile-sidebar__badge">{{ $liveStreamsCount }}</span>
                @endif
            </a>
        @endif
    </div>

    @if($isValidated)
        {{-- Library & account --}}
        <div class="mobile-sidebar__group">
            <p class="mobile-sidebar__group-label">{{ __('Library') }}</p>

            <a href="{{ route('my.bookmarks') }}" class="mobile-sidebar__link {{ $route === 'my.bookmarks' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'bookmark'])</span>
                <span>{{ __('Bookmarks') }}</span>
            </a>

            <a href="{{ route('my.lists.all') }}" class="mobile-sidebar__link {{ in_array($route, ['my.lists.all', 'my.lists.show']) ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'list'])</span>
                <span>{{ __('Lists') }}</span>
            </a>

            <a href="{{ route('my.settings', ['type' => 'subscriptions']) }}" class="mobile-sidebar__link {{ $route === 'my.settings' && is_int(strpos(Request::path(), 'subscriptions')) ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'people'])</span>
                <span>{{ __('Subscriptions') }}</span>
            </a>

            <a href="{{ route('creator.dashboard') }}" class="mobile-sidebar__link {{ str_starts_with($route, 'creator.') ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                </span>
                <span>{{ __('Creator Dashboard') }}</span>
            </a>

            <a href="{{ route('videos.create') }}" class="mobile-sidebar__link {{ $route === 'videos.create' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'videocam'])</span>
                <span>{{ __('Create Video') }}</span>
            </a>

            <a href="{{ route('cryptocurrency.index') }}" class="mobile-sidebar__link {{ in_array($route, ['cryptocurrency.index', 'cryptocurrency.wallet.index']) ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'tokens'])</span>
                <span>{{ __('Tokens') }}</span>
            </a>

            <a href="{{ route('cryptocurrency.wallet') }}" class="mobile-sidebar__link {{ $route === 'cryptocurrency.wallet' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><path d="M17 12h.01"/></svg>
                </span>
                <span>{{ __('Wallet') }}</span>
            </a>

            <a href="{{ route('cryptocurrency.marketplace') }}" class="mobile-sidebar__link {{ $route === 'cryptocurrency.marketplace' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9 4.5 4.5h15L21 9"/><path d="M3 9h18v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/><path d="M9 20v-6h6v6"/></svg>
                </span>
                <span>{{ __('Token Marketplace') }}</span>
            </a>

            <a href="{{ route('nft.marketplace') }}" class="mobile-sidebar__link {{ str_starts_with($route, 'nft.') ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="m3 7 9 5 9-5"/><path d="M12 12v10"/></svg>
                </span>
                <span>{{ __('NFT Marketplace') }}</span>
            </a>
        </div>

        {{-- Rewards: XP / streak / achievements (gamification) --}}
        <div class="mobile-sidebar__group mobile-sidebar__group--rewards">
            <p class="mobile-sidebar__group-label">{{ __('Rewards') }}</p>

            @php
                $gamiXp = (int) (Auth::user()->xp ?? 0);
                $gamiXpProgress = $gamiXp % 100;
            @endphp
            <div class="mobile-sidebar__xp-card" style="padding:14px 16px;margin:0 0 10px;border-radius:12px;background:linear-gradient(135deg,#830866,#a10a7f);color:#fff;">
                <div style="display:flex;align-items:center;justify-content:space-between;font-weight:700;">
                    <span>{{ __('Level') }} {{ Auth::user()->level ?? 1 }}</span>
                    <span>🔥 {{ Auth::user()->streak_count ?? 0 }} {{ __('day streak') }}</span>
                </div>
                <div style="height:7px;background:rgba(255,255,255,.3);border-radius:6px;overflow:hidden;margin-top:8px;">
                    <div style="height:100%;width:{{ $gamiXpProgress }}%;background:#fff;border-radius:6px;"></div>
                </div>
                <div style="font-size:.72rem;opacity:.9;margin-top:4px;">{{ $gamiXpProgress }}/100 {{ __('XP to next level') }}</div>
            </div>

            <a href="{{ route('engagement.hub') }}" class="mobile-sidebar__link {{ $route === 'engagement.hub' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5C11 3 12 8 12 8S13 3 16.5 3a2.5 2.5 0 0 1 0 5"/></svg>
                </span>
                <span>{{ __('Rewards') }}</span>
                @if(($gmRewardsBadge ?? 0) > 0)
                    <span class="badge badge-pill badge-danger ml-auto">{{ $gmRewardsBadge }}</span>
                @endif
            </a>

            <a href="{{ route('gamification.achievements') }}" class="mobile-sidebar__link {{ $route === 'gamification.achievements' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M8 21h8"/><path d="M12 17v4"/><path d="M7 4h10v5a5 5 0 0 1-10 0z"/><path d="M17 5h3v2a3 3 0 0 1-3 3"/><path d="M7 5H4v2a3 3 0 0 0 3 3"/></svg>
                </span>
                <span>{{ __('Achievements') }}</span>
            </a>

            <a href="{{ route('gamification.leaderboard') }}" class="mobile-sidebar__link {{ $route === 'gamification.leaderboard' ? 'is-active' : '' }}">
                <span class="mobile-sidebar__icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V10"/><path d="M12 20V4"/><path d="M20 20v-7"/></svg>
                </span>
                <span>{{ __('Leaderboard') }}</span>
            </a>
        </div>

        {{-- More (expandable) --}}
        <div class="mobile-sidebar__group mobile-sidebar__group--more">
            <button type="button" class="mobile-sidebar__link mobile-sidebar__more-toggle" id="mobile-sidebar-more-toggle" aria-expanded="false" aria-controls="mobile-sidebar-more-panel">
                <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'settings'])</span>
                <span>{{ __('More') }}</span>
                <span class="mobile-sidebar__more-chevron">@include('template.partials.mobile-nav-icon', ['icon' => 'chevron'])</span>
            </button>

            <div class="mobile-sidebar__more-panel" id="mobile-sidebar-more-panel" hidden>
                <a href="{{ route('my.settings') }}" class="mobile-sidebar__link mobile-sidebar__link--sub {{ $route === 'my.settings' && !is_int(strpos(Request::path(), 'subscriptions')) ? 'is-active' : '' }}">
                    <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'settings'])</span>
                    <span>{{ __('Settings') }}</span>
                </a>

                <a href="{{ route('pages.get', ['slug' => 'help']) }}" class="mobile-sidebar__link mobile-sidebar__link--sub {{ $route === 'pages.get' && request()->route('slug') === 'help' ? 'is-active' : '' }}">
                    <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'help'])</span>
                    <span>{{ __('Help and support') }}</span>
                </a>

                @include('template.partials.mobile-sidebar-theme-toggle', ['themeToggleSub' => true])

                @if(getSetting('site.allow_language_switch'))
                    <div class="mobile-sidebar__lang-wrap">
                        <button type="button" class="mobile-sidebar__link mobile-sidebar__link--sub mobile-sidebar__lang-toggle" id="mobile-sidebar-lang-toggle" aria-expanded="false">
                            <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'language'])</span>
                            <span>{{ __('Language') }}</span>
                            <span class="mobile-sidebar__more-chevron">@include('template.partials.mobile-nav-icon', ['icon' => 'chevron'])</span>
                        </button>
                        <div class="mobile-sidebar__lang-list" id="mobile-sidebar-lang-list" hidden>
                            @foreach(LocalesHelper::getAvailableLanguages() as $languageCode)
                                @if(LocalesHelper::getLanguageName($languageCode))
                                    <a href="{{ route('language', ['locale' => $languageCode]) }}" class="mobile-sidebar__lang-item">{{ ucfirst(__(LocalesHelper::getLanguageName($languageCode))) }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <a href="{{ route('logout') }}" class="mobile-sidebar__link mobile-sidebar__link--sub"
                   onclick="event.preventDefault(); document.getElementById('mobile-sidebar-logout-form').submit();">
                    <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'logout'])</span>
                    <span>{{ __('Log out') }}</span>
                </a>
                <form id="mobile-sidebar-logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
            </div>
        </div>
    @endif
</nav>

@if($isValidated)
    <div class="mobile-sidebar__actions">
        @if(getSetting('streams.allow_streams') && !getSetting('site.hide_stream_create_menu'))
            <a href="{{ route('my.streams.get') }}{{ $inProgressStream ? '' : (!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? '' : '?action=create') }}"
               class="mobile-sidebar__action-btn mobile-sidebar__action-btn--live">
                @if($inProgressStream)
                    <span class="mobile-sidebar__live-dot" aria-hidden="true"></span>
                    {{ __('On air') }}
                @else
                    {{ __('Go live') }}
                @endif
            </a>
        @endif

        @if(!getSetting('site.hide_create_post_menu'))
            <a href="{{ route('posts.create') }}" class="mobile-sidebar__action-btn mobile-sidebar__action-btn--primary">
                {{ __('New post') }}
            </a>
        @endif

        <button type="button" class="mobile-sidebar__action-btn mobile-sidebar__action-btn--outline"
                onclick="event.preventDefault(); if (typeof CustomRequest !== 'undefined') CustomRequest.showCreateModal();">
            {{ __('Create Request') }}
        </button>
    </div>
@endif

<div class="mobile-sidebar__footer">
    @if(!Auth::check() || !GenericHelper::isEmailEnforcedAndValidated())
        @include('template.partials.mobile-sidebar-theme-toggle')
    @endif
    @guest
        <a href="{{ route('login') }}" class="mobile-sidebar__link">
            <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'login'])</span>
            <span>{{ __('Login') }}</span>
        </a>
        <button type="button" class="mobile-sidebar__link mobile-sidebar__hide-btn" data-mobile-sidebar-close>
            <span class="mobile-sidebar__icon">@include('template.partials.mobile-nav-icon', ['icon' => 'panel'])</span>
            <span>{{ __('Hide menu') }}</span>
        </button>
    @else
        <a href="{{ route('profile', ['username' => Auth::user()->username]) }}" class="mobile-sidebar__user-card mobile-sidebar__user-card--footer">
            <img src="{{ Auth::user()->avatar }}" alt="" class="mobile-sidebar__user-avatar">
            <div class="mobile-sidebar__user-meta">
                <span class="mobile-sidebar__user-name">{{ Auth::user()->name }}</span>
                <span class="mobile-sidebar__user-handle">{{ '@' . Auth::user()->username }}</span>
            </div>
        </a>
    @endguest
</div>
