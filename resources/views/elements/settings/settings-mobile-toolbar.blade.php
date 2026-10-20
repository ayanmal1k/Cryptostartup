<div class="settings-mobile-toolbar d-md-none">
    <div class="settings-mobile-toolbar__text">
        <h2 class="settings-mobile-toolbar__title">{{ ucfirst(__($activeSettingsTab)) }}</h2>
    </div>
    <button type="button" class="settings-mobile-toolbar__btn" data-settings-inline-drawer-toggle aria-controls="settingsInlineNav" aria-expanded="false" aria-label="{{ __('Settings menu') }}">
        @include('elements.icon',['icon'=>'options-outline','variant'=>'small','centered'=>true,'classes'=>'st-icon st-icon--menu-btn'])
    </button>
</div>

<div class="settings-inline-drawer d-md-none" id="settingsInlineDrawer">
    <div class="settings-inline-drawer__inner">
        @include('elements.settings.settings-menu', ['availableSettings' => $availableSettings, 'navId' => 'settingsInlineNav'])
    </div>
</div>
