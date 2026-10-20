/**
 *
 * Main App Component
 *
 */
"use strict";
/* global app, mediaSettings, Dropzone, trans, launchToast */

// Initialize global container to track whether Dropzone has been initialized
if (typeof window.dropzoneInitialized === 'undefined') {
    window.dropzoneInitialized = false;
}

// jQuery check - ensure it's available
(function() {
    if (typeof jQuery === 'undefined') {
        console.error("jQuery not loaded. FileUpload requires jQuery.");
        
        // Try to add jQuery
        var script = document.createElement('script');
        script.src = 'https://code.jquery.com/jquery-3.6.0.min.js';
        script.integrity = 'sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=';
        script.crossOrigin = 'anonymous';
        document.head.appendChild(script);
    }
    
    if (typeof jQuery !== 'undefined' && typeof $ === 'undefined') {
        window.$ = window.jQuery;
    }
})();

// Check if Dropzone is available
if (typeof Dropzone === 'undefined') {
    console.error("Dropzone not loaded. FileUpload requires Dropzone.");
}

// Wait for document ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize when both jQuery and Dropzone are available
    if (typeof $ !== 'undefined' && typeof Dropzone !== 'undefined') {
        initializeFileUpload();
    } else {
        console.log("Dependencies not loaded yet, waiting...");
        
        // Try again after a delay
        setTimeout(function checkDependencies() {
            if (typeof $ !== 'undefined' && typeof Dropzone !== 'undefined') {
                initializeFileUpload();
            } else {
                console.log("Still waiting for dependencies...");
                setTimeout(checkDependencies, 500);
            }
        }, 500);
    }
});

function initializeFileUpload() {
    // If FileUpload is already initialized, don't do it again
    if (window.dropzoneInitialized) {
        console.log("Dropzone already initialized, skipping...");
        return;
    }
    
    console.log("Initializing FileUpload module...");
    
    // Set Dropzone default options
    Dropzone.autoDiscover = false;
    window.dropzoneInitialized = true;
}

var FileUpload = {
    attachaments: [],
    myDropzone: null,
    isLoading: false,
    isTranscodingVideo: false,
    state: {},

    /**
     * Instantiates the media uploader plugin
     * @param selector - CSS selector or DOM element
     * @param url - URL for uploads
     * @param isChunkUpload - Whether to use chunked uploads
     */
    initDropZone: function(selector, url, isChunkUpload = false) {
        console.log("FileUpload.initDropZone called with selector:", selector);
        
        // Check for jQuery availability
        if (typeof $ === 'undefined') {
            console.error("jQuery not available for FileUpload.initDropZone");
            return;
        }
        
        // Check for Dropzone availability
        if (typeof Dropzone === 'undefined') {
            console.error("Dropzone not available for FileUpload.initDropZone");
            return;
        }
        
        // Check if the element exists
        if (!$(selector).length) {
            console.error("Target element not found:", selector);
            return;
        }
        
        // Check if we already have an instance for this selector
        if (FileUpload.myDropzone !== null) {
            console.log("FileUpload instance already exists, destroying old instance");
            try {
                FileUpload.myDropzone.destroy();
            } catch (e) {
                console.error("Error destroying old Dropzone instance:", e);
            }
        }

        // Set up chunk uploads if enabled
        let chunkSize = 1024;
        if (isChunkUpload) {
            chunkSize = mediaSettings.upload_chunk_size * 1000000;
            url = url.replace('/upload/', '/uploadChunked/');
        }

        // Add simple event handler for direct file uploads
        $(document).off('click', '.file-upload-button').on('click', '.file-upload-button', function(e) {
            // Only trigger if we clicked directly on the button or its text
            if (e.target === this || $(e.target).hasClass('file-button-text') || $(e.target).is('svg') || $(e.target).is('path')) {
                console.log('File upload button clicked');
                
                // Check if Dropzone is working
                if (typeof FileUpload !== 'undefined' && FileUpload.myDropzone) {
                    // Prevent multiple click events
                    e.preventDefault();
                    e.stopPropagation();
                    FileUpload.myDropzone.hiddenFileInput.click();
                    return false;
                } else {
                    // Fallback to direct file input
                    $('#direct-file-input').click();
                }
            }
        });

        // Ensure app.baseUrl is available
        if (typeof app === 'undefined' || !app.baseUrl) {
            console.error("app.baseUrl not defined");
            app = app || {};
            app.baseUrl = '';
        }

        // Full URL for upload endpoint
        const uploadUrl = app.baseUrl + url;
        console.log("Upload URL:", uploadUrl);

        // Create Dropzone instance
        try {
            FileUpload.myDropzone = new Dropzone(selector, {
                paramName: "file", // The name that will be used to transfer the file
                url: uploadUrl,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                clickable: true, // Make the entire dropzone clickable
                previewsContainer: ".messenger-dropzone-previews, .dropzone-previews",
                maxFilesize: mediaSettings.max_file_upload_size, // MB
                addRemoveLinks: true,
                dictRemoveFile: "x",
                acceptedFiles: mediaSettings.allowed_file_extensions,
                chunking: isChunkUpload,
                forceChunking: isChunkUpload,
                chunkSize: chunkSize,
                timeout: 180000, // 3 minutes
                retryChunks: false,
                retryChunksLimit: 2,
                autoProcessQueue: true, // Automatically process files
                init: function() {
                    console.log("Dropzone initialized successfully");
                    
                    var self = this;
                    
                    // Load existing attachments
                    if (FileUpload.attachaments && FileUpload.attachaments.length) {
                        FileUpload.attachaments.forEach(function(element) {
                            if (element && element.attachmentID) {
                                try {
                                    var mockFile = { 
                                        name: element.attachmentID, 
                                        upload: { attachmentID: element.attachmentID }, 
                                        type: element.type, 
                                        thumbnail: element.thumbnail 
                                    };
                                    
                                    // Safe emit function that checks if methods exist
                                    function safeEmit(event, ...args) {
                                        if (self && typeof self.emit === 'function') {
                                            self.emit(event, ...args);
                                        } else {
                                            console.error("Cannot emit Dropzone event: " + event);
                                        }
                                    }
                                    
                                    safeEmit("addedfile", mockFile);
                                    safeEmit("thumbnail", mockFile, element.thumbnail);
                                    safeEmit("complete", mockFile);
                                    
                                    // Update preview with safe checks
                                    FileUpload.updatePreviewElement(mockFile, false, element);
                                } catch (e) {
                                    console.error("Error adding existing file to Dropzone:", e);
                                }
                            }
                        });
                    }
                    
                    // Handle clear button
                    $(".draft-clear-button").off('click').on("click", function() {
                        if (FileUpload.myDropzone && typeof FileUpload.myDropzone.removeAllFiles === 'function') {
                            FileUpload.myDropzone.removeAllFiles(true);
                        }
                    });
                    
                    // Handle save button
                    $(document).off('click', '.submit-button, .save-button').on('click', '.submit-button, .save-button', function(e) {
                        // Process any remaining files in the queue
                        if (FileUpload.isLoading) {
                            e.preventDefault();
                            return launchToast('warning', trans('Please wait'), trans('Please wait for uploads to complete'));
                        }
                        
                        // If there are files in the queue but not uploaded yet, process them
                        if (FileUpload.myDropzone && FileUpload.myDropzone.getQueuedFiles().length > 0) {
                            e.preventDefault();
                            FileUpload.myDropzone.processQueue();
                            
                            // Submit the form when files are processed
                            FileUpload.myDropzone.on('queuecomplete', function() {
                                setTimeout(function() {
                                    $(e.target).closest('form').submit();
                                }, 500);
                            });
                        }
                    });
                }
            });
            
            console.log("Dropzone setup complete");

            // Add event handlers with error handling
            FileUpload.myDropzone.on("sending", function() {
                FileUpload.isLoading = true;
            });

            FileUpload.myDropzone.on("removedfile", function(file) {
                if (file && file.upload && file.upload.attachmentID) {
                    FileUpload.removeAttachment(file.upload.attachmentID);
                }

                if (file && file.previewObjectUrl) {
                    window.URL.revokeObjectURL(file.previewObjectUrl);
                    file.previewObjectUrl = null;
                }
            });

            FileUpload.myDropzone.on("complete", function() {
                FileUpload.isLoading = false;
            });

            FileUpload.myDropzone.on("addedfile", function(file) {
                if (file && file.type && file.type.indexOf('image') === 0) {
                    FileUpload.imagePreview(file, {
                        type: 'image',
                        path: '',
                        thumbnail: ''
                    });
                }
            });

            FileUpload.myDropzone.on("success", function(file, response) {
                if (response && response.success) {
                    file.upload.attachmentID = response.attachmentID;
                    
                    // Logging
                    console.log("File uploaded successfully:", response);
                    
                    // Save attachment to FileUpload internal state
                    FileUpload.attachaments.push({
                        attachmentID: response.attachmentID,
                        path: response.path,
                        type: response.type,
                        thumbnail: response.thumbnail || response.path
                    });
                    
                    // Update preview elements
                    FileUpload.updatePreviewElement(file, response);
                    
                    // Notify user
                    if (typeof launchToast === 'function') {
                        launchToast('success', trans('Success'), trans('File uploaded successfully'));
                    }
                }
            });

            FileUpload.myDropzone.on("error", function(file, errorMessage) {
                console.error("Upload error:", errorMessage);
                if (typeof launchToast === 'function') {
                    launchToast('danger', trans('Error'), errorMessage);
                } else {
                    alert("Upload error: " + errorMessage);
                }
                // Remove the file from the queue
                FileUpload.myDropzone.removeFile(file);
            });
            
            return FileUpload.myDropzone;
        } catch (e) {
            console.error("Error initializing Dropzone:", e);
            return null;
        }
    },
    
    // Update preview element based on file type
    updatePreviewElement: function(file, response = false, element = false) {
        try {
            if (response) {
                element = response;
            }
            
            if (element) {
                const type = element.type || 'image';
                
                if (type.includes('image')) {
                    this.imagePreview(file, element);
                } else if (type.includes('video')) {
                    this.videoPreview(file, element);
                } else if (type.includes('audio')) {
                    this.audioPreview(file, element);
                } else if (type.includes('pdf')) {
                    this.pdfPreview(file, element);
                } else if (type.includes('spreadsheet') || type.includes('excel')) {
                    this.excelPreview(file, element);
                }
                
                // Add price button for all elements
                const previewElement = file.previewElement;
                if (previewElement) {
                    const priceBtn = document.createElement('span');
                    priceBtn.className = 'post-price-button';
                    priceBtn.innerHTML = '<ion-icon name="pricetag-outline"></ion-icon>';
                    priceBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        if (typeof PostCreate !== 'undefined') {
                            PostCreate.showSetPricePostDialog();
                        }
                    });
                    previewElement.appendChild(priceBtn);
                }
            }
        } catch (e) {
            console.error("Error updating preview element:", e);
        }
    },

    /**
     * Sets up the media src for the uploaded file type
     * @param element
     * @param file
     * @returns {boolean}
     */
    setMediaSourceForPreviewByElementAndFile: function (element, file) {
        if(typeof element === 'undefined'){ return false;}
        if (element.canPlayType(file.type).length && element.canPlayType(file.type) !== "no") {
            const fileURL = window.URL.createObjectURL(file);
            $(element).on('loadeddata', function () {
                window.URL.revokeObjectURL(fileURL);
            });
            $(element).attr('src', fileURL);
            $(element).attr('type',file.type);
        }
        else{
            $(element).attr('src', app.baseUrl+'/img/video-loading-spinner.mp4');
            $(element).attr('loop', true);
        }
    },

    /**
     * Sets media source | Thumbnail
     * @param element
     * @param file
     * @param attachment
     */
    setPreviewSource: function (element, file, attachment) {
        if (!element) {
            return;
        }

        if(attachment && attachment.coconut_id !== null && attachment.path && attachment.path.indexOf('videos/tmp/') >= 0){
            // TODO: Use some different video loop for the transcoding pahse
            $(element).attr('src', app.baseUrl+'/img/video-loading-spinner.mp4');
        }
        else{
            $(element).attr('src', FileUpload.normalizePreviewUrl(attachment && attachment.path ? attachment.path : ''));
        }
    },

    normalizePreviewUrl: function(url) {
        if (!url) {
            return '';
        }

        if (url.indexOf('blob:') === 0 || url.indexOf('data:') === 0 || /^https?:\/\//i.test(url)) {
            return url;
        }

        if (url.charAt(0) === '/') {
            return url;
        }

        return (app.baseUrl || '').replace(/\/$/, '') + '/' + url.replace(/^\//, '');
    },

    getLocalImagePreviewSource: function(file) {
        if (!file) {
            return '';
        }

        if (file.dataURL) {
            return file.dataURL;
        }

        if (file.previewObjectUrl) {
            return file.previewObjectUrl;
        }

        if (typeof Blob !== 'undefined' && file instanceof Blob) {
            file.previewObjectUrl = window.URL.createObjectURL(file);
            return file.previewObjectUrl;
        }

        return '';
    },

    getImagePreviewSource: function(file, attachment) {
        var localSource = FileUpload.getLocalImagePreviewSource(file);
        var serverSource = '';

        if (attachment) {
            serverSource = FileUpload.normalizePreviewUrl(attachment.thumbnail || attachment.path);
        }

        if (!serverSource && file) {
            serverSource = FileUpload.normalizePreviewUrl(file.thumbnail || file.path);
        }

        return localSource || serverSource || FileUpload.imageFallbackDataUrl();
    },

    imageFallbackDataUrl: function() {
        return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="480" height="320" viewBox="0 0 480 320"><rect width="480" height="320" fill="#eef2f7"/><path d="M92 238l83-92 57 63 42-46 114 75H92z" fill="#cbd5e1"/><circle cx="350" cy="92" r="34" fill="#94a3b8"/><text x="240" y="282" text-anchor="middle" font-family="Arial, sans-serif" font-size="20" fill="#64748b">Preview unavailable</text></svg>');
    },

    applyOneShotImageFallback: function(img, fallbackSource) {
        if (!img) {
            return;
        }

        img.onerror = function() {
            var nextSource = this.dataset.fallbackSrc;
            this.onerror = null;

            if (nextSource && this.src !== nextSource) {
                this.src = nextSource;
                this.dataset.fallbackSrc = '';
                this.onerror = function() {
                    this.onerror = null;
                    this.src = FileUpload.imageFallbackDataUrl();
                };
                return;
            }

            this.src = FileUpload.imageFallbackDataUrl();
        };

        if (fallbackSource) {
            img.dataset.fallbackSrc = fallbackSource;
        }
    },

    /**
     * Removes an attached file
     * @param attachmentID
     */
    removeAttachment: function (attachmentID) {
        $.ajax({
            type: 'POST',
            data: {
                'attachmentId': attachmentID,
            },
            url: app.baseUrl+'/attachment/remove',
            success: function () {
                launchToast('success',trans('Success'), trans('Attachment removed.'));
            },
            error: function () {
                launchToast('danger',trans('Error'), trans('Failed to remove the attachment.'));
            }
        });
    },

    // Video preview component
    videoPreview: function(file, element) {
        let filePreview = $(file.previewElement);
        filePreview.find('.dz-image').remove();
        filePreview.prepend(videoPreviewTemplate(file));
        var videoPreviewEl = filePreview.find('video').get(0);
        FileUpload.setMediaSourceForPreviewByElementAndFile(videoPreviewEl, file);
    },

    // Image preview component
    imagePreview: function(file, element) {
        let filePreview = $(file.previewElement);
        filePreview.find('.dz-image').remove();
        var primarySource = FileUpload.getImagePreviewSource(file, element);
        var fallbackSource = element && element.path ? FileUpload.normalizePreviewUrl(element.path) : '';
        filePreview.prepend(imagePreviewTemplate(primarySource, fallbackSource));
        FileUpload.applyOneShotImageFallback(filePreview.find('img').get(0), fallbackSource);
    },

    // Audio preview component
    audioPreview: function(file, element) {
        let filePreview = $(file.previewElement);
        filePreview.prepend(audioPreviewTemplate(file));
        filePreview.addClass("w-100");
        filePreview.find('audio').addClass("w-100");
        filePreview.find(".audio-preview-item").addClass("w-100");
        var audioPreviewEl = filePreview.find('audio').get(0);
        filePreview.addClass("w-100");
        FileUpload.setMediaSourceForPreviewByElementAndFile(audioPreviewEl, file);
    },

    // PDF document preview component
    pdfPreview: function(file, element) {
        let filePreview = $(file.previewElement);
        filePreview.prepend(pdfPreviewTemplate(file));
    },

    // Excel document preview component
    excelPreview: function(file, element) {
        let filePreview = $(file.previewElement);
        filePreview.prepend(excelPreviewTemplate(file));
    },
};

// Define preview templates for various file types
function videoPreviewTemplate(file) {
    return '<div class="video-preview-item shadow"><video class="video-preview" controls autoplay muted loop></video></div>';
}

function imagePreviewTemplate(source, fallbackSource) {
    var fallbackAttribute = fallbackSource ? ' data-fallback-src="' + fallbackSource + '"' : '';
    return '<div class="dz-image shadow"><img src="' + source + '"' + fallbackAttribute + '/></div>';
}

function audioPreviewTemplate(file) {
    return '<div class="audio-preview-item shadow"><audio controls></audio></div>';
}

function pdfPreviewTemplate(file) {
    return '<div class="dz-image shadow"><img src="' + app.baseUrl + '/img/pdf-icon.png" style="max-width:64px;"/></div>';
}

function excelPreviewTemplate(file) {
    return '<div class="dz-image shadow"><img src="' + app.baseUrl + '/img/excel-icon.png" style="max-width:64px;"/></div>';
}

$(document).ready(function() {
    // Fix for price button - ensure it doesn't trigger file upload
    $(document).off('click', '.post-price-button').on('click', '.post-price-button', function(e) {
        // Only run if PostCreate is defined
        if (typeof PostCreate !== 'undefined') {
            e.preventDefault();
            e.stopPropagation();
            
            // Show price dialog if user is allowed
            if (!$(this).hasClass('disabled')) {
                PostCreate.showSetPricePostDialog();
            }
            return false;
        }
    });
});

