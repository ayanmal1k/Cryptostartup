<div class="verify-settings">
    @if(session('success'))
        <div class="verify-settings__alert verify-alert verify-alert--success" role="alert">
            <div class="verify-settings__alert-body">
                <span class="verify-settings__alert-icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'small', 'centered' => true])
                </span>
                <div class="verify-settings__alert-copy">
                    <strong class="verify-settings__alert-title">{{ session('success') }}</strong>
                    <div class="verify-settings__alert-details">
                        <p class="verify-settings__alert-lead">{{ __('What happens next?') }}</p>
                        <ul class="verify-settings__alert-list">
                            <li>{{ __('Your verification request has been submitted successfully') }}</li>
                            <li>{{ __('Our admin team will review your ID documents') }}</li>
                            <li>{{ __('Processing typically takes 24-48 hours during business days') }}</li>
                            <li>{{ __("You'll receive an email notification once your verification is processed") }}</li>
                        </ul>
                        <p class="verify-settings__alert-note">{{ __('Check your status: You can check your verification status on this page at any time.') }}</p>
                        <p class="verify-settings__alert-note">{{ __("When can you post? Once your verification is approved, you'll be able to publish posts immediately!") }}</p>
                    </div>
                </div>
            </div>
            <button type="button" class="verify-settings__alert-close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="verify-settings__alert verify-alert verify-alert--warning" role="alert">
            <span class="verify-settings__alert-text">{{ session('error') }}</span>
            <button type="button" class="verify-settings__alert-close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(Auth::user()->verification && (Auth::user()->verification->rejectionReason && Auth::user()->verification->status === 'rejected'))
        <div class="verify-settings__alert verify-alert verify-alert--warning" role="alert">
            <span class="verify-settings__alert-text">{{ __('Your previous verification attempt was rejected for the following reason:') }} "{{ Auth::user()->verification->rejectionReason }}"</span>
            <button type="button" class="verify-settings__alert-close" data-dismiss="alert" aria-label="{{ __('Close') }}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <form class="verify-form verify-settings__form" action="{{ route('my.settings.verify.save') }}" method="POST">
        @csrf

        <div class="verify-settings__intro">
            <p class="verify-settings__intro-text">{{ __('In order to get verified and receive your badge, please take care of the following steps:') }}</p>
        </div>

        <div class="verify-steps">
            <div class="verify-step">
                @if(Auth::user()->email_verified_at)
                    @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--done'])
                @else
                    @include('elements.icon', ['icon' => 'close-circle-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--pending'])
                @endif
                <span class="verify-step__label">{{ __('Confirm your email address.') }}</span>
            </div>
            <div class="verify-step">
                @if(Auth::user()->birthdate)
                    @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--done'])
                @else
                    @include('elements.icon', ['icon' => 'close-circle-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--pending'])
                @endif
                <span class="verify-step__label">{{ __('Set your birthdate.') }}</span>
            </div>
            <div class="verify-step">
                @if((Auth::user()->verification && Auth::user()->verification->status == 'verified'))
                    @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--done'])
                    <span class="verify-step__label">{{ __('Upload a Goverment issued ID card.') }}</span>
                @else
                    @if(!Auth::user()->verification || (Auth::user()->verification && Auth::user()->verification->status !== 'verified' && Auth::user()->verification->status !== 'pending'))
                        @include('elements.icon', ['icon' => 'close-circle-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--pending'])
                        <span class="verify-step__label">{{ __('Upload a Goverment issued ID card.') }}</span>
                    @else
                        @include('elements.icon', ['icon' => 'time-outline', 'variant' => 'medium', 'classes' => 'verify-step__icon verify-step__icon--progress'])
                        <span class="verify-step__label">{{ __('Identity check in progress.') }}</span>
                    @endif
                @endif
            </div>
        </div>

        @if((!Auth::user()->verification || (Auth::user()->verification && Auth::user()->verification->status !== 'verified' && Auth::user()->verification->status !== 'pending')))
            <div class="verify-upload-section">
                <h5 class="verify-upload-section__title">{{ __('Complete your verification') }}</h5>
                <p class="verify-upload-section__desc">{{ __('Please attach clear photos of your ID card back and front side.') }}</p>
                <div class="dropzone-previews dropzone w-100"></div>
                <small class="verify-upload-section__hint">{{ __('Allowed file types') }}: {{ str_replace(',', ', ', AttachmentHelper::filterExtensions('manualPayments')) }}. {{ __('Max size') }}: 4 {{ __('MB') }}.</small>
                <div class="verify-upload-section__actions">
                    <button type="submit" class="btn verify-submit-btn">{{ __('Submit') }}</button>
                </div>
            </div>
        @endif

        @if(Auth::user()->verification && Auth::user()->verification->status == 'pending')
            <div class="verify-status-card verify-status-card--pending">
                <div class="verify-status-card__title">
                    @include('elements.icon', ['icon' => 'time-outline', 'variant' => 'small', 'centered' => true])
                    <span>{{ __('Verification Status: Pending Review') }}</span>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="verify-status-card__info">
                            <span class="verify-status-card__info-label">{{ __('Submitted On') }}</span>
                            <span class="verify-status-card__info-value">{{ Auth::user()->verification->created_at->format('F d, Y \a\t g:i A') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="verify-status-card__info">
                            <span class="verify-status-card__info-label">{{ __('Estimated Processing Time') }}</span>
                            <span class="verify-status-card__info-value">{{ __('24-48 hours (business days)') }}</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="verify-status-card__info">
                            <span class="verify-status-card__info-label">{{ __('Notification') }}</span>
                            <span class="verify-status-card__info-value">{{ __("You'll receive an email when your verification is processed") }}</span>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="verify-status-card__info">
                            <span class="verify-status-card__info-label">{{ __('Check Status') }}</span>
                            <span class="verify-status-card__info-value">{{ __('This page (Settings → Verification)') }}</span>
                        </div>
                    </div>
                </div>
                <div class="verify-status-card__note">
                    <strong>{{ __('When can you post?') }}</strong>
                    {{ __(" Once your verification is approved, you'll be able to publish posts immediately. You can check back here anytime to see your verification status.") }}
                </div>
            </div>
        @endif

        @if(Auth::user()->email_verified_at && Auth::user()->birthdate && (Auth::user()->verification && Auth::user()->verification->status == 'verified'))
            <div class="verify-status-card verify-status-card--success">
                <span class="verify-success-icon" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'variant' => 'large', 'centered' => true])
                </span>
                <h4 class="verify-status-card__success-title">{{ __('Verification Complete!') }}</h4>
                <p class="verify-status-card__success-text">{{ __("Your info looks good, you're all set to post new content!") }}</p>
                <p class="verify-status-card__success-action">{{ __('You can now publish posts!') }}</p>
                @if(Auth::user()->verification->updated_at)
                    <small class="verify-status-card__success-date">{{ __('Verified on:') }} {{ Auth::user()->verification->updated_at->format('F d, Y') }}</small>
                @endif
            </div>
        @endif
    </form>
</div>

@include('elements.uploaded-file-preview-template')
