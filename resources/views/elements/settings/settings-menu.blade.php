<!-- Collapsible Menu -->
<div class="collapse d-lg-block settings-nav" id="{{ $navId ?? 'settingsNav' }}">
    <div class="card-settings settings-nav__card">
        <nav class="settings-nav__list" aria-label="{{ __('Settings navigation') }}">
            <a href="{{ route('creator.dashboard') }}" class="settings-nav__link settings-nav__link--creator">
                <span class="settings-nav__icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'analytics-outline', 'centered' => 'false', 'variant' => 'medium'])
                </span>
                <span class="settings-nav__label">{{ __('Creator Dashboard') }}</span>
                <span class="settings-nav__chevron" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'chevron-forward-outline', 'variant' => 'small'])
                </span>
            </a>

            @foreach($availableSettings as $route => $setting)
                <a href="{{ route('my.settings', ['type' => $route]) }}"
                   class="settings-nav__link{{ $activeSettingsTab == $route ? ' is-active' : '' }}">
                    <span class="settings-nav__icon" aria-hidden="true">
                        @include('elements.icon', ['icon' => $setting['icon'].'-outline', 'centered' => 'false', 'variant' => 'medium'])
                    </span>
                    <span class="settings-nav__label">{{ ucfirst(__($route)) }}</span>
                    <span class="settings-nav__chevron" aria-hidden="true">
                        @include('elements.icon', ['icon' => 'chevron-forward-outline', 'variant' => 'small'])
                    </span>
                </a>
            @endforeach

            <p class="settings-nav__section-label">{{ __('Legal & Compliance') }}</p>

            <a href="{{ route('dmca.index') }}" class="settings-nav__link">
                <span class="settings-nav__icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'shield-checkmark-outline', 'centered' => 'false', 'variant' => 'medium'])
                </span>
                <span class="settings-nav__label">{{ __('DMCA & Copyright') }}</span>
                <span class="settings-nav__chevron" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'chevron-forward-outline', 'variant' => 'small'])
                </span>
            </a>
        </nav>
    </div>
</div>
