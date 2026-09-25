@php
    $withdrawMinFormatted = \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(\App\Providers\PaymentsServiceProvider::getWithdrawalMinimumAmount());
    $withdrawMaxFormatted = \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(\App\Providers\PaymentsServiceProvider::getWithdrawalMaximumAmount());
@endphp

<div class="wallet-settings__panel">
<div class="wallet-settings__withdraw">
    <div class="wallet-settings__field">
        <div class="wallet-settings__input-wrap">
            <span class="wallet-settings__input-icon" id="withdrawal-amount-label" aria-hidden="true">
                @include('elements.icon', ['icon' => 'cash-outline', 'variant' => 'small', 'centered' => true])
            </span>
            <input class="form-control wallet-settings__input"
                   placeholder="{{ \App\Providers\PaymentsServiceProvider::getWithdrawalAmountLimitations() }}"
                   aria-label="{{ __('Amount') }}"
                   aria-describedby="withdrawal-amount-error"
                   id="withdrawal-amount"
                   type="number"
                   inputmode="decimal"
                   step="any">
        </div>
        <div class="wallet-settings__error" id="withdrawal-amount-error" role="alert" aria-live="polite">
            <span class="wallet-settings__error-icon" aria-hidden="true">
                @include('elements.icon', ['icon' => 'alert-circle-outline', 'variant' => 'small', 'centered' => true])
            </span>
            <span class="wallet-settings__error-text">{{ __('Please enter an amount between :min and :max.', ['min' => $withdrawMinFormatted, 'max' => $withdrawMaxFormatted]) }}</span>
        </div>
    </div>

    <div class="wallet-settings__row">
        <div class="wallet-settings__field wallet-settings__field--half">
            <label for="payment-methods">{{ __('Payment method') }}</label>
            <div class="wallet-settings__select-wrap">
                <select class="form-control wallet-settings__select" id="payment-methods" name="payment-methods">
                    @foreach(\App\Providers\PaymentsServiceProvider::getWithdrawalsAllowedPaymentMethods() as $paymentMethod)
                        <option value="{{ $paymentMethod }}">{{ __($paymentMethod) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="wallet-settings__field wallet-settings__field--half update-stripe-connect-box d-none">
            <label id="update-stripe-connect-label" for="update-stripe-connect">{{ __('Update details') }}</label>
            <a href="{{ route('withdrawals.onboarding') }}">
                <button type="button" id="update-stripe-connect" class="btn btn-primary btn-block rounded mr-0 wallet-settings__inline-btn">{{ __('Update') }}</button>
            </a>
        </div>
        <div class="wallet-settings__field wallet-settings__field--half input-label">
            <label id="payment-identifier-label" for="withdrawal-payment-identifier">{{ __('Bank account') }}</label>
            <input class="form-control wallet-settings__input wallet-settings__input--boxed"
                   type="text"
                   id="withdrawal-payment-identifier"
                   name="payment-identifier"
                   placeholder="{{ __('Enter payment account details') }}">
        </div>
    </div>

    <div class="wallet-settings__field input-message">
        <label for="withdrawal-message">{{ __('Message (Optional)') }}</label>
        <textarea placeholder="{{ __('Bank account, notes, etc') }}"
                  class="form-control wallet-settings__textarea"
                  id="withdrawal-message"
                  rows="3"
                  aria-describedby="withdrawal-message-error"></textarea>
        <div class="wallet-settings__error" id="withdrawal-message-error" role="alert" aria-live="polite">
            <span class="wallet-settings__error-icon" aria-hidden="true">
                @include('elements.icon', ['icon' => 'alert-circle-outline', 'variant' => 'small', 'centered' => true])
            </span>
            <span class="wallet-settings__error-text">{{ __('Please add your withdrawal notes: EG: Paypal or Bank account.') }}</span>
        </div>
    </div>

    <div class="stripe-connect-label d-none wallet-settings__notice">
        @if(!Auth::user()->country_id)
            <span>{{ __("You must set the country on your profile before you can start onboarding and withdraw money") }}</span>
        @elseif(!Auth::user()->stripe_onboarding_verified)
            <span>{{ __("We're using Stripe to get you paid quickly and keep your personal and payment information secure. Thousands of companies around the world trust Stripe to process payments for their users. Set up a Stripe account to get paid with us") }}</span>
        @endif
    </div>

    <div class="payment-error error text-danger d-none mt-3 wallet-settings__payment-error">{{ __('Add all required info') }}</div>

    <div class="stripe-connect-buttons d-none w-100">
        @if(!Auth::user()->country_id)
            <div class="mt-3">
                <a href="{{ route('my.settings', ['type' => 'profile']) }}">
                    <button type="button" class="btn btn-primary btn-block rounded mr-0 wallet-settings__inline-btn">{{ __('Set your country') }}</button>
                </a>
            </div>
        @elseif(!Auth::user()->stripe_onboarding_verified)
            <div class="mt-3">
                <a href="{{ route('withdrawals.onboarding') }}">
                    <button type="button" class="btn btn-primary btn-block rounded mr-0 wallet-settings__inline-btn">{{ !Auth::user()->stripe_account_id ? __('Start onboarding') : __('Update details') }}</button>
                </a>
            </div>
        @endif
    </div>

    <button class="btn btn-primary btn-block rounded mr-0 withdrawal-continue-btn wallet-settings__submit-btn" type="button">{{ __('Request withdrawal') }}</button>
</div>
</div>
