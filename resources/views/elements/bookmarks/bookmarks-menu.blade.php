<nav class="bookmarks-page__nav" aria-label="{{ __('Bookmarks') }}">
    @foreach($bookmarkTypes as $route => $setting)
        @php
            switch ($route) {
                case 'all':
                    $tabLabel = __('All');
                    break;
                case 'photos':
                    $tabLabel = __('Photos');
                    break;
                case 'videos':
                    $tabLabel = __('Videos');
                    break;
                case 'audio':
                    $tabLabel = __('Audio');
                    break;
                case 'locked':
                    $tabLabel = __('Locked');
                    break;
                default:
                    $tabLabel = __(ucfirst($route));
            }
        @endphp
        <a class="bookmarks-page__nav-link {{ $activeTab == $route ? 'active' : '' }}" href="{{ route('my.bookmarks', ['type' => $route]) }}">
            <span class="bookmarks-page__nav-label">{{ $tabLabel }}</span>
        </a>
    @endforeach
</nav>
