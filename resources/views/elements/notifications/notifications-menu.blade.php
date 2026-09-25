<nav class="notifications-page__nav" aria-label="{{ __('Notification categories') }}">
    <a class="notifications-page__nav-link {{ !$activeType ? 'active' : '' }}" href="{{ route('my.notifications') }}">
        <span class="notifications-page__nav-label">{{ __('All') }}</span>
    </a>
    @foreach($notificationTypes as $type)
        @php
            switch ($type) {
                case 'messages':
                    $tabLabel = __('Messages');
                    break;
                case 'likes':
                    $tabLabel = __('Likes');
                    break;
                case 'subscriptions':
                    $tabLabel = __('Subscriptions');
                    break;
                case 'tips':
                    $tabLabel = __('Tips');
                    break;
                case 'promos':
                    $tabLabel = __('Promos');
                    break;
                case 'PPV':
                    $tabLabel = __('PPV');
                    break;
                default:
                    $tabLabel = __(ucfirst($type));
            }
        @endphp
        <a class="notifications-page__nav-link {{ $activeType == $type ? 'active' : '' }}" href="{{ route('my.notifications', ['type' => $type]) }}">
            <span class="notifications-page__nav-label">{{ $tabLabel }}</span>
        </a>
    @endforeach
</nav>
