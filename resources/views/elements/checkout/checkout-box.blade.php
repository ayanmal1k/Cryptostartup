@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp
<div class="row checkout-dialog">
    <div class="col-lg-6 mx-auto">
        {{-- Paypal and stripe actual buttons --}}
        <div class="paymentOption paymentPP d-none">
            <form id="pp-buyItem" method="post" action="{{route('payment.initiatePayment')}}">
                @csrf
                <input type="hidden" name="amount" id="payment-deposit-amount" value="">
                <input type="hidden" name="transaction_type" id="payment-type" value="">
                <input type="hidden" name="post_id" id="post" value="">
                <input type="hidden" name="user_message_id" id="userMessage" value="">
                <input type="hidden" name="recipient_user_id" id="recipient" value="">
                <input type="hidden" name="provider" id="provider" value="">
                <input type="hidden" name="first_name" id="paymentFirstName" value="">
                <input type="hidden" name="last_name" id="paymentLastName" value="">
                <input type="hidden" name="billing_address" id="paymentBillingAddress" value="">
                <input type="hidden" name="city" id="paymentCity" value="">
                <input type="hidden" name="state" id="paymentState" value="">
                <input type="hidden" name="postcode" id="paymentPostcode" value="">
                <input type="hidden" name="country" id="paymentCountry" value="">
                <input type="hidden" name="taxes" id="paymentTaxes" value="">
                <input type="hidden" name="stream" id="stream" value="">
                <button class="payment-button" type="submit"></button>
            </form>
        </div>

        <div class="paymentOption ml-2 paymentStripe d-none">
            <button id="stripe-checkout-button">{{__('Checkout')}}</button>
        </div>

        <!-- Modal -->
        <div class="checkout-popup modal fade pf-modal-root" id="checkout-center" tabindex="-1" role="dialog" aria-labelledby="checkout" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered co-modal-dialog" role="document">
                <div class="modal-content co-modal {{ $isDarkTheme ? 'co-modal--dark' : 'co-modal--light' }}">
                    <div class="co-modal__header">
                        <div class="co-modal__header-text">
                            <h5 class="co-modal__title" id="payment-title"></h5>
                            <p class="co-modal__sub">{{__('Complete your payment securely')}}</p>
                        </div>
                        <button type="button" class="co-modal__close" data-dismiss="modal" aria-label="{{__('Close')}}">
                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                        </button>
                    </div>

                    <div class="modal-body co-modal__body">
                        <div class="payment-body">
                            <div class="co-recipient">
                                <img src="" class="rounded-circle user-avatar co-recipient__avatar" alt="">
                                <div class="co-recipient__meta">
                                    <div class="name co-recipient__name"></div>
                                    <div class="text-muted username co-recipient__username"><span>@</span></div>
                                </div>
                            </div>

                            <div class="payment-description co-description mb-3 d-none"></div>

                            <div class="co-field checkout-amount-input d-none">
                                <label class="co-label" for="checkout-amount">{{__('Amount')}}</label>
                                <div class="co-amount">
                                    <span class="co-amount__icon" id="amount-label">
                                        @include('elements.icon',['icon'=>'cash-outline','variant'=>'medium','centered'=>false])
                                    </span>
                                    <input
                                        class="form-control uifield-amount co-input"
                                        placeholder="{{__(\App\Providers\SettingsServiceProvider::leftAlignedCurrencyPosition() ? 'Amount ($5 min, $500 max)' : 'Amount (5$ min, 500$ max)',['min'=>getSetting('payments.min_tip_value'),'max'=>getSetting('payments.max_tip_value'),'currency'=>config('app.site.currency_symbol')])}}"
                                        aria-label="Amount"
                                        aria-describedby="amount-label"
                                        id="checkout-amount"
                                        type="number"
                                        min="0"
                                        step="1"
                                        max="500"
                                    >
                                </div>
                                <div class="invalid-feedback">{{__('Please enter a valid amount.')}}</div>
                            </div>
                        </div>

                        <div id="accordion" class="co-accordion mb-3">
                            <div class="card co-card">
                                <button
                                    type="button"
                                    class="card-header co-accordion__toggle d-flex justify-content-between align-items-center"
                                    id="headingOne"
                                    data-toggle="collapse"
                                    data-target="#billingInformation"
                                    aria-expanded="true"
                                    aria-controls="billingInformation"
                                >
                                    <h6 class="mb-0">{{__('Billing agreement details')}}</h6>
                                    <span class="co-accordion__chevron label-icon">
                                        @include('elements.icon',['icon'=>'chevron-down-outline','centered'=>false])
                                    </span>
                                </button>
                                <div id="billingInformation" class="collapse show" aria-labelledby="headingOne" data-parent="#accordion">
                                    <div class="card-body co-card__body">
                                        <form id="billing-agreement-form">
                                            <div class="tab-content">
                                                <div id="individual" class="tab-pane fade show active">
                                                    <div class="row">
                                                        <div class="col-sm-6 col-6">
                                                            <div class="co-field form-group">
                                                                <label class="co-label" for="firstName"><span>{{__('First name')}}</span></label>
                                                                <input type="text" name="firstName" id="firstName" placeholder="{{__('First name')}}" onchange="checkout.validateFirstNameField();" required class="form-control uifield-first_name co-input">
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-6">
                                                            <div class="co-field form-group">
                                                                <label class="co-label" for="lastName"><span>{{__('Last name')}}</span></label>
                                                                <input type="text" name="lastName" id="lastName" placeholder="{{__('Last name')}}" onblur="checkout.validateLastNameField()" required class="form-control uifield-last_name co-input">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="co-field form-group">
                                                        <label class="co-label" for="countrySelect"><span>{{__('Country')}}</span></label>
                                                        <select class="country-select form-control input-sm uifield-country co-input" id="countrySelect" required onchange="checkout.validateCountryField()"></select>
                                                    </div>
                                                    <div class="co-field form-group">
                                                        <label class="co-label" for="billingCity"><span>{{__('City')}}</span></label>
                                                        <input type="text" name="billingCity" id="billingCity" placeholder="{{__('City')}}" onblur="checkout.validateCityField()" required class="form-control uifield-city co-input">
                                                    </div>
                                                    <div class="row">
                                                        <div class="col-sm-6 col-6">
                                                            <div class="co-field form-group">
                                                                <label class="co-label" for="billingState"><span>{{__('State')}}</span></label>
                                                                <input type="text" name="billingState" id="billingState" placeholder="{{__('State')}}" onblur="checkout.validateStateField()" required class="form-control uifield-state co-input">
                                                            </div>
                                                        </div>
                                                        <div class="col-sm-6 col-6">
                                                            <div class="co-field form-group">
                                                                <label class="co-label" for="billingPostcode"><span>{{__('Postcode')}}</span></label>
                                                                <input type="text" name="billingPostcode" id="billingPostcode" placeholder="{{__('Postcode')}}" onblur="checkout.validatePostcodeField()" required class="form-control uifield-postcode co-input">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="co-field form-group mb-0">
                                                        <label class="co-label" for="billingAddress"><span>{{__('Address')}}</span></label>
                                                        <textarea rows="2" type="text" name="billingAddress" id="billingAddress" onblur="checkout.validateBillingAddressField()" placeholder="{{__('Street address, apartment, suite, unit')}}" class="form-control w-100 uifield-billing_address co-input co-textarea" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="billing-agreement-error error text-danger d-none mt-2">{{__('Please complete all billing details')}}</div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="co-summary mb-3">
                            <h6 class="co-section-title">{{__('Payment summary')}}</h6>
                            <div class="subtotal row co-summary__row">
                                <span class="col-sm left">{{__('Subtotal')}}</span>
                                <span class="subtotal-amount col-sm right text-right"><b>$0.00</b></span>
                            </div>
                            <div class="taxes row co-summary__row">
                                <span class="col-sm left">{{__('Taxes')}}</span>
                            </div>
                            <div class="taxes-details"></div>
                            <div class="total row co-summary__row co-summary__total">
                                <span class="col-sm left"><b>{{__('Total')}}</b></span>
                                <span class="total-amount col-sm right text-right"><b>$0.00</b></span>
                            </div>
                        </div>

                        <div class="co-methods mb-2">
                            <h6 class="co-section-title">{{__('Payment method')}}</h6>
                            <div class="d-flex text-left radio-group row px-1">
                                @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 stripe-payment-method">
                                        <div class="radio mx-auto stripe-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="stripe">
                                            <img src="{{asset('/img/logos/stripe.svg')}}" alt="Stripe">
                                        </div>
                                    </div>
                                @endif
                                @if(config('paypal.client_id') && config('paypal.secret') && !getSetting('payments.paypal_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 paypal-payment-method">
                                        <div class="radio mx-auto paypal-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="paypal">
                                            <img src="{{asset('/img/logos/paypal.svg')}}" alt="PayPal">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.coinbase_api_key') && !getSetting('payments.coinbase_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none coinbase-payment-method">
                                        <div class="radio mx-auto coinbase-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="coinbase">
                                            <img src="{{asset('/img/logos/coinbase.svg')}}" alt="Coinbase">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.nowpayments_api_key') && !getSetting('payments.nowpayments_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none nowpayments-payment-method">
                                        <div class="radio mx-auto nowpayments-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="nowpayments">
                                            <img src="{{asset('/img/logos/nowpayments.svg')}}" alt="NOWPayments">
                                        </div>
                                    </div>
                                @endif
                                @if(\App\Providers\PaymentsServiceProvider::ccbillCredentialsProvided())
                                    <div class="p-1 col-6 col-md-3 d-none ccbill-payment-method">
                                        <div class="radio mx-auto ccbill-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="ccbill">
                                            <img src="{{asset('/img/logos/ccbill.svg')}}" alt="CCBill">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.paystack_secret_key') && !getSetting('payments.paystack_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none paystack-payment-method">
                                        <div class="radio mx-auto paystack-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="paystack">
                                            <img src="{{asset('/img/logos/paystack.svg')}}" alt="Paystack">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.stripe_secret_key') && getSetting('payments.stripe_public_key') && !getSetting('payments.stripe_checkout_disabled') && getSetting('payments.stripe_oxxo_provider_enabled'))
                                    <div class="p-1 col-6 col-md-3 d-none oxxo-payment-method">
                                        <div class="radio mx-auto oxxo-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="oxxo">
                                            <img src="{{asset('/img/logos/oxxo.svg')}}" alt="OXXO">
                                        </div>
                                    </div>
                                @endif
                                @if(getSetting('payments.mercado_access_token') && !getSetting('payments.mercado_checkout_disabled'))
                                    <div class="p-1 col-6 col-md-3 d-none mercado-payment-method">
                                        <div class="radio mx-auto mercado-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="mercado">
                                            <img src="{{asset('/img/logos/mercado.svg')}}" alt="Mercado">
                                        </div>
                                    </div>
                                @endif
                                <div class="credit-payment-method p-1 col-6 col-md-3" @if(!Auth::check() || !Auth::user()->wallet || Auth::user()->wallet->total <= 0) data-toggle="tooltip" data-placement="right" @endif title="{{__('You can use the wallet deposit page to add credit.')}}">
                                    <div class="radio mx-auto credit-payment-provider checkout-payment-provider d-flex align-items-center justify-content-center" data-value="credit">
                                        <div class="credit-provider-text">
                                            <b>{{__("Credit")}}</b>
                                            <div class="available-credit">({{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(Auth::check() && Auth::user()->wallet ? Auth::user()->wallet->total : '0')}})</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="payment-error error text-danger text-bold d-none mb-2">{{__('Please select your payment method')}}</div>
                        <p class="co-note">{{__('Note: After clicking on the button, you will be directed to a secure gateway for payment. After completing the payment process, you will be redirected back to the website.')}}</p>
                    </div>

                    <div class="modal-footer co-modal__footer">
                        <button type="button" class="co-btn co-btn--ghost" data-dismiss="modal">{{__('Cancel')}}</button>
                        <button type="submit" class="co-btn co-btn--primary checkout-continue-btn">
                            {{__('Continue')}}
                            <div class="spinner-border spinner-border-sm ml-2 d-none" role="status">
                                <span class="sr-only">{{__('Loading...')}}</span>
                            </div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
