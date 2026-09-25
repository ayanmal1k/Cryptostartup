<div class="privacy-settings">
    <div class="privacy-settings__card">
        <div class="privacy-settings__header">
            <span class="privacy-settings__title" id="public_profile-label">{{ __('Is public account') }}</span>
            <label class="privacy-settings__toggle" for="public_profile" aria-labelledby="public_profile-label">
                <input type="checkbox"
                       class="privacy-settings__toggle-input custom-control-input"
                       id="public_profile"
                    {{ Auth::user()->public_profile ? 'checked' : '' }}>
                <span class="privacy-settings__toggle-track" aria-hidden="true"></span>
            </label>
        </div>
        <div class="privacy-settings__details">
            <p class="privacy-settings__details-lead">{{ __('Having your profile set to private means:') }}</p>
            <ul class="privacy-settings__list">
                <li>{{ __('It will be hidden for search engines and unregistered users entirely.') }}</li>
                <li>{{ __('It will also be generally hidden from suggestions and user searches on our platform.') }}</li>
            </ul>
        </div>
    </div>

    @if(getSetting('profiles.allow_users_enabling_open_profiles'))
        <div class="privacy-settings__card">
            <div class="privacy-settings__header">
                <span class="privacy-settings__title" id="open_profile-label">{{ __('Open profile') }}</span>
                <label class="privacy-settings__toggle" for="open_profile" aria-labelledby="open_profile-label">
                    <input type="checkbox"
                           class="privacy-settings__toggle-input custom-control-input"
                           id="open_profile"
                        {{ Auth::user()->open_profile ? 'checked' : '' }}>
                    <span class="privacy-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>
            <div class="privacy-settings__details">
                <p class="privacy-settings__details-lead">{{ __('Having your profile set to open means:') }}</p>
                <ul class="privacy-settings__list">
                    <li>{{ __('Both registered and unregistered users will be able to see your posts.') }}</li>
                    <li>{{ __('If account is private, the content will only be available for registered used.') }}</li>
                    <li>{{ __('Paid posts will still be locked for open profiles.') }}</li>
                </ul>
            </div>
        </div>
    @endif

    @if(getSetting('security.allow_geo_blocking'))
        <div class="privacy-settings__card">
            <div class="privacy-settings__header">
                <div class="privacy-settings__header-copy">
                    <span class="privacy-settings__title" id="enable_geoblocking-label">{{ __('Enable Geo-blocking') }}</span>
                    <p class="privacy-settings__hint">{{ __('If enabled, visitors from certain countries will be restricted access.') }}</p>
                </div>
                <label class="privacy-settings__toggle" for="enable_geoblocking" aria-labelledby="enable_geoblocking-label">
                    <input type="checkbox"
                           class="privacy-settings__toggle-input custom-control-input"
                           id="enable_geoblocking"
                        {{ Auth::user()->enable_geoblocking ? 'checked' : '' }}>
                    <span class="privacy-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>
            <div class="privacy-settings__field">
                <label class="privacy-settings__field-label" for="countrySelect">{{ __('Country') }}</label>
                <select class="country-select form-control input-sm uifield-country privacy-settings__select" id="countrySelect" required multiple="multiple">
                    @foreach($countries as $country)
                        @if($country->name !== 'All')
                            <option>{{ $country->name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
        </div>
    @endif

    @if(getSetting('security.allow_users_2fa_switch'))
        <div class="privacy-settings__card">
            <div class="privacy-settings__header">
                <div class="privacy-settings__header-copy">
                    <span class="privacy-settings__title" id="enable_2fa-label">{{ __('Enable email 2FA') }}</span>
                    <p class="privacy-settings__hint">{{ __('If enabled, access from new devices will be restricted until verified.') }}</p>
                </div>
                <label class="privacy-settings__toggle" for="enable_2fa" aria-labelledby="enable_2fa-label">
                    <input type="checkbox"
                           class="privacy-settings__toggle-input custom-control-input"
                           id="enable_2fa"
                        {{ Auth::user()->enable_2fa ? 'checked' : '' }}>
                    <span class="privacy-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>

            <div class="allowed-devices privacy-settings__devices {{ Auth::user()->enable_2fa ? '' : 'd-none' }}">
                @if($verifiedDevicesCount)
                    <p class="privacy-settings__devices-title">{{ __('Allowed devices') }}</p>
                    <div class="privacy-settings__devices-list">
                        @include('elements.settings.user-devices-list', ['type' => 'verified'])
                    </div>
                @endif
                @if($unverifiedDevicesCount)
                    <p class="privacy-settings__devices-title">{{ __('Un-verified devices') }}</p>
                    <div class="privacy-settings__devices-list">
                        @include('elements.settings.user-devices-list', ['type' => 'unverified'])
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@include('elements.settings.device-delete-dialog')
