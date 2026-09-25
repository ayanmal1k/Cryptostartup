<div class="notifications-empty-hero" style="--notif-empty-banner: url('{{ asset('img/notifications-empty-banner.png') }}')">
    <div class="notifications-empty-hero__content">
        <p class="notifications-empty-hero__eyebrow">{{ __('Nothing to see here yet') }}</p>
        <p class="notifications-empty-hero__title">{{ __('No notifications yet') }}</p>
        <p class="notifications-empty-hero__text">{{ __('When you get likes, messages, tips, and more, they will show up here.') }}</p>
    </div>
    <div class="notifications-empty-hero__icon" aria-hidden="true">
        @include('elements.icon', ['icon' => 'notifications-off-outline', 'variant' => 'small', 'centered' => true])
    </div>
</div>
