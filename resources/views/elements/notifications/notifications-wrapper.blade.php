<div class="notifications-list{{ count($notifications) ? '' : ' notifications-list--empty' }}">
    @if(count($notifications))
        <div class="notifications-feed">
            @foreach($notifications as $notification)
                @include('elements.notifications.notification-box', ['notification' => $notification])
            @endforeach
        </div>
        <div class="notifications-pagination">
            {{ $notifications->onEachSide(1)->links() }}
        </div>
    @else
        @include('elements.notifications.notifications-empty-hero')
    @endif
</div>
