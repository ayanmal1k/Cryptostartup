<div class="subs-empty-hero" style="--subs-empty-banner: url('{{ asset('img/notifications-empty-banner.png') }}')">
    <div class="subs-empty-hero__content">
        <p class="subs-empty-hero__eyebrow">{{ __('Your active subscriptions') }}</p>
        <p class="subs-empty-hero__title">{{ __('No subscriptions yet') }}</p>
        <p class="subs-empty-hero__text">{{ __('There are no active or cancelled subscriptions at the moment.') }}</p>
        <a href="{{ route('feed') }}" class="subs-empty-hero__cta">
            @include('elements.icon', ['icon' => 'compass-outline', 'variant' => 'small', 'centered' => true, 'classes' => 'subs-icon'])
            <span>{{ __('Discover') }}</span>
        </a>
    </div>
    <div class="subs-empty-hero__icon" aria-hidden="true">
        @include('elements.icon', ['icon' => 'people-outline', 'variant' => 'small', 'centered' => true])
    </div>
</div>
