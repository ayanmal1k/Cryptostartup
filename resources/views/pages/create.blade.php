@extends('layouts.generic')
@section('page_title', __('New post'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/posts/post.css',
            '/libs/dropzone/dist/dropzone.css',
            '/libs/bootstrap/dist/css/bootstrap.min.css',
         ])->withFullUrl()
    !!}
@stop

@section('scripts')
    <!-- Load required dependencies first -->
    <script src="{{asset('libs/jquery/dist/jquery.min.js')}}"></script>
    <script src="{{asset('libs/popper.js/dist/umd/popper.min.js')}}"></script>
    <script src="{{asset('libs/bootstrap/dist/js/bootstrap.min.js')}}"></script>
    <script src="{{asset('js/plugins/toasts.js')}}"></script>
    <script src="{{asset('libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js')}}"></script>

    <!-- After dependencies load, then load app scripts -->
    {!!
        Minify::javascript([
            '/js/Post.js',
            '/js/posts/create-helper.js',
            '/js/suggestions.js',
            '/js/FileUpload.js',
            (Route::currentRouteName() =='posts.create' ? '/js/posts/create.js' : '/js/posts/edit.js'),
            '/js/attachments-upload.js',
         ])->withFullUrl()
    !!}
    
    <script>
        // Fix jQuery conflicts by ensuring only one version is used
        if (typeof jQuery !== 'undefined') {
            window.$ = window.jQuery;
            window.jQueryAvailable = true;
            console.log('jQuery already loaded and configured');
        } else {
            window.jQueryAvailable = false;
        }
        
        // Bootstrap modal fallback implementation
        window.modalFallback = {
            show: function(selector) {
                var modal = typeof selector === 'string' ? document.querySelector(selector) : selector;
                if (modal) {
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    document.body.classList.add('modal-open');
                    
                    // Add backdrop
                    if (!document.querySelector('.modal-backdrop')) {
                        var backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        document.body.appendChild(backdrop);
                    }
                }
            },
            hide: function(selector) {
                var modal = typeof selector === 'string' ? document.querySelector(selector) : selector;
                if (modal) {
                    modal.style.display = 'none';
                    modal.classList.remove('show');
                    document.body.classList.remove('modal-open');
                    
                    // Remove backdrop
                    var backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                }
            },
            init: function() {
                // Apply to all modals on the page
                var modals = document.querySelectorAll('.modal');
                modals.forEach(function(modal) {
                    var closeButtons = modal.querySelectorAll('[data-dismiss="modal"], .close');
                    closeButtons.forEach(function(btn) {
                        btn.addEventListener('click', function() {
                            window.modalFallback.hide(modal);
                        });
                    });
                });
            }
        };
        
        // Toast implementation fallback
        window.toastFallback = {
            show: function(type, title, message) {
                console.log('[Toast]', type, title, message);
                
                // Create toast element
                var toast = document.createElement('div');
                toast.className = 'fallback-toast toast-' + type;
                toast.innerHTML = '<div class="toast-header">' + title + '</div><div class="toast-body">' + message + '</div>';
                
                // Style the toast
                Object.assign(toast.style, {
                    position: 'fixed',
                    top: '20px',
                    right: '20px',
                    zIndex: '9999',
                    backgroundColor: type === 'success' ? '#28a745' : '#dc3545',
                    color: 'white',
                    padding: '10px',
                    borderRadius: '5px',
                    boxShadow: '0 0.5rem 1rem rgba(0, 0, 0, 0.15)',
                    opacity: '0',
                    transition: 'opacity 0.3s ease-in-out'
                });
                
                // Add to document
                document.body.appendChild(toast);
                
                // Animate in
                setTimeout(function() {
                    toast.style.opacity = '1';
                }, 10);
                
                // Remove after 3 seconds
                setTimeout(function() {
                    toast.style.opacity = '0';
                    setTimeout(function() {
                        if (toast.parentNode) {
                            toast.parentNode.removeChild(toast);
                        }
                    }, 300);
                }, 3000);
            }
        };
        
        // Ensure jQuery is loaded first, then load Dropzone
        document.addEventListener('DOMContentLoaded', function() {
            if (!window.jQueryAvailable) {
                console.log('jQuery not detected! Loading from CDN...');
                var jqScript = document.createElement('script');
                jqScript.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
                jqScript.onload = function() {
                    console.log('jQuery loaded successfully!');
                    window.$ = window.jQuery;
                    window.jQueryAvailable = true;
                    
                    // Load Bootstrap after jQuery
                    loadBootstrap();
                };
                document.head.appendChild(jqScript);
            } else {
                // Check if Bootstrap is already loaded
                loadBootstrap();
            }
        });
        
        // Load Bootstrap if needed
        function loadBootstrap() {
            if (typeof $.fn === 'undefined' || typeof $.fn.modal === 'undefined') {
                console.log('Bootstrap modal not detected! Loading...');
                var bsScript = document.createElement('script');
                bsScript.src = '{{asset("libs/bootstrap/dist/js/bootstrap.bundle.min.js")}}';
                bsScript.onload = function() {
                    console.log('Bootstrap loaded successfully!');
                    // Initialize Bootstrap components
                    $('.modal').modal({show: false});
                    $('[data-toggle="tooltip"]').tooltip();
                    loadDropzone();
                };
                bsScript.onerror = function() {
                    console.error('Failed to load Bootstrap - using fallbacks');
                    // Initialize fallback systems
                    window.modalFallback.init();
                    loadDropzone();
                };
                document.head.appendChild(bsScript);
            } else {
                console.log('Bootstrap already loaded');
                loadDropzone();
            }
        }
        
        function loadDropzone() {
            if (typeof Dropzone === 'undefined') {
                console.log('Dropzone not detected! Loading from source...');
                var dzScript = document.createElement('script');
                dzScript.src = '{{ asset('libs/dropzone/dist/dropzone.js') }}';
                dzScript.onload = function() {
                    console.log('Dropzone loaded successfully!');
                    initializePostPage();
                };
                dzScript.onerror = function() {
                    console.error('Failed to load Dropzone - using fallbacks');
                    initializePostPage();
                };
                document.head.appendChild(dzScript);
            } else {
                console.log('Dropzone already loaded');
                initializePostPage();
            }
        }
        
        function initializePostPage() {
            console.log('Initializing post page...');
            
            // Set up global fallbacks
            if (typeof $.toast === 'undefined') {
                console.log('$.toast not available - setting up fallback');
                $.toast = function(opts) {
                    window.toastFallback.show(
                        opts.indicator ? opts.indicator.type : 'info', 
                        opts.title || '', 
                        opts.content || ''
                    );
                };
            }
            
            if (typeof window.launchToast === 'undefined') {
                console.log('launchToast not available - setting up fallback');
                window.launchToast = function(type, title, message) {
                    window.toastFallback.show(type, title, message);
                };
            }
            
            // Initialize simple file uploader
            console.log('Initializing simple file uploader');
            
            // Add direct file input for fallback
            var fileInput = document.getElementById('direct-file-input');
            if (!fileInput) {
                fileInput = document.createElement('input');
                fileInput.id = 'direct-file-input';
                fileInput.type = 'file';
                fileInput.style.display = 'none';
                fileInput.multiple = true;
                fileInput.accept = '.jpg,.jpeg,.gif,.png,.mp4,.zip,.rar,.avi,.mov,.webp,.webm,.mkv,.flv,.wmv,.mp3,.wav';
                document.body.appendChild(fileInput);
                
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        Array.from(e.target.files).forEach(function(file) {
                            if (typeof FileUpload !== 'undefined' && FileUpload.myDropzone) {
                                FileUpload.myDropzone.addFile(file);
                            } else {
                                console.log('Dropzone not available for file:', file.name);
                                launchToast('danger', 'Error', 'File upload system not available');
                            }
                        });
                    }
                });
            }
            
            // Set up tooltips with fallback
            if (typeof $.fn.tooltip !== 'undefined') {
                $('[data-toggle="tooltip"]').tooltip();
            } else {
                console.log('Tooltip function not available');
                // Simple tooltip fallback could be added here if needed
            }
        }
    </script>
    
    <script>
        // Make sure jQuery, Bootstrap, and FileUpload dependencies are available before proceeding
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Loading Dropzone...');
            
            // Wait for jQuery to be defined
            function checkDependencies() {
                if (typeof $ === 'undefined' || typeof FileUpload === 'undefined') {
                    console.log('Waiting for dependencies...');
                    setTimeout(checkDependencies, 200);
                    return;
                }
                
                console.log('Initializing FileUpload...');
                
                // Initialize the file uploader
                FileUpload.initDropZone('#dropzone-uploader', '/attachment/upload/post');
            }
            
            // Start checking for dependencies
            checkDependencies();
        });
    </script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize post actions buttons
            function initPostButtons() {
                console.log('Initializing post buttons');
                
                // Fix price button modal problems
                if (typeof PostCreate !== 'undefined' && (typeof $.fn === 'undefined' || typeof $.fn.modal === 'undefined')) {
                    // Override the modal function if Bootstrap is not loaded
                    PostCreate.showSetPricePostDialog = function() {
                        console.log('Modal plugin not available - using fallback');
                        window.modalFallback.show('#post-set-price-dialog');
                    };
                    
                    // Set up save and clear buttons
                    var saveBtn = document.querySelector('#post-set-price-dialog .price-save-btn');
                    if (saveBtn) {
                        saveBtn.addEventListener('click', function() {
                            PostCreate.savePostPrice();
                        });
                    }
                    
                    var clearBtn = document.querySelector('#post-set-price-dialog .price-clear-btn');
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            PostCreate.clearPostPrice();
                        });
                    }
                }
                
                // File button handler - ensure we don't bubble clicks through the entire button
                $('.file-upload-button').on('click', function(e) {
                    // Only handle clicks on the button itself or its direct children
                    if (e.target === this || $(e.target).hasClass('file-button-text') || 
                        $(e.target).is('svg') || $(e.target).is('path')) {
                        
                        console.log('File upload button clicked');
                        // Check if we can use the normal uploader
                        if (typeof FileUpload === 'undefined' || !FileUpload.myDropzone) {
                            // Use direct file input as fallback
                            $('#direct-file-input').click();
                        } else {
                            // Let FileUpload.js handle it
                            return;
                        }
                        
                        e.preventDefault();
                        e.stopPropagation();
                    }
                });
                
                // Price button handler
                $('.post-price-button').on('click', function(e) {
                    // Don't do anything if the button is disabled
                    if ($(this).hasClass('disabled')) return;
                    
                    if (typeof PostCreate !== 'undefined') {
                        e.preventDefault();
                        e.stopPropagation();
                        PostCreate.showSetPricePostDialog();
                    }
                });
            }
            
            // Run initialization after a short delay to ensure all scripts are loaded
            setTimeout(initPostButtons, 1000);
        });
    </script>
@stop

@section('content')
<div class="modern-post-create-container {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? 'dark-theme' : 'light-theme') : (Cookie::get('app_theme') == 'dark' ? 'dark-theme' : 'light-theme'))}}">
    <!-- Include hidden templates -->
    @include('elements.uploaded-file-preview-template')
    @include('elements.direct-upload-form')
    @include('elements.post-price-setup',['postPrice'=>(isset($post) ? $post->price : 0)])
    @include('elements.attachments-uploading-dialog')
    @include('elements.post-schedule-setup', isset($post) ? ['release_date' => $post->release_date,'expire_date' => $post->expire_date] : [])

    <!-- Main Content Container -->
    <div class="create-post-wrapper">
        <!-- Header Section -->
        <div class="create-post-header">
            <div class="header-content">
                <div class="page-title-section">
                    <h1 class="page-title">
                        <i class="fas fa-plus-circle"></i>
                        {{Route::currentRouteName() == 'posts.create' ? __('Create New Post') : __('Edit Post')}}
                    </h1>
                    <p class="page-subtitle">{{__('Share your thoughts, upload media, and connect with your audience')}}</p>
                </div>
            </div>
        </div>

        <!-- Warning Messages -->
        @if(!PostsHelper::getDefaultPostStatus(Auth::user()->id))
            <div class="warning-card">
                @include('elements.pending-posts-warning-box')
            </div>
        @endif

        @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
            <div class="verification-alert">
                <div class="alert-content">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="alert-text">
                        <strong>{{__("Verification Required")}}</strong>
                        <p>{{__("Before being able to publish an item, you need to complete your")}} <a href="{{route('my.settings',['type'=>'verify'])}}">{{__("profile verification")}}</a>.</p>
                    </div>
                    <button type="button" class="alert-close" data-dismiss="alert">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        @endif

        <!-- Main Post Creation Card -->
        <div class="post-creation-card">
            <!-- Text Input Section -->
            <div class="post-input-section">
                <div class="input-header">
                    <h3>{{__('What\'s on your mind?')}}</h3>
                    <div class="character-count">
                        <span class="current-count">0</span>
                        <span class="max-count">/ 5000</span>
                    </div>
                </div>
                
                <div class="text-input-container">
                    <textarea id="dropzone-uploader" 
                              name="input-text" 
                              class="modern-textarea dropzone" 
                              rows="6" 
                              spellcheck="false" 
                              placeholder="{{__('Share your thoughts, ideas, or experiences...')}}" 
                              maxlength="5000">{{isset($post) ? $post->text : ''}}</textarea>
                    <div class="textarea-focus-border"></div>
                    <div class="invalid-feedback" role="alert">
                        <i class="fas fa-exclamation-circle"></i>
                        <strong class="post-invalid-feedback">{{__('Your post must contain more than 10 characters.')}}</strong>
                    </div>
                </div>
            </div>

            <!-- Media Upload Section -->
            <div class="media-upload-section">
                <div class="upload-options">
                    <div class="upload-option">
                        <button class="upload-btn file-upload-button">
                            <div class="btn-content">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>{{__('Upload Files')}}</span>
                            </div>
                            <div class="btn-shine"></div>
                        </button>
                        <p class="upload-hint">{{__('Images, videos, documents')}}</p>
                    </div>
                    
                    <div class="upload-option">
                        <button class="upload-btn post-price-button">
                            <div class="btn-content">
                                <i class="fas fa-dollar-sign"></i>
                                <span>{{__('Set Price')}}</span>
                            </div>
                            <div class="btn-shine"></div>
                        </button>
                        <p class="upload-hint">{{__('Monetize your content')}}</p>
                    </div>
                </div>

                <!-- File Previews -->
                <div class="dropzone-previews dropzone"></div>
            </div>

            <!-- Post Actions -->
            <div class="post-actions-section">
                <div class="actions-left">
                    @include('elements.post-create-actions')
                </div>
                
                <div class="actions-right">
                    @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                        <button class="btn-save disabled">
                            <i class="fas fa-lock"></i>
                            {{__('Save Post')}}
                        </button>
                    @else
                        <button class="btn-save post-create-button">
                            <i class="fas fa-paper-plane"></i>
                            {{__('Publish Post')}}
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Post Creation Styles */
.modern-post-create-container {
    min-height: 100vh;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    padding: 2rem 0;
}

.modern-post-create-container.dark-theme {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
}

.create-post-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 0 1.5rem;
}

/* Header Section */
.create-post-header {
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.page-title-section {
    flex: 1;
}

.page-title {
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 0.5rem 0;
    color: #1f2937;
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.dark-theme .page-title {
    color: #f9fafb;
}

.page-title i {
    color: #cb0c9f;
    font-size: 1.75rem;
}

.page-subtitle {
    font-size: 1.1rem;
    color: #6b7280;
    margin: 0;
    line-height: 1.5;
}

.dark-theme .page-subtitle {
    color: #9ca3af;
}

.header-actions {
    display: flex;
    align-items: center;
}



/* Warning Cards */
.warning-card,
.verification-alert {
    margin-bottom: 1.5rem;
    padding: 1rem;
    background: rgba(245, 158, 11, 0.1);
    border: 1px solid rgba(245, 158, 11, 0.2);
    border-radius: 12px;
    backdrop-filter: blur(10px);
}

.verification-alert {
    background: rgba(239, 68, 68, 0.1);
    border-color: rgba(239, 68, 68, 0.2);
}

.alert-content {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
}

.alert-content i {
    font-size: 1.25rem;
    color: #f59e0b;
    margin-top: 0.125rem;
}

.verification-alert .alert-content i {
    color: #ef4444;
}

.alert-text strong {
    display: block;
    margin-bottom: 0.25rem;
    color: #1f2937;
}

.dark-theme .alert-text strong {
    color: #f9fafb;
}

.alert-text p {
    margin: 0;
    color: #6b7280;
    line-height: 1.5;
}

.dark-theme .alert-text p {
    color: #9ca3af;
}

.alert-text a {
    color: #cb0c9f;
    text-decoration: none;
    font-weight: 500;
}

.alert-close {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.alert-close:hover {
    background: rgba(0, 0, 0, 0.05);
    color: #374151;
}

.dark-theme .alert-close:hover {
    background: rgba(255, 255, 255, 0.05);
    color: #e5e7eb;
}

/* Main Post Creation Card */
.post-creation-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.1);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(255, 255, 255, 0.2);
    overflow: hidden;
    transition: all 0.3s ease;
}

.dark-theme .post-creation-card {
    background: rgba(17, 24, 39, 0.9);
    border-color: rgba(255, 255, 255, 0.1);
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
}

.post-creation-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.15);
}

.dark-theme .post-creation-card:hover {
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
}

/* Post Input Section */
.post-input-section {
    padding: 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dark-theme .post-input-section {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.input-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.input-header h3 {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    color: #1f2937;
}

.dark-theme .input-header h3 {
    color: #f9fafb;
}

.character-count {
    font-size: 0.875rem;
    color: #6b7280;
    font-weight: 500;
}

.dark-theme .character-count {
    color: #9ca3af;
}

.current-count {
    color: #cb0c9f;
    font-weight: 600;
}

.text-input-container {
    position: relative;
}

.modern-textarea {
    width: 100%;
    min-height: 120px;
    padding: 1.5rem;
    border: 2px solid rgba(0, 0, 0, 0.05);
    border-radius: 16px;
    font-size: 1rem;
    line-height: 1.6;
    resize: vertical;
    transition: all 0.3s ease;
    background: rgba(249, 250, 251, 0.5);
    color: #1f2937;
    font-family: inherit;
}

.dark-theme .modern-textarea {
    background: rgba(31, 41, 55, 0.5);
    border-color: rgba(255, 255, 255, 0.1);
    color: #f9fafb;
}

.modern-textarea:focus {
    outline: none;
    border-color: #cb0c9f;
    background: rgba(249, 250, 251, 0.8);
    box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
}

.dark-theme .modern-textarea:focus {
    background: rgba(31, 41, 55, 0.8);
}

.modern-textarea::placeholder {
    color: #9ca3af;
    font-style: italic;
}

.textarea-focus-border {
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 0;
    height: 2px;
    background: linear-gradient(90deg, #cb0c9f, #830866);
    border-radius: 1px;
    transition: width 0.3s ease;
}

.modern-textarea:focus + .textarea-focus-border {
    width: 80%;
}

/* Media Upload Section */
.media-upload-section {
    padding: 2rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.dark-theme .media-upload-section {
    border-bottom-color: rgba(255, 255, 255, 0.1);
}

.upload-options {
    display: flex;
    gap: 2rem;
    margin-bottom: 1.5rem;
}

.upload-option {
    flex: 1;
    text-align: center;
}

.upload-btn {
    position: relative;
    width: 100%;
    padding: 1.5rem;
    border: 2px dashed rgba(99, 102, 241, 0.3);
    border-radius: 16px;
    background: rgba(99, 102, 241, 0.05);
    color: #cb0c9f;
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.upload-btn:hover {
    border-color: #cb0c9f;
    background: rgba(99, 102, 241, 0.1);
    transform: translateY(-2px);
}

.btn-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    position: relative;
    z-index: 2;
}

.btn-content i {
    font-size: 2rem;
}

.btn-content span {
    font-weight: 600;
    font-size: 1rem;
}

.btn-shine {
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.6s ease;
}

.upload-btn:hover .btn-shine {
    left: 100%;
}

.upload-hint {
    margin: 0.75rem 0 0 0;
    font-size: 0.875rem;
    color: #6b7280;
    font-style: italic;
}

.dark-theme .upload-hint {
    color: #9ca3af;
}

/* Post Actions Section */
.post-actions-section {
    padding: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
}

.actions-left {
    flex: 1;
}

.actions-right {
    flex-shrink: 0;
}

.btn-save {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem 2rem;
    background: linear-gradient(135deg, #cb0c9f 0%, #830866 100%);
    color: white;
    border: none;
    border-radius: 16px;
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    position: relative;
    overflow: hidden;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
}

.btn-save:active {
    transform: translateY(0);
}

.btn-save.disabled {
    background: #6b7280;
    cursor: not-allowed;
    box-shadow: none;
}

.btn-save.disabled:hover {
    transform: none;
    box-shadow: none;
}

.btn-save i {
    font-size: 1.1rem;
}

/* Responsive Design */
@media (max-width: 768px) {
    .create-post-wrapper {
        padding: 0 1rem;
    }
    
    .header-content {
        flex-direction: column;
        gap: 1rem;
    }
    
    .page-title {
        font-size: 1.75rem;
    }
    
    .post-input-section,
    .media-upload-section,
    .post-actions-section {
        padding: 1.5rem;
    }
    
    .upload-options {
        flex-direction: column;
        gap: 1rem;
    }
    
    .post-actions-section {
        flex-direction: column;
        gap: 1.5rem;
    }
    
    .actions-right {
        width: 100%;
    }
    
    .btn-save {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .modern-post-create-container {
        padding: 1rem 0;
    }
    
    .page-title {
        font-size: 1.5rem;
    }
    
    .post-input-section,
    .media-upload-section,
    .post-actions-section {
        padding: 1rem;
    }
    
    .modern-textarea {
        padding: 1rem;
        min-height: 100px;
    }
}

/* Character Counter Animation */
@keyframes countUpdate {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.character-count.updated {
    animation: countUpdate 0.3s ease;
}

/* Focus States */
.modern-textarea:focus {
    animation: textareaFocus 0.3s ease;
}

@keyframes textareaFocus {
    0% { transform: scale(1); }
    100% { transform: scale(1.01); }
}

/* Upload Button Hover Effect */
.upload-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.1) 0%, rgba(139, 92, 246, 0.1) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    border-radius: 14px;
}

.upload-btn:hover::before {
    opacity: 1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter
    const textarea = document.getElementById('dropzone-uploader');
    const currentCount = document.querySelector('.current-count');
    const characterCount = document.querySelector('.character-count');
    
    if (textarea && currentCount) {
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            currentCount.textContent = count;
            
            // Add animation class
            characterCount.classList.add('updated');
            setTimeout(() => {
                characterCount.classList.remove('updated');
            }, 300);
            
            // Change color based on character count
            if (count > 4500) {
                currentCount.style.color = '#ef4444';
            } else if (count > 4000) {
                currentCount.style.color = '#f59e0b';
            } else {
                currentCount.style.color = '#cb0c9f';
            }
        });
        
        // Initial count
        currentCount.textContent = textarea.value.length;
    }
    
    // Enhanced upload button interactions
    const uploadBtns = document.querySelectorAll('.upload-btn');
    uploadBtns.forEach(btn => {
        btn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px) scale(1.02)';
        });
        
        btn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
    });
    
    // Save button enhancement
    const saveBtn = document.querySelector('.btn-save:not(.disabled)');
    if (saveBtn) {
        saveBtn.addEventListener('click', function() {
            // Add loading state
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Publishing...';
            this.style.pointerEvents = 'none';
        });
    }
});
</script>

@stop
