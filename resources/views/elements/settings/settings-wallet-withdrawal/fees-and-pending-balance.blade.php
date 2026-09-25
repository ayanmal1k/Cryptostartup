<div class="wallet-settings__fees">
    @if(getSetting('payments.withdrawal_allow_fees') && floatval(getSetting('payments.withdrawal_default_fee_percentage')) > 0)
        <div class="wallet-settings__fees-item">
            @include('elements.icon', ['icon' => 'information-circle-outline', 'variant' => 'small', 'centered' => false])
            <span id="pending-balance" title="{{ __('The payouts are manually and it usually take up to 24 hours for a withdrawal to be processed, we will notify you as soon as your request is processed.') }}">
                {{ __('A :feeAmount% fee will be applied.', ['feeAmount' => floatval(getSetting('payments.withdrawal_default_fee_percentage'))]) }}
            </span>
        </div>
    @endif
    <div class="wallet-settings__fees-item">
        @include('elements.icon', ['icon' => 'information-circle-outline', 'variant' => 'small', 'centered' => false])
        <span title="{{ __('The payouts are manually and it usually take up to 24 hours for a withdrawal to be processed, we will notify you as soon as your request is processed.') }}">
            {{ __('Pending balance') }} (<b class="wallet-pending-amount">{{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format(Auth::user()->wallet ? Auth::user()->wallet->pendingBalance : 0, 2, '.', '')) }}</b>)
        </span>
    </div>
</div>

@if(getSetting('payments.withdrawal_custom_message_box'))
    <div class="wallet-settings__notice" role="alert">
        {!! getSetting('payments.withdrawal_custom_message_box') !!}
    </div>
@endif
