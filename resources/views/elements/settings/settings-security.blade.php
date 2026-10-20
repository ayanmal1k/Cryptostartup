@php
    $me = Auth::user();

    $twoFaOn = (bool) $me->enable_2fa;
    $emailVerified = (bool) $me->email_verified_at;
    $deviceCount = isset($devices) ? $devices->count() : 0;
    $unverified = $unverifiedDevicesCount ?? 0;

    /**
     * Security posture, so the page opens with an assessment rather than a list of toggles.
     * Only checks we can actually verify from stored state — nothing invented.
     */
    $checks = [
        [
            'label' => __('Two-factor authentication'),
            'ok' => $twoFaOn,
            'good' => __('Enabled — new devices must verify by email.'),
            'bad' => __('Off. Anyone with your password can sign in.'),
        ],
        [
            'label' => __('Email verified'),
            'ok' => $emailVerified,
            'good' => __('Your email is confirmed.'),
            'bad' => __('Unverified — you cannot recover your account without it.'),
        ],
        [
            'label' => __('Unrecognised devices'),
            'ok' => $unverified === 0,
            'good' => __('Every device on your account is verified.'),
            'bad' => trans_choice('{1} :count device has not been verified.|[2,*] :count devices have not been verified.', $unverified, ['count' => $unverified]),
        ],
    ];
    $passed = count(array_filter($checks, fn ($c) => $c['ok']));
    $score = count($checks) ? (int) round($passed / count($checks) * 100) : 0;
@endphp

<div class="profile-settings ps">

    {{-- Posture ------------------------------------------------------------------- --}}
    <div class="ps-card ps-complete">
        <div class="ps-complete__head">
            <div>
                <h2 class="ps-complete__title">{{ __('Account security') }}</h2>
                <p class="ps-complete__sub">
                    {{ $passed === count($checks)
                        ? __('Everything we can check looks good.')
                        : trans_choice('{1} :count thing needs your attention.|[2,*] :count things need your attention.', count($checks) - $passed, ['count' => count($checks) - $passed]) }}
                </p>
            </div>
            <span class="ps-complete__pct">{{ $score }}%</span>
        </div>
        <div class="ps-complete__track" role="progressbar" aria-valuenow="{{ $score }}" aria-valuemin="0" aria-valuemax="100">
            <div class="ps-complete__fill" style="width: {{ $score }}%"></div>
        </div>

        <ul class="sec-checks">
            @foreach($checks as $c)
                <li class="sec-check {{ $c['ok'] ? 'is-ok' : 'is-warn' }}">
                    <span class="sec-check__icon" aria-hidden="true">
                        @if($c['ok'])
                            <svg viewBox="0 0 24 24"><path d="M20 6 9 17l-5-5"/></svg>
                        @else
                            <svg viewBox="0 0 24 24"><path d="M12 9v4m0 4h.01"/><circle cx="12" cy="12" r="9"/></svg>
                        @endif
                    </span>
                    <span class="sec-check__text">
                        <strong>{{ $c['label'] }}</strong>
                        <span>{{ $c['ok'] ? $c['good'] : $c['bad'] }}</span>
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    {{-- Two-factor ----------------------------------------------------------------- --}}
    <div class="ps-card">
        <div class="ps-card__head">
            <h2 class="ps-card__title">{{ __('Two-factor authentication') }}</h2>
            <p class="ps-card__sub">{{ __('When on, signing in from a new device requires a code sent to your email.') }}</p>
        </div>

        <div class="sec-row">
            <div class="sec-row__text">
                <strong>{{ __('Email two-factor') }}</strong>
                <span>{{ $twoFaOn ? __('Currently enabled') : __('Currently disabled') }}</span>
            </div>
            {{-- Same markup the privacy page uses, so it picks up the existing toggle styling.
                 The change handler is wired below against GeneralSettings.updateFlagSetting
                 (from settings.js, loaded on every settings tab) rather than by loading
                 privacy.js here — privacy.js also needs a userGeoBlocking global and would
                 throw on this page. --}}
            <label class="privacy-settings__toggle" for="enable_2fa">
                <input type="checkbox"
                       class="privacy-settings__toggle-input"
                       id="enable_2fa"
                       {{ $twoFaOn ? 'checked' : '' }}>
                <span class="privacy-settings__toggle-track" aria-hidden="true"></span>
                <span class="sr-only">{{ __('Enable email 2FA') }}</span>
            </label>
        </div>
    </div>

    {{-- Password ------------------------------------------------------------------- --}}
    <div class="ps-card">
        <div class="ps-card__head">
            <h2 class="ps-card__title">{{ __('Password') }}</h2>
            <p class="ps-card__sub">{{ __('Use a password you do not use anywhere else.') }}</p>
        </div>

        {{-- Field names must match UpdateUserSettingsRequest exactly: password (current, checked
             by the MatchOldPassword rule), new_password, confirm_password. saveAccount() hashes
             confirm_password. --}}
        <form method="POST" action="{{ route('my.settings.account.save') }}" class="profile-settings__fields">
            @csrf
            <div class="form-group profile-settings__field">
                <label for="sec_password">{{ __('Current password') }}</label>
                <input type="password" class="form-control {{ $errors->has('password') ? 'is-invalid' : '' }}"
                       id="sec_password" name="password" autocomplete="current-password">
                @if($errors->has('password'))
                    <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('password') }}</strong></span>
                @endif
            </div>
            <div class="profile-settings__row">
                <div class="profile-settings__row-col profile-settings__row-col--half">
                    <div class="form-group profile-settings__field">
                        <label for="sec_new_password">{{ __('New password') }}</label>
                        <input type="password" class="form-control {{ $errors->has('new_password') ? 'is-invalid' : '' }}"
                               id="sec_new_password" name="new_password" autocomplete="new-password" minlength="8">
                        @if($errors->has('new_password'))
                            <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('new_password') }}</strong></span>
                        @endif
                    </div>
                </div>
                <div class="profile-settings__row-col profile-settings__row-col--half">
                    <div class="form-group profile-settings__field">
                        <label for="sec_confirm_password">{{ __('Confirm new password') }}</label>
                        <input type="password" class="form-control {{ $errors->has('confirm_password') ? 'is-invalid' : '' }}"
                               id="sec_confirm_password" name="confirm_password" autocomplete="new-password" minlength="8">
                        @if($errors->has('confirm_password'))
                            <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('confirm_password') }}</strong></span>
                        @endif
                    </div>
                </div>
            </div>
            <p class="ps-hint">{{ __('At least 8 characters.') }}</p>
            <button type="submit" class="btn btn-primary profile-settings__submit">{{ __('Update password') }}</button>
        </form>
    </div>

    {{-- Devices -------------------------------------------------------------------- --}}
    <div class="ps-card">
        <div class="ps-card__head">
            <h2 class="ps-card__title">{{ __('Devices & sessions') }}</h2>
            <p class="ps-card__sub">
                {{ __('Everywhere your account has signed in. Remove anything you do not recognise.') }}
            </p>
        </div>

        @if($deviceCount)
            <ul class="sec-devices">
                @foreach($devices as $device)
                    <li class="sec-device">
                        <span class="sec-device__icon" aria-hidden="true">
                            @if($device->device_type === 'Mobile')
                                <svg viewBox="0 0 24 24"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/></svg>
                            @elseif($device->device_type === 'Tablet')
                                <svg viewBox="0 0 24 24"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M11 18h2"/></svg>
                            @else
                                <svg viewBox="0 0 24 24"><rect x="2" y="4" width="20" height="12" rx="2"/><path d="M8 20h8M12 16v4"/></svg>
                            @endif
                        </span>
                        <span class="sec-device__meta">
                            <strong>{{ $device->browser ?: __('Unknown browser') }} · {{ $device->platform ?: $device->device_type }}</strong>
                            <span>
                                {{ $device->ip }}
                                @if($device->last_login)
                                    · {{ __('last used') }} {{ \Carbon\Carbon::parse($device->last_login)->diffForHumans() }}
                                @endif
                            </span>
                        </span>
                        <span class="sec-device__right">
                            @if($device->verified_at)
                                <span class="sec-tag sec-tag--ok">{{ __('Verified') }}</span>
                            @else
                                <span class="sec-tag sec-tag--warn">{{ __('Unverified') }}</span>
                            @endif
                            <button type="button"
                                    class="sec-device__remove"
                                    data-device-id="{{ $device->id }}"
                                    aria-label="{{ __('Remove device') }}">{{ __('Remove') }}</button>
                        </span>
                    </li>
                @endforeach
            </ul>
        @else
            <p class="ps-hint" style="padding: 4px 2px 8px;">{{ __('No devices recorded yet.') }}</p>
        @endif
    </div>

    {{-- Pointers to the rest ------------------------------------------------------- --}}
    <div class="ps-card">
        <div class="ps-card__head">
            <h2 class="ps-card__title">{{ __('More controls') }}</h2>
            <p class="ps-card__sub">{{ __('Privacy, blocking and identity live on their own pages.') }}</p>
        </div>
        <div class="ps-links">
            <a href="{{ route('my.settings', ['type' => 'privacy']) }}" class="ps-link">
                <span class="ps-link__title">{{ __('Privacy') }}</span>
                <span class="ps-link__sub">{{ __('Profile visibility and geo-blocking') }}</span>
            </a>
            @if(array_key_exists('verify', $availableSettings ?? []))
                <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="ps-link">
                    <span class="ps-link__title">{{ __('Verification') }}</span>
                    <span class="ps-link__sub">{{ __('Confirm your identity to earn') }}</span>
                </a>
            @endif
            <a href="{{ route('my.settings', ['type' => 'notifications']) }}" class="ps-link">
                <span class="ps-link__title">{{ __('Notifications') }}</span>
                <span class="ps-link__sub">{{ __('What we email you about') }}</span>
            </a>
            <a href="{{ route('my.settings', ['type' => 'account']) }}" class="ps-link">
                <span class="ps-link__title">{{ __('Account') }}</span>
                <span class="ps-link__sub">{{ __('Email and account controls') }}</span>
            </a>
        </div>
    </div>
</div>

<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]');

    // 2FA toggle -> the same flags endpoint the privacy page uses.
    var twoFa = document.getElementById('enable_2fa');
    if (twoFa) {
        twoFa.addEventListener('change', function () {
            if (window.GeneralSettings && GeneralSettings.updateFlagSetting) {
                GeneralSettings.updateFlagSetting('enable_2fa', twoFa.checked);
            }
        });
    }

    // Device removal uses the existing 2fa.delete endpoint rather than a new one.
    document.querySelectorAll('.sec-device__remove').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (!window.confirm(@json(__('Remove this device? It will have to verify again next time it signs in.')))) return;

            btn.disabled = true;
            fetch(@json(route('2fa.delete')), {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf ? csrf.getAttribute('content') : '',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ id: btn.dataset.deviceId })
            })
            .then(function (r) { return r.json().catch(function () { return {}; }); })
            .then(function () { window.location.reload(); })
            .catch(function () { btn.disabled = false; });
        });
    });
})();
</script>
