<div class="notifications-toolbar">
    <div class="notifications-toolbar__left">
        <div class="notifications-list-filter-wrap">
            <button type="button" class="notifications-list-filter" id="notifications-list-filter-btn" aria-haspopup="listbox" aria-expanded="false" aria-controls="notifications-list-filter-menu">
                <span class="notifications-list-filter__label">{{ __('All') }}</span>
                @include('elements.icon', ['icon' => 'chevron-down-outline', 'variant' => 'small', 'centered' => true, 'classes' => 'notifications-list-filter__chevron'])
            </button>
            <div class="notifications-list-filter-menu" id="notifications-list-filter-menu" role="listbox" hidden>
                <button type="button" class="notifications-list-filter-option is-active" data-filter="all" role="option" aria-selected="true">{{ __('All') }}</button>
                <button type="button" class="notifications-list-filter-option" data-filter="unread" role="option" aria-selected="false">{{ __('Unread') }}</button>
                <button type="button" class="notifications-list-filter-option" data-filter="read" role="option" aria-selected="false">{{ __('Read') }}</button>
            </div>
        </div>
    </div>
    <div class="notifications-toolbar__right">
        <button type="button" class="notifications-toolbar__btn notifications-toolbar__btn--outline" id="notifications-mark-all-read">
            {{ __('Mark all as read') }}
        </button>
        <a href="{{ route('my.settings', ['type' => 'notifications']) }}" class="notifications-toolbar__btn notifications-toolbar__btn--icon" aria-label="{{ __('Notification settings') }}">
            @include('elements.icon', ['icon' => 'settings-outline', 'variant' => 'small', 'centered' => true])
        </a>
    </div>
</div>
