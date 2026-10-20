@if(!Auth::user()->email_verified_at)
    <div class="account-settings__verify">
        @include('elements.resend-verification-email-box')
    </div>
@endif

<div class="account-settings">
    <div class="account-settings__intro">
        <div class="account-settings__intro-icon" aria-hidden="true">
            @include('elements.icon', ['icon' => 'lock-closed-outline', 'variant' => 'medium', 'centered' => true])
        </div>
        <div class="account-settings__intro-text">
            <h2 class="account-settings__intro-title">{{ __('Security') }}</h2>
            <p class="account-settings__intro-desc">{{ __('Update your password to keep your account secure.') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('my.settings.account.save') }}" class="account-settings__form">
        @csrf

        @if(session('success'))
            <div class="account-settings__alert" role="alert">
                <span class="account-settings__alert-icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'small', 'centered' => true])
                </span>
                <span class="account-settings__alert-text">{{ session('success') }}</span>
                <button type="button" class="account-settings__alert-close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="account-settings__card">
            <div class="account-settings__field">
                <label for="account-password">{{ __('Current password') }}</label>
                <div class="account-settings__input-wrap">
                    <span class="account-settings__input-icon" aria-hidden="true">
                        @include('elements.icon', ['icon' => 'key-outline', 'variant' => 'small', 'centered' => true])
                    </span>
                    <input class="form-control account-settings__input {{ $errors->has('password') ? 'is-invalid' : '' }}" id="account-password" name="password" type="password" autocomplete="current-password" placeholder="{{ __('Enter current password') }}">
                </div>
                @if($errors->has('password'))
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $errors->first('password') }}</strong>
                    </span>
                @endif
            </div>

            <div class="account-settings__field">
                <label for="account-new-password">{{ __('New password') }}</label>
                <div class="account-settings__input-wrap">
                    <span class="account-settings__input-icon" aria-hidden="true">
                        @include('elements.icon', ['icon' => 'lock-closed-outline', 'variant' => 'small', 'centered' => true])
                    </span>
                    <input class="form-control account-settings__input {{ $errors->has('new_password') ? 'is-invalid' : '' }}" id="account-new-password" name="new_password" type="password" autocomplete="new-password" placeholder="{{ __('Enter new password') }}">
                </div>
                @if($errors->has('new_password'))
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $errors->first('new_password') }}</strong>
                    </span>
                @endif
            </div>

            <div class="account-settings__field account-settings__field--last">
                <label for="account-confirm-password">{{ __('Confirm password') }}</label>
                <div class="account-settings__input-wrap">
                    <span class="account-settings__input-icon" aria-hidden="true">
                        @include('elements.icon', ['icon' => 'shield-checkmark-outline', 'variant' => 'small', 'centered' => true])
                    </span>
                    <input class="form-control account-settings__input {{ $errors->has('confirm_password') ? 'is-invalid' : '' }}" id="account-confirm-password" name="confirm_password" type="password" autocomplete="new-password" placeholder="{{ __('Confirm new password') }}">
                </div>
                @if($errors->has('confirm_password'))
                    <span class="invalid-feedback d-block" role="alert">
                        <strong>{{ $errors->first('confirm_password') }}</strong>
                    </span>
                @endif
            </div>
        </div>

        <button class="btn btn-primary account-settings__submit" type="submit">
            {{ __('Save') }}
        </button>
    </form>
</div>
