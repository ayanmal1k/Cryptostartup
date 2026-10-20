@if(!Auth::user()->email_verified_at) @include('elements.resend-verification-email-box') @endif

@if(getSetting('ai.open_ai_enabled'))
    @include('elements.suggest-description')
@endif

@php
    $me = Auth::user();

    /**
     * Profile completeness. Gives the page a goal instead of being a flat form, and shows
     * the user exactly what is still missing.
     */
    $checks = [
        __('Avatar')      => !empty($me->avatar) && !str_contains((string) $me->avatar, 'default'),
        __('Cover photo') => !empty($me->cover) && !str_contains((string) $me->cover, 'default'),
        __('Full name')   => !empty($me->name),
        __('Bio')         => !empty($me->bio),
        __('Location')    => !empty($me->location) || !empty($me->country_id),
        __('Website')     => !empty($me->website),
    ];
    $doneCount = count(array_filter($checks));
    $totalCount = count($checks);
    $completion = $totalCount ? (int) round($doneCount / $totalCount * 100) : 0;
    $missing = array_keys(array_filter($checks, fn ($v) => !$v));
@endphp

<div class="profile-settings ps">
    <form method="POST" action="{{ route('my.settings.profile.save', ['type' => 'profile']) }}" class="profile-settings__form">
        @csrf
        @include('elements.dropzone-dummy-element')

        {{-- Completeness ------------------------------------------------------------ --}}
        <div class="ps-card ps-complete">
            <div class="ps-complete__head">
                <div>
                    <h2 class="ps-complete__title">{{ __('Profile strength') }}</h2>
                    <p class="ps-complete__sub">
                        @if($completion === 100)
                            {{ __('Your profile is complete. Nice.') }}
                        @else
                            {{ __('Still missing:') }} {{ implode(', ', $missing) }}
                        @endif
                    </p>
                </div>
                <span class="ps-complete__pct">{{ $completion }}%</span>
            </div>
            <div class="ps-complete__track" role="progressbar"
                 aria-valuenow="{{ $completion }}" aria-valuemin="0" aria-valuemax="100">
                <div class="ps-complete__fill" style="width: {{ $completion }}%"></div>
            </div>
        </div>

        {{-- Photos -------------------------------------------------------------------
             The .profile-cover-bg / .avatar-holder / .actions-holder / .upload-button
             hooks are required by public/js/pages/settings/profile.js (Dropzone binds
             `clickable` to `<container> .upload-button`) — keep them on any restyle. --}}
        <div class="ps-card">
            <div class="ps-card__head">
                <h2 class="ps-card__title">{{ __('Photos') }}</h2>
                <p class="ps-card__sub">{{ __('Your cover and avatar are the first thing fans see.') }}</p>
            </div>

            <div class="profile-settings__media ps-media">
                <div class="profile-settings__cover-wrap ps-cover-wrap">
                    <div class="card profile-cover-bg profile-settings__cover ps-cover">
                        <img class="card-img-top centered-and-cropped profile-settings__cover-img ps-cover__img" src="{{ $me->cover }}" alt="">
                        <div class="card-img-overlay profile-settings__cover-overlay d-flex justify-content-center align-items-center">
                            <div class="actions-holder profile-settings__actions d-none">
                                <div class="d-flex">
                                    <span class="h-pill h-pill-accent pointer-cursor mr-1 upload-button profile-settings__action-btn" data-toggle="tooltip" data-placement="top" title="{{ __('Upload cover image') }}">
                                        @include('elements.icon', ['icon' => 'image', 'variant' => 'medium'])
                                    </span>
                                    <span class="h-pill h-pill-accent pointer-cursor profile-settings__action-btn" onclick="ProfileSettings.removeUserAsset('cover')" data-toggle="tooltip" data-placement="top" title="{{ __('Remove cover image') }}">
                                        @include('elements.icon', ['icon' => 'close', 'variant' => 'medium'])
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Always-visible controls. The overlay above only appears on hover,
                             which is unusable on touch. These carry .upload-button so Dropzone
                             treats them as triggers too. --}}
                        <div class="ps-media__controls">
                            <button type="button" class="ps-media__btn upload-button">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                {{ __('Change cover') }}
                            </button>
                            <button type="button" class="ps-media__btn ps-media__btn--danger" onclick="ProfileSettings.removeUserAsset('cover')">
                                {{ __('Remove') }}
                            </button>
                        </div>
                    </div>
                </div>

                <div class="profile-settings__avatar-wrap ps-avatar-wrap">
                    <div class="card avatar-holder profile-settings__avatar ps-avatar">
                        <img class="card-img-top profile-settings__avatar-img ps-avatar__img" src="{{ $me->avatar }}" alt="">
                        <div class="card-img-overlay profile-settings__avatar-overlay d-flex justify-content-center align-items-center">
                            <div class="actions-holder profile-settings__actions d-none">
                                <div class="d-flex">
                                    <span class="h-pill h-pill-accent pointer-cursor mr-1 upload-button profile-settings__action-btn" data-toggle="tooltip" data-placement="top" title="{{ __('Upload avatar') }}">
                                        @include('elements.icon', ['icon' => 'image', 'variant' => 'medium'])
                                    </span>
                                    <span class="h-pill h-pill-accent pointer-cursor profile-settings__action-btn" onclick="ProfileSettings.removeUserAsset('avatar')" data-toggle="tooltip" data-placement="top" title="{{ __('Remove avatar') }}">
                                        @include('elements.icon', ['icon' => 'close', 'variant' => 'medium'])
                                    </span>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="ps-avatar__btn upload-button" aria-label="{{ __('Change avatar') }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if(session('success'))
            <div class="alert alert-success profile-settings__alert text-white font-weight-bold" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        {{-- Identity ------------------------------------------------------------------ --}}
        <div class="ps-card">
            <div class="ps-card__head">
                <h2 class="ps-card__title">{{ __('Identity') }}</h2>
                <p class="ps-card__sub">{{ __('How you appear across the platform.') }}</p>
            </div>

            <div class="profile-settings__fields">
                <div class="form-group profile-settings__field">
                    <div class="profile-settings__label-row">
                        <label for="username">{{ __('Username') }}</label>
                        <span class="ps-count" id="username_count">{{ strlen((string) $me->username) }}/255</span>
                    </div>
                    <input class="form-control {{ $errors->has('username') ? 'is-invalid' : '' }}" id="username" name="username" value="{{ $me->username }}" maxlength="255">
                    <p class="ps-hint">{{ __('Your profile link:') }}
                        <span class="ps-hint__url">{{ rtrim(url('/'), '/') }}/<span id="username_preview">{{ $me->username }}</span></span>
                    </p>
                    @if($errors->has('username'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('username') }}</strong></span>
                    @endif
                </div>

                <div class="form-group profile-settings__field">
                    <div class="profile-settings__label-row">
                        <label for="name">{{ __('Full name') }}</label>
                        <span class="ps-count" id="name_count">{{ strlen((string) $me->name) }}/255</span>
                    </div>
                    <input class="form-control {{ $errors->has('name') ? 'is-invalid' : '' }}" id="name" name="name" value="{{ $me->name }}" maxlength="255">
                    @if($errors->has('name'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('name') }}</strong></span>
                    @endif
                </div>

                <div class="profile-settings__row">
                    <div class="profile-settings__row-col {{ getSetting('profiles.allow_gender_pronouns') ? 'profile-settings__row-col--half' : 'profile-settings__row-col--full' }}">
                        <div class="form-group profile-settings__field">
                            <label for="gender">{{ __('Gender') }}</label>
                            <select class="form-control" id="gender" name="gender">
                                <option value="" disabled {{ $me->gender_id ? '' : 'selected' }}>{{ __('Select Gender') }}</option>
                                @foreach($genders as $gender)
                                    <option value="{{ $gender->id }}" {{ $me->gender_id == $gender->id ? 'selected' : '' }}>{{ __($gender->gender_name) }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('gender'))
                                <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('gender') }}</strong></span>
                            @endif
                        </div>
                    </div>

                    @if(getSetting('profiles.allow_gender_pronouns'))
                        <div class="profile-settings__row-col profile-settings__row-col--half">
                            <div class="form-group profile-settings__field">
                                <label for="pronoun">{{ __('Gender pronoun') }}</label>
                                <input class="form-control {{ $errors->has('pronoun') ? 'is-invalid' : '' }}" id="pronoun" name="pronoun" value="{{ $me->gender_pronoun }}">
                                @if($errors->has('pronoun'))
                                    <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('pronoun') }}</strong></span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <div class="form-group profile-settings__field">
                    <label for="birthdate">{{ __('Birthdate') }}</label>
                    <div class="profile-settings__date-wrap">
                        <input type="date" class="form-control {{ $errors->has('birthdate') ? 'is-invalid' : '' }}" id="birthdate" name="birthdate" value="{{ $me->birthdate }}" max="{{ $minBirthDate }}">
                    </div>
                    <p class="ps-hint">{{ __('Used to confirm you are old enough to use the platform. Never shown publicly.') }}</p>
                    @if($errors->has('birthdate'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('birthdate') }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        {{-- About --------------------------------------------------------------------- --}}
        <div class="ps-card">
            <div class="ps-card__head">
                <h2 class="ps-card__title">{{ __('About you') }}</h2>
                <p class="ps-card__sub">{{ __('A good bio is the difference between a visit and a subscription.') }}</p>
            </div>

            <div class="profile-settings__fields">
                <div class="form-group profile-settings__field">
                    <div class="profile-settings__label-row">
                        <label for="bio">{{ __('Bio') }}</label>
                        <span class="ps-label-row__right">
                            @if(getSetting('ai.open_ai_enabled'))
                                <a href="javascript:void(0)" class="profile-settings__suggest-link" onclick="{{ 'AiSuggestions.suggestDescriptionDialog();' }}" data-toggle="tooltip" data-placement="left" title="{{ __('Use AI to generate your description.') }}">{{ trans_choice('Suggestion', 2) }}</a>
                            @endif
                            <span class="ps-count" id="bio_count">{{ strlen((string) $me->bio) }} {{ __('characters') }}</span>
                        </span>
                    </div>
                    <textarea class="form-control {{ $errors->has('bio') ? 'is-invalid' : '' }}" id="bio" name="bio" rows="4" spellcheck="false">{{ $me->bio }}</textarea>
                    @if($errors->has('bio'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('bio') }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Location & links ---------------------------------------------------------- --}}
        <div class="ps-card">
            <div class="ps-card__head">
                <h2 class="ps-card__title">{{ __('Location & links') }}</h2>
                <p class="ps-card__sub">{{ __('Help fans find and follow you elsewhere.') }}</p>
            </div>

            <div class="profile-settings__fields">
                <div class="profile-settings__row">
                    <div class="profile-settings__row-col profile-settings__row-col--half">
                        <div class="form-group profile-settings__field">
                            <label for="country">{{ __('Country') }}</label>
                            <select class="form-control" id="country" name="country">
                                <option value="" disabled {{ $me->country_id ? '' : 'selected' }}>{{ __('Select country') }}</option>
                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ $me->country_id == $country->id ? 'selected' : '' }}>{{ __($country->name) }}</option>
                                @endforeach
                            </select>
                            @if($errors->has('country'))
                                <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('country') }}</strong></span>
                            @endif
                        </div>
                    </div>
                    <div class="profile-settings__row-col profile-settings__row-col--half">
                        <div class="form-group profile-settings__field">
                            <label for="location">{{ __('Location') }}</label>
                            <input class="form-control {{ $errors->has('location') ? 'is-invalid' : '' }}" id="location" name="location" value="{{ $me->location }}" placeholder="{{ __('e.g. Los Angeles') }}">
                            @if($errors->has('location'))
                                <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('location') }}</strong></span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="form-group profile-settings__field">
                    <label for="website">{{ __('Website URL') }}</label>
                    <input type="url" class="form-control {{ $errors->has('website') ? 'is-invalid' : '' }}" id="website" name="website" value="{{ $me->website }}" placeholder="https://">
                    @if($errors->has('website'))
                        <span class="invalid-feedback" role="alert"><strong>{{ $errors->first('website') }}</strong></span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Save bar sticks to the bottom so it is reachable from any section --}}
        <div class="ps-savebar">
            <a href="{{ route('profile', ['username' => $me->username]) }}" class="ps-savebar__view" target="_blank" rel="noopener">
                {{ __('View my profile') }}
            </a>
            <button class="btn btn-primary profile-settings__submit ps-savebar__submit" type="submit">{{ __('Save changes') }}</button>
        </div>
    </form>
</div>

<script>
(function () {
    // Live character counters + username → profile-URL preview.
    function bindCount(inputId, countId, max) {
        var input = document.getElementById(inputId);
        var out = document.getElementById(countId);
        if (!input || !out) return;
        var update = function () { out.textContent = input.value.length + '/' + max; };
        input.addEventListener('input', update);
        update();
    }

    // Caps mirror the users table (varchar 255). bio is a TEXT column with no
    // server-side limit, so it shows a plain count rather than a false maximum.
    bindCount('username', 'username_count', 255);
    bindCount('name', 'name_count', 255);

    var bio = document.getElementById('bio');
    var bioOut = document.getElementById('bio_count');
    if (bio && bioOut) {
        var label = @json(__('characters'));
        var updateBio = function () { bioOut.textContent = bio.value.length + ' ' + label; };
        bio.addEventListener('input', updateBio);
        updateBio();
    }

    var username = document.getElementById('username');
    var preview = document.getElementById('username_preview');
    if (username && preview) {
        username.addEventListener('input', function () {
            preview.textContent = username.value || '…';
        });
    }
})();
</script>
