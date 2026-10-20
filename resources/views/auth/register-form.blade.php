<form method="POST" action="{{ route('register') }}" id="register-form" class="auth-form">
    @csrf

    @if($errors->any())
        <div class="auth-alert auth-alert--error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="register-account-type-wrap">
        <label class="register-account-type-label">
            {{ __('I want to') }} <span class="auth-label__req">*</span>
        </label>
        <div class="register-account-type-buttons">
            <label class="register-account-type-btn account-type-option selected" data-type="subscriber">
                <input type="radio" name="account_type" value="subscriber" checked style="display: none;">
                <div class="btn-radio"><span class="dot"></span></div>
                <div class="btn-text">
                    <div class="title">{{ __('Subscribe & Watch') }}</div>
                    <div class="subtitle">{{ __('Fan/Subscriber account') }}</div>
                </div>
            </label>
            <label class="register-account-type-btn account-type-option" data-type="creator">
                <input type="radio" name="account_type" value="creator" style="display: none;">
                <div class="btn-radio"><span class="dot"></span></div>
                <div class="btn-text">
                    <div class="title">{{ __('Create & Earn') }}</div>
                    <div class="subtitle">{{ __('Content Creator account') }}</div>
                </div>
            </label>
        </div>
        @error('account_type')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="name" class="auth-label">
            {{ __('Full Name') }} <span class="auth-label__req">*</span>
        </label>
        <input
            id="name"
            class="auth-input"
            type="text"
            name="name"
            value="{{ old('name') }}"
            autocomplete="name"
            autofocus
            required
            placeholder="{{ __('Enter your full name') }}"
        >
        @error('name')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="username" class="auth-label">
            {{ __('Username') }} <span class="auth-label__req">*</span>
        </label>
        <div class="auth-input-wrap">
            <input
                id="username"
                class="auth-input auth-input--with-icon"
                type="text"
                name="username"
                value="{{ old('username') }}"
                required
                autocomplete="username"
                placeholder="{{ __('Choose a username') }}"
                oninput="checkUsernameAvailability(this.value)"
            >
            <span id="username-status" class="auth-input-status"></span>
        </div>
        <p id="username-feedback" class="auth-hint"></p>
        @error('username')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="email" class="auth-label">
            {{ __('Email Address') }} <span class="auth-label__req">*</span>
        </label>
        <input
            id="email"
            class="auth-input"
            type="email"
            name="email"
            value="{{ old('email') }}"
            required
            autocomplete="email"
            placeholder="{{ __('Enter your email') }}"
        >
        @error('email')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="birthdate" class="auth-label">
            {{ __('Date of Birth') }} <span class="auth-label__req">*</span>
            <span class="auth-label__hint">({{ __('Must be 18+') }})</span>
        </label>
        <input
            id="birthdate"
            class="auth-input"
            type="date"
            name="birthdate"
            value="{{ old('birthdate') }}"
            required
            max="{{ \Carbon\Carbon::now()->subYears(18)->format('Y-m-d') }}"
            onblur="validateAge(this.value)"
        >
        <p id="age-feedback" class="auth-hint"></p>
        @error('birthdate')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="country_id" class="auth-label">
            {{ __('Country/Region') }} <span class="auth-label__req">*</span>
        </label>
        <select id="country_id" name="country_id" class="auth-select" required>
            <option value="">{{ __('Select your country') }}</option>
            @php
                try {
                    $unitedStates = \App\Model\Country::where('name', 'United States of America (the)')->first();
                    $otherCountries = \App\Model\Country::where('name', '!=', 'All')
                        ->where('name', '!=', 'United States of America (the)')
                        ->orderBy('name')
                        ->get();

                    if ($otherCountries->isEmpty() && !$unitedStates) {
                        \Artisan::call('db:seed', ['--class' => 'InsertCountries']);
                        $unitedStates = \App\Model\Country::where('name', 'United States of America (the)')->first();
                        $otherCountries = \App\Model\Country::where('name', '!=', 'All')
                            ->where('name', '!=', 'United States of America (the)')
                            ->orderBy('name')
                            ->get();
                    }

                    $countries = collect([]);
                    if ($unitedStates) {
                        $countries->push($unitedStates);
                    }
                    $countries = $countries->merge($otherCountries);
                    $defaultCountryId = $unitedStates ? $unitedStates->id : null;
                } catch (\Exception $e) {
                    $countries = collect([]);
                    $defaultCountryId = null;
                }
            @endphp
            @if($countries->isNotEmpty())
                @foreach($countries as $country)
                    <option
                        value="{{ $country->id }}"
                        {{ (old('country_id') ? old('country_id') == $country->id : ($defaultCountryId && $country->id == $defaultCountryId)) ? 'selected' : '' }}
                    >
                        {{ $country->name }}
                    </option>
                @endforeach
            @else
                <option value="">{{ __('Countries loading...') }}</option>
            @endif
        </select>
        @error('country_id')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password" class="auth-label">
            {{ __('Password') }} <span class="auth-label__req">*</span>
        </label>
        <div class="auth-input-wrap">
            <input
                id="password"
                class="auth-input auth-input--with-icon"
                type="password"
                name="password"
                required
                autocomplete="new-password"
                minlength="8"
                placeholder="{{ __('Create a password (min 8 characters)') }}"
                oninput="checkPasswordStrength(this.value)"
            >
            <button type="button" class="auth-input-toggle" onclick="togglePassword('password')" aria-label="{{ __('Toggle password visibility') }}">
                <i class="fas fa-eye" id="password-toggle-icon"></i>
            </button>
        </div>
        <div class="auth-strength">
            <div class="auth-strength__bars">
                <div id="strength-1" class="auth-strength__bar"></div>
                <div id="strength-2" class="auth-strength__bar"></div>
                <div id="strength-3" class="auth-strength__bar"></div>
                <div id="strength-4" class="auth-strength__bar"></div>
            </div>
            <p id="password-strength-text" class="auth-strength__text"></p>
        </div>
        <ul class="auth-reqs">
            <li id="req-length">{{ __('At least 8 characters') }}</li>
            <li id="req-upper">{{ __('One uppercase letter') }}</li>
            <li id="req-lower">{{ __('One lowercase letter') }}</li>
            <li id="req-number">{{ __('One number') }}</li>
            <li id="req-special">{{ __('One special character (e.g. !@#$%^&*.)') }}</li>
        </ul>
        @error('password')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="password-confirm" class="auth-label">
            {{ __('Confirm Password') }} <span class="auth-label__req">*</span>
        </label>
        <div class="auth-input-wrap">
            <input
                id="password-confirm"
                class="auth-input auth-input--with-icon"
                type="password"
                name="password_confirmation"
                required
                autocomplete="new-password"
                placeholder="{{ __('Confirm your password') }}"
                oninput="checkPasswordMatch()"
            >
            <button type="button" class="auth-input-toggle" onclick="togglePassword('password-confirm')" aria-label="{{ __('Toggle password visibility') }}">
                <i class="fas fa-eye" id="password-confirm-toggle-icon"></i>
            </button>
        </div>
        <p id="password-match-feedback" class="auth-hint"></p>
        @error('password_confirmation')
            <p class="auth-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="auth-field">
        <label for="referral_code" class="auth-label">
            {{ __('Referral Code') }}
            <span class="auth-label__hint">({{ __('Optional') }})</span>
        </label>
        <input
            id="referral_code"
            class="auth-input"
            type="text"
            name="referral_code"
            value="{{ old('referral_code', request('ref')) }}"
            autocomplete="off"
            placeholder="{{ __('Enter referral code if you have one') }}"
        >
    </div>

    <div class="auth-legal">
        <p class="auth-legal__title">
            <i class="fas fa-shield-alt" aria-hidden="true"></i>
            {{ __('Legal Agreements') }}
        </p>

        <div class="auth-legal__item">
            <input id="ageConfirm" name="age_confirm" type="checkbox" value="1" required>
            <label for="ageConfirm">
                <span class="auth-label__req">*</span> {{ __('I confirm that I am 18 years of age or older') }}
            </label>
        </div>

        <div class="auth-legal__item">
            <input id="tosAgree" name="terms" type="checkbox" value="1" required>
            <label for="tosAgree">
                <span class="auth-label__req">*</span> {{ __('I agree to the') }}
                <a href="{{ route('pages.get', ['slug' => GenericHelper::getTOSPage()->slug]) }}" target="_blank">{{ __('Terms of Service') }}</a>
            </label>
        </div>

        <div class="auth-legal__item">
            <input id="privacyAgree" name="privacy" type="checkbox" value="1" required>
            <label for="privacyAgree">
                <span class="auth-label__req">*</span> {{ __('I agree to the') }}
                <a href="{{ route('pages.get', ['slug' => 'privacy-policy']) }}" target="_blank">{{ __('Privacy Policy') }}</a>
            </label>
        </div>

        <div class="auth-legal__item">
            <input id="guidelinesAgree" name="community_guidelines" type="checkbox" value="1" required>
            <label for="guidelinesAgree">
                <span class="auth-label__req">*</span> {{ __('I agree to the') }}
                <a href="{{ route('pages.get', ['slug' => 'community-guidelines']) }}" target="_blank">{{ __('Community Guidelines') }}</a>
            </label>
        </div>

        <div class="auth-legal__item">
            <input id="dataConsent" name="data_processing_consent" type="checkbox" value="1" required>
            <label for="dataConsent">
                <span class="auth-label__req">*</span> {{ __('I consent to the processing of my personal data as described in the Privacy Policy') }}
            </label>
        </div>

        <div class="auth-legal__item auth-legal__optional">
            <input id="marketingConsent" name="marketing_consent" type="checkbox" value="1">
            <label for="marketingConsent">
                {{ __('I would like to receive promotional emails and updates (optional)') }}
            </label>
        </div>
    </div>

    <div id="creator-terms" class="auth-legal auth-legal--creator">
        <p class="auth-legal__title">
            <i class="fas fa-star" aria-hidden="true"></i>
            {{ __('Creator Agreement') }}
        </p>

        <div class="auth-legal__item">
            <input id="creatorTerms" name="creator_terms" type="checkbox" value="1">
            <label for="creatorTerms">
                <span class="auth-label__req">*</span> {{ __('I agree to the') }}
                <a href="{{ route('pages.get', ['slug' => 'creator-terms']) }}" target="_blank">{{ __('Creator Terms & Conditions') }}</a>
            </label>
        </div>

        <div class="auth-legal__item">
            <input id="contentRights" name="content_rights" type="checkbox" value="1">
            <label for="contentRights">
                <span class="auth-label__req">*</span> {{ __('I confirm that I own or have the rights to all content I will upload') }}
            </label>
        </div>

        <p class="auth-legal__note">
            <i class="fas fa-info-circle" aria-hidden="true"></i>
            {{ __('As a creator, you will need to complete identity verification (ID check) before you can start posting content and earning.') }}
        </p>
    </div>

    @if(getSetting('security.recaptcha_enabled') && !Auth::check())
        <div class="auth-captcha">
            {!! NoCaptcha::display(['data-theme' => (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme')) : Cookie::get('app_theme'))]) !!}
            @error('g-recaptcha-response')
                <p class="auth-error">{{ __('Please check the captcha field.') }}</p>
            @enderror
        </div>
    @endif

    <button type="submit" id="submit-btn" class="auth-btn auth-btn--primary">
        <span class="submit-btn__default">
            <i class="fas fa-user-plus" aria-hidden="true"></i>
            {{ __('Create Account') }}
        </span>
        <span class="submit-btn__loading" style="display:none;">
            <i class="fas fa-spinner fa-spin" aria-hidden="true"></i>
            {{ __('Creating your account...') }}
        </span>
    </button>

    <p class="auth-secure-note">
        <i class="fas fa-lock" aria-hidden="true"></i>
        {{ __('Your data is protected with industry-standard encryption') }}
    </p>
</form>

<script>
    document.querySelectorAll('.account-type-option').forEach(option => {
        option.addEventListener('click', function() {
            document.querySelectorAll('.account-type-option').forEach(opt => {
                opt.classList.remove('selected');
            });
            this.classList.add('selected');
            this.querySelector('input').checked = true;

            const creatorTerms = document.getElementById('creator-terms');
            if (this.dataset.type === 'creator') {
                creatorTerms.style.display = 'block';
                document.getElementById('creatorTerms').required = true;
                document.getElementById('contentRights').required = true;
            } else {
                creatorTerms.style.display = 'none';
                document.getElementById('creatorTerms').required = false;
                document.getElementById('contentRights').required = false;
            }
        });
    });

    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '-toggle-icon');
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    function checkPasswordStrength(password) {
        const requirements = {
            length: password.length >= 8,
            upper: /[A-Z]/.test(password),
            lower: /[a-z]/.test(password),
            number: /[0-9]/.test(password),
            special: /[^A-Za-z0-9]/.test(password)
        };

        document.getElementById('req-length').style.color = requirements.length ? '#22c55e' : '#71717a';
        document.getElementById('req-upper').style.color = requirements.upper ? '#22c55e' : '#71717a';
        document.getElementById('req-lower').style.color = requirements.lower ? '#22c55e' : '#71717a';
        document.getElementById('req-number').style.color = requirements.number ? '#22c55e' : '#71717a';
        document.getElementById('req-special').style.color = requirements.special ? '#22c55e' : '#71717a';

        const strength = Object.values(requirements).filter(Boolean).length;
        const colors = { 1: '#ef4444', 2: '#f97316', 3: '#eab308', 4: '#22c55e', 5: '#22c55e' };
        const texts = {
            0: '',
            1: @json(__('Very Weak')),
            2: @json(__('Weak')),
            3: @json(__('Fair')),
            4: @json(__('Strong')),
            5: @json(__('Very Strong'))
        };

        for (let i = 1; i <= 4; i++) {
            document.getElementById('strength-' + i).style.background =
                i <= Math.min(strength, 4) ? colors[strength] : 'rgba(255, 255, 255, 0.12)';
        }

        document.getElementById('password-strength-text').textContent = texts[strength] || '';
        document.getElementById('password-strength-text').style.color = colors[strength] || '#71717a';
    }

    function checkPasswordMatch() {
        const password = document.getElementById('password').value;
        const confirm = document.getElementById('password-confirm').value;
        const feedback = document.getElementById('password-match-feedback');

        if (confirm.length === 0) {
            feedback.textContent = '';
            return;
        }

        if (password === confirm) {
            feedback.textContent = '✓ ' + @json(__('Passwords match'));
            feedback.style.color = '#22c55e';
        } else {
            feedback.textContent = '✗ ' + @json(__('Passwords do not match'));
            feedback.style.color = '#ef4444';
        }
    }

    function validateAge(dateString) {
        const feedback = document.getElementById('age-feedback');
        if (!dateString) {
            feedback.textContent = '';
            return;
        }

        const birthDate = new Date(dateString);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();

        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }

        if (age >= 18) {
            feedback.textContent = '✓ ' + @json(__('Age verified')) + ' (' + age + ' ' + @json(__('years old')) + ')';
            feedback.style.color = '#22c55e';
        } else {
            feedback.textContent = '✗ ' + @json(__('You must be 18 or older to register'));
            feedback.style.color = '#ef4444';
        }
    }

    (function () {
        const form = document.getElementById('register-form');
        const submitBtn = document.getElementById('submit-btn');

        // Show loading state on a valid submit so the (sometimes slow) server
        // response never feels like a dead button, and block double submits.
        if (form && submitBtn) {
            form.addEventListener('submit', function () {
                if (!form.checkValidity()) {
                    return; // let the browser surface the native validation UI
                }
                submitBtn.disabled = true;
                submitBtn.classList.add('is-loading');
                const def = submitBtn.querySelector('.submit-btn__default');
                const load = submitBtn.querySelector('.submit-btn__loading');
                if (def) def.style.display = 'none';
                if (load) load.style.display = 'inline-flex';
            });
        }

        // If the server bounced back with validation errors, make sure the user
        // actually sees them instead of staring at the button.
        const alertBox = document.querySelector('.auth-alert--error');
        if (alertBox) {
            alertBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    })();

    let usernameTimeout;
    function checkUsernameAvailability(username) {
        clearTimeout(usernameTimeout);
        const status = document.getElementById('username-status');
        const feedback = document.getElementById('username-feedback');

        if (username.length < 3) {
            status.textContent = '';
            feedback.textContent = username.length > 0 ? @json(__('Username must be at least 3 characters')) : '';
            feedback.style.color = '#71717a';
            return;
        }

        status.innerHTML = '<i class="fas fa-spinner fa-spin" style="color: #71717a;"></i>';
        feedback.textContent = @json(__('Checking availability...'));

        usernameTimeout = setTimeout(() => {
            fetch('/api/check-username?username=' + encodeURIComponent(username))
                .then(response => response.json())
                .then(data => {
                    if (data.available) {
                        status.innerHTML = '<i class="fas fa-check-circle" style="color: #22c55e;"></i>';
                        feedback.textContent = @json(__('Username is available'));
                        feedback.style.color = '#22c55e';
                    } else {
                        status.innerHTML = '<i class="fas fa-times-circle" style="color: #ef4444;"></i>';
                        feedback.textContent = @json(__('Username is already taken'));
                        feedback.style.color = '#ef4444';
                    }
                })
                .catch(() => {
                    status.textContent = '';
                    feedback.textContent = '';
                });
        }, 500);
    }
</script>
