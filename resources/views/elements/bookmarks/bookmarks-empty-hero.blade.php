<div class="bookmarks-empty-hero" style="--bm-empty-banner: url('{{ asset('img/notifications-empty-banner.png') }}')">
    <div class="bookmarks-empty-hero__content">
        <p class="bookmarks-empty-hero__eyebrow">{{ __('Nothing saved yet') }}</p>
        <p class="bookmarks-empty-hero__title">{{ __('No posts available') }}</p>
        <p class="bookmarks-empty-hero__text">{{ __('Posts you bookmark will appear here for quick access later.') }}</p>
    </div>
    <div class="bookmarks-empty-hero__icon" aria-hidden="true">
        @include('elements.icon', ['icon' => 'bookmarks-outline', 'variant' => 'small', 'centered' => true])
    </div>
</div>
