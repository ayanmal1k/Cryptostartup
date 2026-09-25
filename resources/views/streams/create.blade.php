@extends('layouts.generic')

@section('page_title', __('Go live'))

@section('styles')
    @include('streams.partials.head')
@stop

@section('content')
<div class="dx">
    @include('streams.partials.subnav', ['active' => null])

    <main class="dx-main">
        <section>
            <div class="dx-section__head">
                <div>
                    <h1 class="dx-section__title">{{ __('Set up your stream') }}</h1>
                    <p class="dx-section__sub">{{ __('Next you\'ll land in your studio, where you check your camera and go live.') }}</p>
                </div>
            </div>

            <form action="{{ route('streams.store') }}" method="POST" enctype="multipart/form-data" class="lv-form">
                @csrf

                @if($errors->any())
                    <div class="lv-errors" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="lv-field">
                    <span class="lv-field__label" id="lv-title-label">{{ __('Title') }}</span>
                    <input type="text" name="title" class="lv-input" maxlength="120" required
                           value="{{ old('title') }}" placeholder="{{ __('What\'s happening on stream?') }}"
                           aria-labelledby="lv-title-label" autocomplete="off">
                </div>

                <div class="lv-field">
                    <span class="lv-field__label" id="lv-desc-label">{{ __('Description') }} <span class="lv-field__hint">({{ __('optional') }})</span></span>
                    <textarea name="description" class="lv-input" maxlength="1000"
                              placeholder="{{ __('Tell people what to expect') }}" aria-labelledby="lv-desc-label">{{ old('description') }}</textarea>
                </div>

                <div class="lv-field">
                    <span class="lv-field__label">{{ __('Cover') }} <span class="lv-field__hint">({{ __('optional') }})</span></span>
                    <label class="lv-cover" data-lv-cover>
                        <input type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp" aria-label="{{ __('Choose a cover image') }}">
                        <img alt="" hidden>
                        <span class="lv-cover__empty">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            {{ __('Add a cover image') }}
                            <span class="lv-field__hint">{{ __('Shown in the Live list. Without one, your profile picture is used.') }}</span>
                        </span>
                    </label>
                </div>

                <div class="lv-field">
                    <span class="lv-field__label">{{ __('Who can watch') }}</span>
                    <div class="lv-choice" role="radiogroup">
                        <label>
                            <input type="radio" name="audience" value="everyone" {{ old('audience', 'everyone') === 'everyone' ? 'checked' : '' }}>
                            <span class="lv-choice__opt">
                                <strong>{{ __('Everyone') }}</strong>
                                <span>{{ __('Anyone signed in') }}</span>
                            </span>
                        </label>
                        <label>
                            <input type="radio" name="audience" value="subscribers" {{ old('audience') === 'subscribers' ? 'checked' : '' }}>
                            <span class="lv-choice__opt">
                                <strong>{{ __('Subscribers') }}</strong>
                                <span>{{ __('Only people subscribed to you') }}</span>
                            </span>
                        </label>
                    </div>
                </div>

                @include('streams.partials.video-note')

                <button type="submit" class="dx-btn dx-btn--primary dx-btn--block" style="height: 52px">
                    {{ __('Continue to studio') }}
                </button>
            </form>
        </section>
    </main>
</div>
<script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
<script src="{{ asset('js/pages/live.js') }}?v=20260924a" defer></script>
@stop
