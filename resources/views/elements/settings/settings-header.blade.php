<div class="settings-tab-header {{ $type == 'generic' ? 'settings-tab-header--generic' : 'settings-tab-header--mobile' }}">
    <div class="settings-tab-header__text">
        @if($type == 'generic')
            <h5 class="settings-tab-header__title text-bold {{ (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r')) }}">{{ __('Settings') }}</h5>
            <h6 class="settings-tab-header__subtitle text-muted">{{ __('Manage your account') }}</h6>
        @else
            <h5 class="settings-tab-header__title text-bold mt-0 mb-0 {{ (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r')) }}">{{ ucfirst(__($activeSettingsTab)) }}</h5>
            <h6 class="settings-tab-header__subtitle text-muted mt-2">{{ __($currentSettingTab['heading']) }}</h6>
        @endif
    </div>
    @if($type != 'generic')
        <div class="settings-tab-header__actions">
            <button type="button" class="settings-tab-header__menu-btn navbar-toggler d-lg-none" data-toggle="collapse" data-target="#settingsNav" aria-controls="settingsNav" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                @include('elements.icon',['icon'=>'options-outline','variant'=>'small','centered'=>true,'classes'=>'st-icon st-icon--menu-btn'])
            </button>
        </div>
    @endif
</div>
