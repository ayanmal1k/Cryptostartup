@extends('layouts.generic')

@section('page_title', __('Edit Video'))

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    // Derive the current tier from the stored flags.
    $currentVisibility = old('visibility', $video->is_private
        ? 'private'
        : ((float) ($video->price ?? 0) > 0 ? 'paid' : 'public'));
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/video-upload.css') }}?v=20260817a">
@endsection

@section('content')
{{-- This view did not exist: videos.edit was routed and the controller returned it, so every
     edit attempt threw "View [videos.edit] not found". It reuses the upload page's design
     system (vu-*) so editing matches the kept upload design. --}}
<div class="vu-page {{ $isDarkTheme ? 'vu-page--dark' : 'vu-page--light' }}">
<div class="vu-container">

    <header class="vu-header">
        <div>
            <h1 class="vu-header__title">{{ __('Edit Video') }}</h1>
            <p class="vu-header__sub">{{ __('Update the details, visibility and price') }}</p>
        </div>
        <a href="{{ route('videos.my') }}" class="vu-btn vu-btn--ghost">{{ __('Cancel') }}</a>
    </header>

    @if($errors->any())
        <div class="vu-alert vu-alert--danger">
            <ul style="margin:0;padding-left:18px;">
                @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('videos.update', $video) }}" method="POST" enctype="multipart/form-data" class="vu-form">
        @csrf
        @method('PUT')

        <div class="vu-main">

            {{-- Current video preview --}}
            <div class="vu-field">
                <div class="vu-label-row">
                    <span class="vu-label">{{ __('Current video') }}</span>
                </div>
                <video class="vu-current-video" controls preload="metadata"
                       @if($video->thumbnail_path) poster="{{ asset('storage/' . $video->thumbnail_path) }}" @endif>
                    <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                </video>
                <p class="vu-hint">{{ __('The video file itself cannot be replaced — upload a new video instead.') }}</p>
            </div>

            {{-- Title --}}
            <div class="vu-field">
                <div class="vu-label-row">
                    <label for="title" class="vu-label">{{ __('Title') }}<span class="vu-req">*</span></label>
                </div>
                <input type="text" class="vu-input" id="title" name="title"
                       value="{{ old('title', $video->title) }}" maxlength="191" required>
            </div>

            {{-- Description --}}
            <div class="vu-field">
                <div class="vu-label-row">
                    <label for="description" class="vu-label">{{ __('Description') }}</label>
                </div>
                <textarea class="vu-input vu-textarea" id="description" name="description"
                          rows="4" maxlength="1000">{{ old('description', $video->description) }}</textarea>
            </div>

            {{-- Thumbnail --}}
            <div class="vu-field">
                <div class="vu-label-row">
                    <label for="thumbnail" class="vu-label">{{ __('Thumbnail') }} <span class="vu-count">({{ __('Optional') }})</span></label>
                </div>
                @if($video->thumbnail_path)
                    <img src="{{ asset('storage/' . $video->thumbnail_path) }}" alt="" class="vu-current-thumb">
                @endif
                <input type="file" class="vu-input" id="thumbnail" name="thumbnail"
                       accept="image/jpeg,image/png,image/jpg,image/gif">
                <p class="vu-hint">{{ __('JPEG, PNG, GIF (Max 5MB). Leave empty to keep the current one.') }}</p>
            </div>

            {{-- Visibility --}}
            <div class="vu-field">
                <div class="vu-label-row">
                    <span class="vu-label">{{ __('Who can watch this') }}</span>
                </div>
                <div class="vu-visibility">
                    @foreach([
                        'public'  => ['label' => __('Public'),  'hint' => __('Anyone can watch'),         'icon' => 'M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20'],
                        'paid'    => ['label' => __('Paid'),    'hint' => __('Unlock for a one-off fee'), 'icon' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
                        'private' => ['label' => __('Private'), 'hint' => __('Only you can watch'),       'icon' => 'M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2zM7 11V7a5 5 0 0 1 10 0v4'],
                    ] as $value => $opt)
                        <label class="vu-visibility__opt">
                            <input type="radio" name="visibility" value="{{ $value }}"
                                   {{ $currentVisibility === $value ? 'checked' : '' }}>
                            <span class="vu-visibility__body">
                                <span class="vu-visibility__icon" aria-hidden="true">
                                    <svg class="vu-ic" viewBox="0 0 24 24"><path d="{{ $opt['icon'] }}"/></svg>
                                </span>
                                <span class="vu-visibility__text">
                                    <span class="vu-visibility__label">{{ $opt['label'] }}</span>
                                    <span class="vu-visibility__hint">{{ $opt['hint'] }}</span>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Price --}}
            <div class="vu-field {{ $currentVisibility === 'paid' ? '' : 'd-none' }}" id="priceField">
                <div class="vu-label-row">
                    <label for="price" class="vu-label">{{ __('Price') }}<span class="vu-req">*</span></label>
                    <span class="vu-count">{{ __('One-off unlock') }}</span>
                </div>
                <input type="number" class="vu-input" id="price" name="price"
                       step="0.01" min="0.5" max="500"
                       value="{{ old('price', (float) ($video->price ?? 0) > 0 ? $video->price : '') }}"
                       placeholder="4.99">
                <p class="vu-hint">{{ __('Fans pay this once to unlock the video. Minimum 0.50.') }}</p>
            </div>

            <div class="vu-actions">
                <button type="submit" class="vu-btn vu-btn--primary">{{ __('Save changes') }}</button>
                <a href="{{ route('videos.my') }}" class="vu-btn vu-btn--ghost">{{ __('Cancel') }}</a>
            </div>
        </div>
    </form>

</div>
</div>

<script>
(function () {
    var priceField = document.getElementById('priceField');
    if (!priceField) return;
    var priceInput = document.getElementById('price');

    document.querySelectorAll('input[name="visibility"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var paid = radio.value === 'paid' && radio.checked;
            priceField.classList.toggle('d-none', !paid);
            if (!paid && priceInput) priceInput.value = '';
        });
    });
})();
</script>
@endsection
