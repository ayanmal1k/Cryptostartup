@extends('layouts.generic')

@section('page_title')
    {{ __('Upload Video') }}
@endsection

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/video-upload.css') }}?v=20260817a">
@endsection

@section('content')
<div class="vu-page {{ $isDarkTheme ? 'vu-page--dark' : 'vu-page--light' }}">
<div class="vu-container">

    <header class="vu-header">
        <div class="vu-header__text">
            <h1 class="vu-header__title">{{ __('Upload Video') }}</h1>
            <p class="vu-header__sub">{{ __('Share a new video with your audience') }}</p>
        </div>
        <a href="{{ route('videos.reels') }}" class="vu-back">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M19 12H5M12 19l-7-7 7-7" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            {{ __('Cancel') }}
        </a>
    </header>

    {{-- Posting warnings --}}
    @if(isset($postingWarnings) && count($postingWarnings) > 0)
        <div class="vu-alert vu-alert--warn">
            <div class="vu-alert__icon"><svg class="vu-ic" viewBox="0 0 24 24"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
            <div class="vu-alert__body">
                <h3>{{ __('Action Required') }}</h3>
                <p>{{ __('To upload videos, please complete the following:') }}</p>
                <ul>
                    @foreach($postingWarnings as $warning)
                        <li>{{ $warning }}</li>
                    @endforeach
                </ul>
                <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="vu-btn vu-btn--warn">
                    <svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>{{ __('Complete Verification') }}
                </a>
            </div>
        </div>
    @endif

    @if ($errors->any())
        <div class="vu-alert vu-alert--danger">
            <div class="vu-alert__icon"><svg class="vu-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
            <div class="vu-alert__body">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="vu-alert vu-alert--ok">
            <div class="vu-alert__icon"><svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
            <div class="vu-alert__body"><p>{{ session('success') }}</p></div>
        </div>
    @endif

    @if (session('error'))
        <div class="vu-alert vu-alert--danger">
            <div class="vu-alert__icon"><svg class="vu-ic" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg></div>
            <div class="vu-alert__body"><p>{{ session('error') }}</p></div>
        </div>
    @endif

    <div class="vu-grid">
        <form class="vu-main" method="POST" action="{{ route('videos.store') }}" enctype="multipart/form-data" id="videoUploadForm">
            @csrf

            <div class="vu-card">
                {{-- Video File --}}
                <div class="vu-field">
                    <div class="vu-label-row">
                        <span class="vu-label">{{ __('Video File') }}<span class="vu-req">*</span></span>
                    </div>
                    <div class="vu-dropzone" id="videoDropZone">
                        <input type="file" class="d-none @error('video') is-invalid @enderror"
                               id="video" name="video" accept="video/mp4,video/mov,video/webm,video/avi" required>
                        <div class="vu-dropzone__placeholder" id="videoPlaceholder">
                            <div class="vu-dropzone__icon"><svg class="vu-ic" viewBox="0 0 24 24"><path d="M16 16l-4-4-4 4"/><path d="M12 12v9"/><path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/></svg></div>
                            <h5 class="vu-dropzone__title">{{ __('Drag & drop your video here') }}</h5>
                            <p class="vu-dropzone__hint">{{ __('or click to browse') }}</p>
                            <button type="button" class="vu-choose" onclick="document.getElementById('video').click()">
                                <svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>{{ __('Choose File') }}
                            </button>
                            <p class="vu-dropzone__formats">{{ __('Supported formats') }}: <b>MP4, MOV, WebM, AVI</b> ({{ __('Max 20MB') }})</p>
                        </div>
                        <div class="vu-dropzone__preview d-none" id="videoPreview">
                            <video id="videoPreviewPlayer" controls></video>
                            <div class="vu-preview-bar">
                                <span class="vu-preview-name" id="videoFileName"></span>
                                <button type="button" class="vu-remove" onclick="clearVideoPreview()">
                                    <svg class="vu-ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>{{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @error('video')
                        <span class="vu-error-text">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Title --}}
                <div class="vu-field">
                    <div class="vu-label-row">
                        <label for="title" class="vu-label">{{ __('Title') }}<span class="vu-req">*</span></label>
                        <span class="vu-count"><span id="titleCharCount">0</span>/191</span>
                    </div>
                    <input type="text" class="vu-input @error('title') is-invalid @enderror"
                           id="title" name="title" value="{{ old('title') }}"
                           placeholder="{{ __('Enter a catchy title for your video') }}" required maxlength="191">
                    @error('title')
                        <span class="vu-error-text">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Description --}}
                <div class="vu-field">
                    <div class="vu-label-row">
                        <label for="description" class="vu-label">{{ __('Description') }}</label>
                        <span class="vu-count"><span id="descCharCount">0</span>/1000</span>
                    </div>
                    <textarea class="vu-textarea @error('description') is-invalid @enderror"
                              id="description" name="description" rows="4"
                              placeholder="{{ __('Tell viewers what your video is about...') }}" maxlength="1000">{{ old('description') }}</textarea>
                    @error('description')
                        <span class="vu-error-text">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Thumbnail --}}
                <div class="vu-field">
                    <div class="vu-label-row">
                        <label for="thumbnail" class="vu-label">{{ __('Thumbnail') }} <span class="vu-count">({{ __('Optional') }})</span></label>
                    </div>
                    <div class="vu-dropzone vu-dropzone--thumb" id="thumbnailDropZone">
                        <input type="file" class="d-none @error('thumbnail') is-invalid @enderror"
                               id="thumbnail" name="thumbnail" accept="image/jpeg,image/png,image/jpg,image/gif">
                        <div class="vu-dropzone__placeholder" id="thumbnailPlaceholder">
                            <div class="vu-dropzone__icon"><svg class="vu-ic" viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg></div>
                            <h5 class="vu-dropzone__title">{{ __('Upload a custom thumbnail') }}</h5>
                            <button type="button" class="vu-choose" onclick="document.getElementById('thumbnail').click()">
                                <svg class="vu-ic" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>{{ __('Upload Image') }}
                            </button>
                            <p class="vu-dropzone__formats">{{ __('JPEG, PNG, GIF (Max 5MB)') }} — {{ __('Recommended: 1280x720') }}</p>
                        </div>
                        <div class="vu-dropzone__preview d-none" id="thumbnailPreview">
                            <img id="thumbnailPreviewImg" alt="thumbnail preview">
                            <div class="vu-preview-bar">
                                <span class="vu-preview-name" id="thumbnailFileName"></span>
                                <button type="button" class="vu-remove" onclick="clearThumbnailPreview()">
                                    <svg class="vu-ic" viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>{{ __('Remove') }}
                                </button>
                            </div>
                        </div>
                    </div>
                    @error('thumbnail')
                        <span class="vu-error-text">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Visibility + price. Ported here from the retired create-post design so
                     the kept upload design carries the same controls. --}}
                <div class="vu-field">
                    <div class="vu-label-row">
                        <span class="vu-label">{{ __('Who can watch this') }}</span>
                    </div>
                    <div class="vu-visibility">
                        @foreach([
                            'public'  => ['label' => __('Public'),  'hint' => __('Anyone can watch'),        'icon' => 'M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20'],
                            'paid'    => ['label' => __('Paid'),    'hint' => __('Unlock for a one-off fee'), 'icon' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
                            'private' => ['label' => __('Private'), 'hint' => __('Only you can watch'),      'icon' => 'M19 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2zM7 11V7a5 5 0 0 1 10 0v4'],
                        ] as $value => $opt)
                            <label class="vu-visibility__opt">
                                <input type="radio" name="visibility" value="{{ $value }}"
                                       {{ old('visibility', 'public') === $value ? 'checked' : '' }}>
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
                    @error('visibility')<span class="vu-error-text">{{ $message }}</span>@enderror
                </div>

                {{-- Price: only meaningful for the "paid" option, so it is revealed by JS. --}}
                <div class="vu-field {{ old('visibility') === 'paid' ? '' : 'd-none' }}" id="priceField">
                    <div class="vu-label-row">
                        <label for="price" class="vu-label">{{ __('Price') }}<span class="vu-req">*</span></label>
                        <span class="vu-count">{{ __('One-off unlock') }}</span>
                    </div>
                    <input type="number" class="vu-input @error('price') is-invalid @enderror"
                           id="price" name="price" step="0.01" min="0.5" max="500"
                           value="{{ old('price') }}" placeholder="4.99">
                    <p class="vu-hint">{{ __('Fans pay this once to unlock the video. Minimum 0.50.') }}</p>
                    @error('price')<span class="vu-error-text">{{ $message }}</span>@enderror
                </div>

                {{-- Upload Progress --}}
                <div class="vu-field d-none" id="uploadProgress">
                    <div class="vu-label-row">
                        <span class="vu-label">{{ __('Upload Progress') }}</span>
                        <span class="vu-count" id="progressText">0%</span>
                    </div>
                    <div class="vu-progress-track">
                        <div class="vu-progress-fill" id="progressBar" role="progressbar"></div>
                    </div>
                    <p class="vu-progress-status" id="uploadStatus">{{ __('Preparing upload...') }}</p>
                </div>

                {{-- Actions --}}
                <div class="vu-actions">
                    <button type="submit" class="vu-btn vu-btn--primary" id="submitBtn" @if(isset($canPost) && !$canPost) disabled @endif>
                        <svg class="vu-ic" viewBox="0 0 24 24"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>{{ __('Upload Video') }}
                    </button>
                    <a href="{{ route('videos.reels') }}" class="vu-btn vu-btn--ghost">{{ __('Cancel') }}</a>
                </div>
            </div>
        </form>

        {{-- Sidebar --}}
        <aside class="vu-side">
            <div class="vu-card">
                <h2 class="vu-card__title"><svg class="vu-ic" viewBox="0 0 24 24"><line x1="9" y1="18" x2="15" y2="18"/><line x1="10" y1="22" x2="14" y2="22"/><path d="M15.09 14c.18-.98.65-1.74 1.41-2.5A4.65 4.65 0 0 0 18 8 6 6 0 0 0 6 8c0 1 .23 2.23 1.5 3.5A4.61 4.61 0 0 1 8.91 14"/></svg>{{ __('Upload Tips') }}</h2>
                <ul class="vu-tips">
                    <li><svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>{{ __('Use good lighting for better quality') }}</li>
                    <li><svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>{{ __('Keep videos under 20MB for faster uploads') }}</li>
                    <li><svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>{{ __('Use vertical format (9:16) for best viewing') }}</li>
                    <li><svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>{{ __('Write catchy titles to attract viewers') }}</li>
                    <li><svg class="vu-ic" viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>{{ __('Add a custom thumbnail to stand out') }}</li>
                </ul>
            </div>

            <div class="vu-card">
                <h2 class="vu-card__title"><svg class="vu-ic" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>{{ __('Guidelines') }}</h2>
                <p class="vu-note">{{ __('By uploading, you agree to our community guidelines:') }}</p>
                <ul class="vu-guidelines">
                    <li>{{ __('No illegal or harmful content') }}</li>
                    <li>{{ __('Respect copyright and intellectual property') }}</li>
                    <li>{{ __('No hate speech or harassment') }}</li>
                    <li>{{ __('Must be appropriate for the platform') }}</li>
                </ul>
            </div>
        </aside>
    </div>

</div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Video file handling
    var videoInput = document.getElementById('video');
    var videoDropZone = document.getElementById('videoDropZone');
    var videoPlaceholder = document.getElementById('videoPlaceholder');
    var videoPreview = document.getElementById('videoPreview');
    var videoPreviewPlayer = document.getElementById('videoPreviewPlayer');
    var videoFileName = document.getElementById('videoFileName');
    
    // Thumbnail handling
    var thumbnailInput = document.getElementById('thumbnail');
    var thumbnailDropZone = document.getElementById('thumbnailDropZone');
    var thumbnailPlaceholder = document.getElementById('thumbnailPlaceholder');
    var thumbnailPreview = document.getElementById('thumbnailPreview');
    var thumbnailPreviewImg = document.getElementById('thumbnailPreviewImg');
    var thumbnailFileName = document.getElementById('thumbnailFileName');
    
    // Character counters
    var titleInput = document.getElementById('title');
    var titleCharCount = document.getElementById('titleCharCount');
    var descriptionInput = document.getElementById('description');
    var descCharCount = document.getElementById('descCharCount');
    
    // Video drag and drop
    setupDropZone(videoDropZone, videoInput);
    setupDropZone(thumbnailDropZone, thumbnailInput);
    
    function setupDropZone(dropZone, input) {
        if (!dropZone || !input) return;
        
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, preventDefaults, false);
        });
        
        ['dragenter', 'dragover'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function() { dropZone.classList.add('dragover'); }, false);
        });
        
        ['dragleave', 'drop'].forEach(function(eventName) {
            dropZone.addEventListener(eventName, function() { dropZone.classList.remove('dragover'); }, false);
        });
        
        dropZone.addEventListener('drop', function(e) {
            var files = e.dataTransfer.files;
            if (files.length) {
                input.files = files;
                var event = new Event('change', { bubbles: true });
                input.dispatchEvent(event);
            }
        });
        
        dropZone.addEventListener('click', function(e) {
            if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'INPUT' && e.target.tagName !== 'I') {
                input.click();
            }
        });
    }
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    // Video file change handler
    if (videoInput) {
        videoInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                // Validate file size (20MB)
                if (file.size > 20 * 1024 * 1024) {
                    alert('Video file is too large. Maximum size is 20MB.');
                    this.value = '';
                    return;
                }
                
                // Show preview
                var url = URL.createObjectURL(file);
                videoPreviewPlayer.src = url;
                videoFileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                videoPlaceholder.classList.add('d-none');
                videoPreview.classList.remove('d-none');
            }
        });
    }
    
    // Thumbnail file change handler
    if (thumbnailInput) {
        thumbnailInput.addEventListener('change', function() {
            var file = this.files[0];
            if (file) {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('Thumbnail is too large. Maximum size is 5MB.');
                    this.value = '';
                    return;
                }
                
                // Show preview
                var reader = new FileReader();
                reader.onload = function(e) {
                    thumbnailPreviewImg.src = e.target.result;
                    thumbnailFileName.textContent = file.name + ' (' + formatFileSize(file.size) + ')';
                    thumbnailPlaceholder.classList.add('d-none');
                    thumbnailPreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    // Character counters
    if (titleInput) {
        titleInput.addEventListener('input', function() {
            titleCharCount.textContent = this.value.length;
        });
        titleCharCount.textContent = titleInput.value.length;
    }
    
    if (descriptionInput) {
        descriptionInput.addEventListener('input', function() {
            descCharCount.textContent = this.value.length;
        });
        descCharCount.textContent = descriptionInput.value.length;
    }
    
    // Form submission with progress
    var form = document.getElementById('videoUploadForm');
    var submitBtn = document.getElementById('submitBtn');
    var uploadProgress = document.getElementById('uploadProgress');
    var progressBar = document.getElementById('progressBar');
    var progressText = document.getElementById('progressText');
    var uploadStatus = document.getElementById('uploadStatus');
    
    if (form) {
        form.addEventListener('submit', function(e) {
            // Validate video is selected
            if (!videoInput.files || !videoInput.files[0]) {
                e.preventDefault();
                alert('Please select a video file to upload.');
                return;
            }
            
            // Validate title
            if (!titleInput.value.trim()) {
                e.preventDefault();
                alert('Please enter a title for your video.');
                titleInput.focus();
                return;
            }
            
            // Show progress and disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin mr-2"></i>Uploading...';
            uploadProgress.classList.remove('d-none');
            
            // Simulate progress (actual progress would need AJAX)
            var progress = 0;
            var interval = setInterval(function() {
                progress += Math.random() * 15;
                if (progress > 90) progress = 90;
                progressBar.style.width = progress + '%';
                progressText.textContent = Math.round(progress) + '%';
                uploadStatus.textContent = 'Uploading video...';
            }, 500);
            
            // Let form submit normally
        });
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});

// Clear video preview
function clearVideoPreview() {
    var videoInput = document.getElementById('video');
    var videoPlaceholder = document.getElementById('videoPlaceholder');
    var videoPreview = document.getElementById('videoPreview');
    var videoPreviewPlayer = document.getElementById('videoPreviewPlayer');
    
    videoInput.value = '';
    videoPreviewPlayer.src = '';
    videoPlaceholder.classList.remove('d-none');
    videoPreview.classList.add('d-none');
}

// Clear thumbnail preview
function clearThumbnailPreview() {
    var thumbnailInput = document.getElementById('thumbnail');
    var thumbnailPlaceholder = document.getElementById('thumbnailPlaceholder');
    var thumbnailPreview = document.getElementById('thumbnailPreview');
    var thumbnailPreviewImg = document.getElementById('thumbnailPreviewImg');
    
    thumbnailInput.value = '';
    thumbnailPreviewImg.src = '';
    thumbnailPlaceholder.classList.remove('d-none');
    thumbnailPreview.classList.add('d-none');
}

// Reveal the price input only when "Paid" is selected.
(function () {
    var priceField = document.getElementById('priceField');
    if (!priceField) return;
    var priceInput = document.getElementById('price');

    document.querySelectorAll('input[name="visibility"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            var paid = radio.value === 'paid' && radio.checked;
            priceField.classList.toggle('d-none', !paid);
            // Clear the amount when leaving Paid so a stale value can't be submitted.
            if (!paid && priceInput) priceInput.value = '';
            if (paid && priceInput) priceInput.focus();
        });
    });
})();
</script>
@endsection
