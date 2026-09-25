{{-- Paypal and stripe actual buttons --}}
<div class="paymentOption paymentPP d-none">
    <form id="wallet-deposit" method="post" action="{{route('payment.initiatePayment')}}" >
        @csrf
        <input type="hidden" name="amount" id="wallet-deposit-amount" value="1">
        <input type="hidden" name="transaction_type" id="payment-type" value="">
        <input type="hidden" name="provider" id="provider" value="">
        <input type="hidden" name="manual_payment_files" id="manual-payment-files" value="">
        <input type="hidden" name="manual_payment_description" id="manual-payment-description" value="">

        <button class="payment-button" type="submit"></button>
    </form>
</div>

<div class="paymentOption ml-2 paymentStripe d-none">
    <button id="stripe-checkout-button">{{__('Checkout')}}</button>
</div>

{{-- Actual form --}}
<div class="wallet-settings">
    @include('elements/message-alert', ['classes' =>'mb-2'])

    <div class="wallet-settings__balance" role="status">
        <div class="wallet-settings__balance-top">
            <span class="wallet-settings__balance-label">{{ __('Available balance') }}</span>
            <div class="wallet-settings__balance-icon" aria-hidden="true">
                @include('elements.icon', ['icon' => 'wallet', 'variant' => 'small', 'centered' => true])
            </div>
        </div>
        <div class="wallet-settings__balance-amount wallet-total-amount">{{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(number_format(Auth::user()->wallet ? Auth::user()->wallet->total : 0, 2, '.', ''))}}</div>
        <p class="wallet-settings__balance-desc">{{__('Deposit funds or become a creator to start earning.')}}</p>
    </div>

    <div class="wallet-settings__tabs inline-border-tabs">
        <nav class="nav nav-pills nav-justified">
            @foreach(\App\Providers\SettingsServiceProvider::allowWithdrawals(Auth::user()) ? ['deposit', 'withdraw'] : ['deposit'] as $tab)
                <a class="nav-item nav-link {{$activeTab == $tab ? 'active' : ''}}" href="{{route('my.settings',['type' => 'wallet', 'active' => $tab])}}">

                    <div class="d-flex align-items-center justify-content-center">
                        @if($tab == 'deposit')
                            @include('elements.icon',['icon'=>'wallet','variant'=>'medium','classes'=>'mr-2'])
                        @elseif(\App\Providers\SettingsServiceProvider::allowWithdrawals(Auth::user()))
                            @include('elements.icon',['icon'=>'card','variant'=>'medium','classes'=>'mr-2'])
                        @endif
                        {{__(ucfirst($tab))}}

                    </div>
                </a>
            @endforeach
        </nav>
    </div>

    @if($activeTab != null && $activeTab === 'withdraw' && \App\Providers\SettingsServiceProvider::allowWithdrawals(Auth::user()))
        @include('elements/settings/settings-wallet-withdraw')
    @else
        @include('elements/settings/settings-wallet-deposit')
    @endif

</div>
