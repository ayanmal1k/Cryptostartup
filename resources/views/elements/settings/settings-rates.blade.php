<div class="rates-settings">
    @if(session('success'))
        <div class="rates-settings__alert rates-settings__alert--success" role="alert">
            <span class="rates-settings__alert-icon" aria-hidden="true">
                @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'small', 'centered' => true])
            </span>
            <span class="rates-settings__alert-text">{{ session('success') }}</span>
            <button type="button" class="rates-settings__alert-close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(getSetting('profiles.allow_users_enabling_open_profiles') && Auth::user()->open_profile)
        <div class="rates-settings__alert rates-settings__alert--warning" role="alert">
            <span class="rates-settings__alert-icon" aria-hidden="true">
                @include('elements.icon', ['icon' => 'alert-circle-outline', 'variant' => 'small', 'centered' => true])
            </span>
            <span class="rates-settings__alert-text">{{ __("Your profile is set to 'Open profile', meaning your profile will be treated as free one") }}.</span>
            <button type="button" class="rates-settings__alert-close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form method="POST" action="{{ route('my.settings.rates.save') }}" class="rates-settings__form">
        @csrf

        <div class="rates-settings__toggle-card">
            <div class="rates-settings__toggle-row">
                <div class="rates-settings__toggle-icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'layers-outline', 'variant' => 'medium', 'centered' => true])
                </div>
                <div class="rates-settings__toggle-copy">
                    <span class="rates-settings__toggle-title">{{ __('Paid profile') }}</span>
                    <span class="rates-settings__toggle-desc">{{ __('Charge subscribers to access your profile and content.') }}</span>
                </div>
                <label class="rates-settings__toggle" for="paid-profile">
                    <input type="checkbox"
                           class="rates-settings__toggle-input custom-control-input"
                           id="paid-profile"
                           name="paid-profile"
                        {{ isset(Auth::user()->paid_profile) ? (Auth::user()->paid_profile == '1' ? 'checked' : '') : false }}>
                    <span class="rates-settings__toggle-track" aria-hidden="true"></span>
                </label>
            </div>
        </div>

        <div class="paid-profile-rates {{ isset(Auth::user()->paid_profile) ? (Auth::user()->paid_profile == '1' ? '' : 'd-none') : '' }}">
            <div class="rates-settings__card">
                <div class="rates-settings__field">
                    <label for="profile_access_price">{{ __('Monthly subscription price') }}</label>
                    <div class="rates-settings__input-wrap">
                        <span class="rates-settings__input-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'cash-outline', 'variant' => 'small', 'centered' => true])
                        </span>
                        <input class="form-control rates-settings__input {{ $errors->has('profile_access_price') ? 'is-invalid' : '' }}"
                               id="profile_access_price"
                               name="profile_access_price"
                               type="number"
                               inputmode="decimal"
                               step="any"
                               value="{{ Auth::user()->profile_access_price }}">
                        @if($offer)
                            <span class="rates-settings__old-price">{{ __('Old') }}: {{ $offer->old_profile_access_price }}</span>
                        @endif
                    </div>
                    @if($errors->has('profile_access_price'))
                        <span class="rates-settings__error d-block" role="alert">
                            <strong>{{ __($errors->first('profile_access_price')) }}</strong>
                        </span>
                    @endif
                </div>

                <div class="rates-settings__field">
                    <label for="profile_access_price_3_months">{{ __('Monthly price for 3 months subscriptions') }}</label>
                    <div class="rates-settings__input-wrap">
                        <span class="rates-settings__input-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'cash-outline', 'variant' => 'small', 'centered' => true])
                        </span>
                        <input class="form-control rates-settings__input {{ $errors->has('profile_access_price_3_months') ? 'is-invalid' : '' }}"
                               id="profile_access_price_3_months"
                               name="profile_access_price_3_months"
                               type="number"
                               inputmode="decimal"
                               step="any"
                               value="{{ Auth::user()->profile_access_price_3_months }}">
                        @if($offer && $offer->old_profile_access_price_3_months)
                            <span class="rates-settings__old-price">{{ __('Old') }}: {{ $offer->old_profile_access_price_3_months }}</span>
                        @endif
                    </div>
                    @if($errors->has('profile_access_price_3_months'))
                        <span class="rates-settings__error d-block" role="alert">
                            <strong>{{ __($errors->first('profile_access_price_3_months')) }}</strong>
                        </span>
                    @endif
                </div>

                <div class="rates-settings__field">
                    <label for="profile_access_price_6_months">{{ __('Monthly price for 6 months subscriptions') }}</label>
                    <div class="rates-settings__input-wrap">
                        <span class="rates-settings__input-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'cash-outline', 'variant' => 'small', 'centered' => true])
                        </span>
                        <input class="form-control rates-settings__input {{ $errors->has('profile_access_price_6_months') ? 'is-invalid' : '' }}"
                               id="profile_access_price_6_months"
                               name="profile_access_price_6_months"
                               type="number"
                               inputmode="decimal"
                               step="any"
                               value="{{ Auth::user()->profile_access_price_6_months }}">
                        @if($offer && $offer->old_profile_access_price_6_months)
                            <span class="rates-settings__old-price">{{ __('Old') }}: {{ $offer->old_profile_access_price_6_months }}</span>
                        @endif
                    </div>
                    @if($errors->has('profile_access_price_6_months'))
                        <span class="rates-settings__error d-block" role="alert">
                            <strong>{{ __($errors->first('profile_access_price_6_months')) }}</strong>
                        </span>
                    @endif
                </div>

                <div class="rates-settings__field rates-settings__field--last">
                    <label for="profile_access_price_12_months">{{ __('Monthly price for yearly subscriptions') }}</label>
                    <div class="rates-settings__input-wrap">
                        <span class="rates-settings__input-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'cash-outline', 'variant' => 'small', 'centered' => true])
                        </span>
                        <input class="form-control rates-settings__input {{ $errors->has('profile_access_price_12_months') ? 'is-invalid' : '' }}"
                               id="profile_access_price_12_months"
                               name="profile_access_price_12_months"
                               type="number"
                               inputmode="decimal"
                               step="any"
                               value="{{ Auth::user()->profile_access_price_12_months }}">
                        @if($offer && $offer->old_profile_access_price_12_months)
                            <span class="rates-settings__old-price">{{ __('Old') }}: {{ $offer->old_profile_access_price_12_months }}</span>
                        @endif
                    </div>
                    @if($errors->has('profile_access_price_12_months'))
                        <span class="rates-settings__error d-block" role="alert">
                            <strong>{{ __($errors->first('profile_access_price_12_months')) }}</strong>
                        </span>
                    @endif
                </div>
            </div>

            @if(!getSetting('profiles.disable_profile_offers'))
                <div class="rates-settings__offer">
                    <div class="rates-settings__offer-header">
                        <div class="rates-settings__offer-header-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'pricetag-outline', 'variant' => 'medium', 'centered' => true])
                        </div>
                        <div class="rates-settings__offer-header-text">
                            <h3 class="rates-settings__offer-title">{{ __('Promotion') }}</h3>
                            <p class="rates-settings__offer-subtitle">{{ __('Is offer until') }}</p>
                        </div>
                    </div>

                    <div class="rates-settings__offer-control">
                        <label class="rates-settings__offer-enable" for="is_offer">
                            <input type="checkbox" name="is_offer" id="is_offer" {{ Auth::user()->offer && Auth::user()->offer->expires_at ? 'checked' : '' }}>
                            <span class="rates-settings__offer-checkbox" aria-hidden="true"></span>
                            <span class="rates-settings__offer-enable-text">{{ __('Enable limited-time pricing') }}</span>
                        </label>

                        <div class="rates-settings__date-wrap">
                            <input type="date"
                                   class="form-control rates-settings__input rates-settings__input--date {{ $errors->has('profile_access_offer_date') ? 'is-invalid' : '' }}"
                                   id="profile_access_offer_date"
                                   name="profile_access_offer_date"
                                   value="{{ Auth::user()->offer && Auth::user()->offer->expires_at ? Auth::user()->offer->expires_at->format('Y-m-d') : '' }}">
                        </div>
                    </div>

                    <div class="rates-settings__hint-box">
                        <span class="rates-settings__hint-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'information-circle-outline', 'variant' => 'small', 'centered' => true])
                        </span>
                        <p class="rates-settings__hint">{{ __('In order to start a promotion, reduce your monthly prices and select a future promotion end date.') }}</p>
                    </div>

                    @if($errors->has('profile_access_offer_date'))
                        <span class="rates-settings__error d-block" role="alert">
                            <strong>{{ __($errors->first('profile_access_offer_date')) }}</strong>
                        </span>
                    @endif
                </div>
            @endif

            <button class="btn btn-primary rates-settings__submit" type="submit">
                @include('elements.icon', ['icon' => 'save-outline', 'variant' => 'small', 'centered' => true, 'classes' => 'rates-settings__submit-icon'])
                <span>{{ __('Save changes') }}</span>
            </button>
        </div>
    </form>
</div>
