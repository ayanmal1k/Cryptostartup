@extends('layouts.generic')

@section('content')
{{-- Installed first: play()/pause() must be made race-safe before any script below calls them --}}
@include('elements.reels-playback')
<div class="reels-container">
    <div class="video-feed">
        @foreach($videos as $index => $video)
        <div class="video-item" data-video-id="{{ $video->id }}">
            <div class="video-wrapper">
                <video 
                    src="{{ $video->video_url }}" 
                    poster="{{ $video->thumbnail_url }}"
                    loop
                    muted
                    playsinline
                    preload="metadata"
                    class="video-player"
                    id="video-{{ $video->id }}"
                    webkit-playsinline
                    x-webkit-airplay="allow"
                ></video>
            </div>
            
            <div class="video-overlay">
                <div class="video-info">
                    <div class="user-info">
                        <div class="user-avatar-initials" style="background: {{ ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7', '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'][crc32($video->user->name) % 10] }};">
                            {{ strtoupper(substr($video->user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $video->user->name)[1] ?? '', 0, 1)) }}
                        </div>
                        <div class="user-details">
                            <h4>{{ $video->user->name }}</h4>
                            <p>{{ '@' . ($video->user->username ?? $video->user->name) }}</p>
                        </div>
                    </div>
                    
                    <div class="video-caption">
                        <p class="video-title">{{ $video->title }}</p>
                        @if($video->description)
                            <p class="video-description">{{ $video->description }}</p>
                        @endif
                    </div>
                </div>
                
                <div class="video-actions">
                    <div class="action-item">
                        <button class="action-btn like-btn" data-video-id="{{ $video->id }}">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    
                    <div class="action-item">
                        <button class="action-btn comment-btn" data-video-id="{{ $video->id }}">
                            <i class="fas fa-comment"></i>
                        </button>
                    </div>
                    
                    <div class="action-item">
                        <button class="action-btn share-btn" data-video-id="{{ $video->id }}">
                            <i class="fas fa-share"></i>
                        </button>
                    </div>
                    
                    <div class="action-item create-btn-item">
                        <a href="{{ route('videos.create') }}" class="action-btn create-btn">
                            <i class="fas fa-plus"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="progress-bar">
                <div class="progress"></div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Fixed Comments Sidebar -->
    <div class="comments-sidebar" id="comments-sidebar">
        <div class="comments-header">
            <h3>Comments</h3>
            <button class="close-comments">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="comments-list" id="comments-list">
            <div class="no-comments">
                <p>No comments yet. Be the first to comment!</p>
            </div>
        </div>
        
        @auth
        <div class="comment-form">
            <input type="text" id="comment-input" placeholder="Add a comment...">
            <button id="post-comment">Post</button>
        </div>
        @else
        <div class="comment-form">
            <p class="login-message">Please <a href="{{ route('login') }}">login</a> to comment</p>
        </div>
        @endauth
    </div>
</div>

<!-- Make sure Font Awesome is loaded -->
@if(!str_contains(app('view')->getSections()['head'] ?? '', 'font-awesome'))
    @push('head')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @endpush
@endif

@push('scripts')
<script>
    // Device detection
    const isMobile = /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent);
    const isAndroid = /Android/.test(navigator.userAgent);
    
    // Get the target video ID from URL parameter
    const urlParams = new URLSearchParams(window.location.search);
    const targetVideoId = urlParams.get('video');
    
    // Utility function to get user initials
    function getUserInitials(name) {
        if (!name) return 'U';
        const words = name.trim().split(' ');
        if (words.length === 1) {
            return words[0].charAt(0).toUpperCase();
        }
        return (words[0].charAt(0) + (words[1]?.charAt(0) || '')).toUpperCase();
    }

    // Utility function to create initials avatar
    function createInitialsAvatar(name, className = 'comment-avatar-initials') {
        const initials = getUserInitials(name);
        const colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
        ];
        const colorIndex = name.charCodeAt(0) % colors.length;
        const bgColor = colors[colorIndex];
        
        return `<div class="${className}" style="background-color: ${bgColor};">${initials}</div>`;
    }

    document.addEventListener('DOMContentLoaded', function() {
        const videoItems = document.querySelectorAll('.video-item');
        const videoPlayers = document.querySelectorAll('.video-player');
        let currentVideoIndex = 0;
        let currentVideoId = null;
        let isInitialized = false;
        
        // Find the target video index if specified in URL
        let targetVideoIndex = 0;
        if (targetVideoId) {
            const targetVideoElement = document.querySelector(`[data-video-id="${targetVideoId}"]`);
            if (targetVideoElement) {
                targetVideoIndex = Array.from(videoItems).indexOf(targetVideoElement);
            }
        }
        
        // Request tracking to prevent loops
        let activeRequests = new Set();
        let requestCooldowns = new Map();
        const COOLDOWN_MS = 2000;
        
        // Safe request function with loop prevention
        function safeRequest(key, requestFn, cooldownMs = COOLDOWN_MS) {
            if (activeRequests.has(key)) {
                return Promise.resolve();
            }
            
            const lastRequest = requestCooldowns.get(key);
            const now = Date.now();
            if (lastRequest && (now - lastRequest) < cooldownMs) {
                return Promise.resolve();
            }
            
            activeRequests.add(key);
            requestCooldowns.set(key, now);
            
            return requestFn()
                .finally(() => {
                    activeRequests.delete(key);
                });
        }
        
        // Improved fetch with proper error handling
        async function safeFetch(url, options = {}) {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        ...options.headers
                    }
                });
                
                if (response.status === 429) {
                    return { ok: false, status: 429 };
                }
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                return response;
            } catch (error) {
                return { ok: false, error };
            }
        }

        // Enhanced video preparation for cross-platform compatibility
        function prepareVideo(video, index) {
            // Set video attributes for better compatibility (don't set muted by default)
            video.setAttribute('playsinline', '');
            video.setAttribute('webkit-playsinline', '');
            video.setAttribute('x-webkit-airplay', 'allow');
            video.preload = 'metadata';
            
            // Force full width/height
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'cover';
            video.style.objectPosition = 'center';
            
            // For mobile devices, especially iOS
            if (isMobile) {
                video.setAttribute('controls', 'false');
                video.removeAttribute('controls');
                video.style.minWidth = '100vw';
                video.style.minHeight = '100vh';
            }
            
            // Apply user's sound preference (default to unmuted)
            applySoundPreference(video);
            
            // Ensure proper loading
            video.load();
            
            // Add event listeners for better control
            video.addEventListener('loadedmetadata', function() {
                // Force dimensions after metadata loads
                this.style.width = '100%';
                this.style.height = '100%';
                this.style.objectFit = 'cover';
                this.style.objectPosition = 'center';
                
                // Additional mobile adjustments
                if (isMobile) {
                    this.style.minWidth = '100vw';
                    this.style.minHeight = '100vh';
                }
            });
            
            video.addEventListener('canplay', function() {
                // Only auto-play if this is the target video or first video when no target specified
                if ((targetVideoId && index === targetVideoIndex && !isInitialized) || 
                    (!targetVideoId && index === 0 && !isInitialized)) {
                    setTimeout(() => {
                        playVideo(video);
                        isInitialized = true;
                    }, 500);
                }
            });
            
            video.addEventListener('error', function(e) {
                console.error(`Video ${index} error:`, e);
                this.closest('.video-item').querySelector('.video-overlay').innerHTML += 
                    '<div class="video-error">Video failed to load</div>';
            });
            
            // Handle resize events to maintain full coverage
            video.addEventListener('resize', function() {
                this.style.width = '100%';
                this.style.height = '100%';
                this.style.objectFit = 'cover';
                this.style.objectPosition = 'center';
            });
        }

        // Enhanced play function with cross-platform support
        async function playVideo(video) {
            if (!video) return;
            
            try {
                // Reset video to start
                video.currentTime = 0;
                
                // ALWAYS try to play with sound first (ignore session storage)
                video.muted = false;
                video.volume = 1.0;
                
                // Try to play with sound first
                try {
                    await video.play();
                } catch (autoplayError) {
                    // If autoplay with sound fails, try muted
                    video.muted = true;
                    await video.play();
                }
                
                // Add sound control overlay after video starts
                addSoundControl(video);
                
            } catch (error) {
                
                // If all autoplay attempts fail, add a play button overlay
                if (error.name === 'NotAllowedError') {
                    addPlayButton(video);
                }
            }
        }

        // Add sound control button to video
        function addSoundControl(video) {
            const videoWrapper = video.closest('.video-wrapper');
            let soundButton = videoWrapper.querySelector('.sound-control-btn');
            
            if (!soundButton) {
                soundButton = document.createElement('button');
                soundButton.className = 'sound-control-btn';
                soundButton.title = 'Toggle sound';
                
                soundButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    toggleSound(video, this);
                });
                
                videoWrapper.appendChild(soundButton);
            }
            
            // Update button state based on current mute status
            updateSoundButton(video, soundButton);
        }

        // Toggle sound on/off (only affects current session, not page reload)
        function toggleSound(video, button) {
            if (video.muted) {
                video.muted = false;
                video.volume = 1.0;
                button.innerHTML = '<i class="fas fa-volume-up"></i>';
                button.title = 'Tap to mute';
                button.classList.remove('muted');
                button.classList.add('unmuted');
                
                // Apply to all videos for consistent experience (session only)
                videoPlayers.forEach(v => {
                    if (v !== video) {
                        v.muted = false;
                        v.volume = 1.0;
                        const otherButton = v.closest('.video-wrapper').querySelector('.sound-control-btn');
                        if (otherButton) {
                            updateSoundButton(v, otherButton);
                        }
                    }
                });
                
                showToast('Sound enabled');
            } else {
                video.muted = true;
                button.innerHTML = '<i class="fas fa-volume-mute"></i>';
                button.title = 'Tap to unmute';
                button.classList.add('muted');
                button.classList.remove('unmuted');
                
                // Apply to all videos (session only)
                videoPlayers.forEach(v => {
                    v.muted = true;
                    const otherButton = v.closest('.video-wrapper').querySelector('.sound-control-btn');
                    if (otherButton) {
                        updateSoundButton(v, otherButton);
                    }
                });
                
                showToast('Sound muted');
            }
        }

        // Update sound button appearance
        function updateSoundButton(video, button) {
            if (video.muted) {
                button.innerHTML = '<i class="fas fa-volume-mute"></i>';
                button.title = 'Tap to unmute';
                button.classList.add('muted');
                button.classList.remove('unmuted');
            } else {
                button.innerHTML = '<i class="fas fa-volume-up"></i>';
                button.title = 'Tap to mute';
                button.classList.remove('muted');
                button.classList.add('unmuted');
            }
        }

        // Check for user's previous sound preference (ALWAYS default to sound enabled)
        function applySoundPreference(video) {
            // ALWAYS start with sound enabled on page load
            video.muted = false;
            video.volume = 1.0;
        }

        // Add play button for manual interaction (if autoplay fails)
        function addPlayButton(video) {
            const videoWrapper = video.closest('.video-wrapper');
            let playButton = videoWrapper.querySelector('.manual-play-btn');
            
            if (!playButton) {
                playButton = document.createElement('button');
                playButton.className = 'manual-play-btn';
                playButton.innerHTML = '<i class="fas fa-play"></i>';
                playButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    
                    // ALWAYS try to play with sound first
                    video.muted = false;
                    video.volume = 1.0;
                    
                    video.play().then(() => {
                        this.style.display = 'none';
                        addSoundControl(video); // Add sound control after manual play
                    }).catch(err => {
                        video.muted = true;
                        video.play().then(() => {
                            this.style.display = 'none';
                            addSoundControl(video);
                        }).catch(err2 => console.log('Manual play failed completely:', err2));
                    });
                });
                videoWrapper.appendChild(playButton);
            }
        }

        // Pause all videos except the current one
        function pauseAllVideosExcept(currentVideo) {
            videoPlayers.forEach(video => {
                if (video !== currentVideo) {
                    video.pause();
                    video.currentTime = 0;
                    
                    // Reset progress bar
                    const progressBar = video.closest('.video-item').querySelector('.progress');
                    if (progressBar) {
                        progressBar.style.width = '0%';
                    }
                }
            });
        }

        // Enhanced Intersection Observer for better video management
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                const video = entry.target;
                const videoWrapper = video.closest('.video-wrapper');
                const playButton = videoWrapper.querySelector('.manual-play-btn');
                
                if (entry.isIntersecting && entry.intersectionRatio > 0.7) {
                    // Pause all other videos first
                    pauseAllVideosExcept(video);
                    
                    // Update current index and video ID
                    currentVideoIndex = Array.from(videoPlayers).indexOf(video);
                    currentVideoId = video.closest('.video-item').dataset.videoId;
                    
                    // Reset and start progress bar
                    const progressBar = video.closest('.video-item').querySelector('.progress');
                    if (progressBar) {
                        progressBar.style.width = '0%';
                        startProgressBar(video, progressBar);
                    }
                    
                    // Play the video
                    playVideo(video);
                    
                    // Hide manual play button if exists
                    if (playButton) {
                        playButton.style.display = 'none';
                    }
                    
                    // Update view count (with cooldown)
                    safeRequest(`view-${currentVideoId}`, () => updateViewCount(currentVideoId), 5000);
                } else {
                    // Pause when out of view
                    video.pause();
                    
                    // Reset progress bar
                    const progressBar = video.closest('.video-item').querySelector('.progress');
                    if (progressBar) {
                        progressBar.style.width = '0%';
                    }
                }
            });
        }, {
            threshold: [0.5, 0.7, 0.9],
            rootMargin: '-10% 0px'
        });

        // Initialize all videos
        videoPlayers.forEach((video, index) => {
            prepareVideo(video, index);
            observer.observe(video);
            
            // Enhanced click handler for play/pause
            let clickCount = 0;
            let clickTimer = null;
            
            video.addEventListener('click', function(e) {
                e.stopPropagation();
                
                clickCount++;
                
                if (clickCount === 1) {
                    clickTimer = setTimeout(() => {
                        // Single click - play/pause
                        if (this.paused) {
                            playVideo(this);
                        } else {
                            this.pause();
                        }
                        clickCount = 0;
                    }, 300);
                } else if (clickCount === 2) {
                    // Double click - toggle sound
                    clearTimeout(clickTimer);
                    const soundButton = this.closest('.video-wrapper').querySelector('.sound-control-btn');
                    if (soundButton) {
                        toggleSound(this, soundButton);
                    }
                    clickCount = 0;
                }
            });
        });

        // Scroll to target video if specified in URL
        if (targetVideoId && targetVideoIndex > 0) {
            setTimeout(() => {
                videoItems[targetVideoIndex].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'center'
                });
                currentVideoIndex = targetVideoIndex;
                
                // Update URL to show current video without page reload
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('video', targetVideoId);
                window.history.replaceState({}, '', newUrl);
            }, 500);
        }

        // Handle window resize to ensure videos maintain full coverage
        window.addEventListener('resize', function() {
            videoPlayers.forEach(video => {
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'cover';
                video.style.objectPosition = 'center';
                
                if (isMobile) {
                    video.style.minWidth = '100vw';
                    video.style.minHeight = '100vh';
                }
            });
        });

        // Handle orientation change for mobile devices
        window.addEventListener('orientationchange', function() {
            setTimeout(() => {
                videoPlayers.forEach(video => {
                    video.style.width = '100%';
                    video.style.height = '100%';
                    video.style.objectFit = 'cover';
                    video.style.objectPosition = 'center';
                    
                    if (isMobile) {
                        video.style.minWidth = '100vw';
                        video.style.minHeight = '100vh';
                    }
                });
            }, 100);
        });

        // Function to update progress bar
        function startProgressBar(video, progressBar) {
            const updateProgress = () => {
                if (!video.paused && video.duration && video.duration > 0) {
                    const progress = (video.currentTime / video.duration) * 100;
                    progressBar.style.width = progress + '%';
                    
                    if (progress < 100 && !video.paused) {
                        requestAnimationFrame(updateProgress);
                    }
                }
            };
            
            requestAnimationFrame(updateProgress);
        }
        
        // Function to update view count
        function updateViewCount(videoId) {
            const viewedVideos = JSON.parse(sessionStorage.getItem('viewedVideos') || '[]');
            if (viewedVideos.includes(videoId)) {
                return Promise.resolve();
            }
            
            return safeFetch(`/videos/${videoId}/view`, { method: 'POST' })
                .then(response => {
                    if (response.ok) {
                        viewedVideos.push(videoId);
                        sessionStorage.setItem('viewedVideos', JSON.stringify(viewedVideos));
                    }
                })
                .catch(err => console.log('View count update failed:', err));
        }

        // Enhanced scroll handling for different devices
        const videoFeed = document.querySelector('.video-feed');
        let isScrolling = false;
        let scrollTimeout;

        // Mouse wheel for desktop
        videoFeed.addEventListener('wheel', (e) => {
            if (isMobile) return; // Skip on mobile, use touch instead
            
            e.preventDefault();
            
            if (!isScrolling) {
                isScrolling = true;
                
                const direction = e.deltaY > 0 ? 1 : -1;
                const nextIndex = Math.max(0, Math.min(videoItems.length - 1, currentVideoIndex + direction));
                
                if (nextIndex !== currentVideoIndex) {
                    scrollToVideo(nextIndex);
                }
                
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    isScrolling = false;
                }, 600);
            }
        }, { passive: false });

        // Enhanced touch handling for mobile
        let touchStartY = 0;
        let touchEndY = 0;
        let touchStartTime = 0;
        let isTouching = false;

        videoFeed.addEventListener('touchstart', (e) => {
            touchStartY = e.changedTouches[0].screenY;
            touchStartTime = Date.now();
            isTouching = true;
        }, { passive: true });

        videoFeed.addEventListener('touchmove', (e) => {
            if (!isTouching) return;
            
            const currentY = e.changedTouches[0].screenY;
            const deltaY = touchStartY - currentY;
            
            // Prevent default scroll if we're doing video navigation
            if (Math.abs(deltaY) > 50) {
                e.preventDefault();
            }
        }, { passive: false });

        videoFeed.addEventListener('touchend', (e) => {
            if (!isTouching) return;
            
            touchEndY = e.changedTouches[0].screenY;
            const touchDuration = Date.now() - touchStartTime;
            isTouching = false;
            
            handleSwipe(touchDuration);
        }, { passive: true });

        function handleSwipe(duration) {
            const swipeDistance = touchStartY - touchEndY;
            const minSwipeDistance = 80;
            const maxSwipeDuration = 800;
            
            if (Math.abs(swipeDistance) < minSwipeDistance || duration > maxSwipeDuration) {
                return;
            }
            
            const direction = swipeDistance > 0 ? 1 : -1;
            const nextIndex = Math.max(0, Math.min(videoItems.length - 1, currentVideoIndex + direction));
            
            if (nextIndex !== currentVideoIndex) {
                scrollToVideo(nextIndex);
            }
        }

        function scrollToVideo(index) {
            if (index >= 0 && index < videoItems.length) {
                videoItems[index].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'center'
                });
                currentVideoIndex = index;
                
                // Update URL with current video
                const videoId = videoItems[index].dataset.videoId;
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('video', videoId);
                window.history.replaceState({}, '', newUrl);
            }
        }

        // Handle like button clicks - FIXED with better highlighting
        const likeBtns = document.querySelectorAll('.like-btn');
        likeBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const videoId = this.getAttribute('data-video-id');
                const isLiked = this.classList.contains('liked');
                
                if (this.disabled) return;
                this.disabled = true;
                
                // IMMEDIATELY toggle the liked state for visual feedback
                this.classList.toggle('liked');
                this.classList.add('clicked');
                
                // Heart animation
                if (!isLiked) {
                    this.style.animation = 'heartBeat 0.6s ease';
                    showToast('❤️ Liked!');
                } else {
                    showToast('💔 Unliked');
                }
                
                setTimeout(() => {
                    this.classList.remove('clicked');
                    this.style.animation = '';
                }, 600);
                
                @auth
                safeRequest(`like-${videoId}`, () => {
                    return safeFetch(`/videos/${videoId}/like`, { method: 'POST' })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Failed to like');
                            }
                            return response.json();
                        })
                        .then(data => {
                        })
                        .catch(err => {
                            // Revert the like state if request failed
                            this.classList.toggle('liked');
                            showToast('❌ Failed to like video');
                        });
                }, 1000).finally(() => {
                    this.disabled = false;
                });
                @else
                // Revert the state since user needs to login
                this.classList.toggle('liked');
                this.disabled = false;
                showToast('Please login to like videos');
                setTimeout(() => {
                    window.location.href = '{{ route("login") }}';
                }, 1500);
                @endauth
            });
        });

        // Handle share button clicks - FIXED to open native share dialog
        const shareBtns = document.querySelectorAll('.share-btn');
        shareBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Visual feedback
                this.classList.add('clicked');
                setTimeout(() => this.classList.remove('clicked'), 300);
                
                const videoId = this.getAttribute('data-video-id');
                // Generate proper URL using current page URL structure
                const currentUrl = new URL(window.location.href);
                const shareUrl = `${currentUrl.protocol}//${currentUrl.host}/videos/reels?video=${videoId}`;
                
                
                // Check if Web Share API is supported
                if (navigator.share) {
                    navigator.share({
                        title: 'Check out this amazing video!',
                        text: 'Watch this awesome video!',
                        url: shareUrl
                    }).then(() => {
                        showToast('📤 Shared successfully!');
                        trackShareEvent(videoId, 'native');
                    }).catch(err => {
                        if (err.name !== 'AbortError') {
                            // Fallback to custom share dialog
                            showCustomShareDialog(shareUrl, videoId);
                        }
                    });
                } else {
                    // Fallback to custom share dialog for browsers without Web Share API
                    showCustomShareDialog(shareUrl, videoId);
                }
            });
        });

        // Custom share dialog for browsers without Web Share API
        function showCustomShareDialog(url, videoId) {
            // Create modal overlay
            const overlay = document.createElement('div');
            overlay.className = 'share-modal-overlay';
            overlay.innerHTML = `
                <div class="share-modal">
                    <div class="share-header">
                        <h3>Share this video</h3>
                        <button class="share-close">&times;</button>
                    </div>
                    <div class="share-content">
                        <div class="share-url-box">
                            <input type="text" class="share-url-input" value="${url}" readonly>
                            <button class="copy-url-btn">Copy</button>
                        </div>
                        <div class="share-options">
                            <a href="https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}" target="_blank" class="share-option facebook">
                                <i class="fab fa-facebook-f"></i> Facebook
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=Check out this amazing video!" target="_blank" class="share-option twitter">
                                <i class="fab fa-twitter"></i> Twitter
                            </a>
                            <a href="https://wa.me/?text=Check out this amazing video! ${encodeURIComponent(url)}" target="_blank" class="share-option whatsapp">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                            <a href="mailto:?subject=Check out this video&body=I thought you might like this video: ${encodeURIComponent(url)}" class="share-option email">
                                <i class="fas fa-envelope"></i> Email
                            </a>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(overlay);
            
            // Add event listeners
            overlay.querySelector('.share-close').onclick = () => {
                document.body.removeChild(overlay);
            };
            
            overlay.querySelector('.copy-url-btn').onclick = () => {
                copyToClipboard(url, videoId);
            };
            
            overlay.onclick = (e) => {
                if (e.target === overlay) {
                    document.body.removeChild(overlay);
                }
            };
            
            // Track social media clicks
            overlay.querySelectorAll('.share-option').forEach(option => {
                option.onclick = () => {
                    const platform = option.className.split(' ').pop();
                    trackShareEvent(videoId, platform);
                    setTimeout(() => {
                        document.body.removeChild(overlay);
                    }, 500);
                };
            });
        }

        // Handle comment button clicks - FIXED width and visibility
        const commentBtns = document.querySelectorAll('.comment-btn');
        const commentsSidebar = document.getElementById('comments-sidebar');
        const closeCommentsBtn = document.querySelector('.close-comments');
        
        // Ensure sidebar is hidden on page load
        commentsSidebar.classList.remove('active');
        
        commentBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                this.classList.add('clicked');
                setTimeout(() => this.classList.remove('clicked'), 300);
                
                // Show the sidebar
                commentsSidebar.classList.add('active');
                const videoId = this.getAttribute('data-video-id');
                currentVideoId = videoId;
                
                safeRequest(`comments-${videoId}`, () => loadComments(videoId), 1000);
            });
        });
        
        closeCommentsBtn.addEventListener('click', function() {
            commentsSidebar.classList.remove('active');
        });

        // Close comments when clicking outside
        document.addEventListener('click', function(e) {
            if (commentsSidebar.classList.contains('active') && 
                !commentsSidebar.contains(e.target) && 
                !e.target.closest('.comment-btn')) {
                commentsSidebar.classList.remove('active');
            }
        });

        // Copy to clipboard function - IMPROVED
        function copyToClipboard(text, videoId) {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(() => {
                    showToast('🔗 Link copied to clipboard!');
                    trackShareEvent(videoId, 'clipboard');
                }).catch(err => {
                    fallbackCopy(text, videoId);
                });
            } else {
                fallbackCopy(text, videoId);
            }
        }

        // Fallback copy method - IMPROVED
        function fallbackCopy(text, videoId) {
            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showToast('🔗 Link copied to clipboard!');
                    trackShareEvent(videoId, 'fallback');
                } else {
                    promptCopy(text);
                }
            } catch (err) {
                promptCopy(text);
            } finally {
                document.body.removeChild(textArea);
            }
        }

        // Last resort - show prompt
        function promptCopy(text) {
            const userAgent = navigator.userAgent.toLowerCase();
            if (userAgent.includes('mobile') || userAgent.includes('android') || userAgent.includes('iphone')) {
                // On mobile, show a modal-like alert
                showToast('📋 Tap and hold to copy: ' + text, 5000);
            } else {
                // On desktop, use prompt
                prompt('Copy this link (Ctrl+C):', text);
            }
        }

        // Track share event - YOUR REQUESTED FUNCTION
        function trackShareEvent(videoId, method) {
            @auth
            safeRequest(`share-${videoId}`, () => {
                return safeFetch(`/videos/${videoId}/share`, {
                    method: 'POST',
                    body: JSON.stringify({ 
                        platform: 'web',
                        method: method 
                    })
                });
            }, 2000);
            @endauth
        }

        // Handle comment posting
        const commentInput = document.getElementById('comment-input');
        const postCommentBtn = document.getElementById('post-comment');
        
        if (commentInput && postCommentBtn) {
            postCommentBtn.addEventListener('click', function() {
                const comment = commentInput.value.trim();
                if (comment && currentVideoId) {
                    postComment(currentVideoId, comment);
                }
            });
            
            commentInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    postCommentBtn.click();
                }
            });
        }

        // Function to load comments
        function loadComments(videoId) {
            const commentsList = document.getElementById('comments-list');
            commentsList.innerHTML = '<div class="loading">Loading comments...</div>';
            
            return safeFetch(`/videos/${videoId}/comments`)
                .then(response => {
                    if (!response.ok) throw new Error('Failed to load');
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.comments && data.comments.length > 0) {
                        let commentsHtml = '';
                        data.comments.forEach(comment => {
                            commentsHtml += `
                                <div class="comment-item">
                                    ${createInitialsAvatar(comment.user.name, 'comment-avatar-initials')}
                                    <div class="comment-content">
                                        <strong>${comment.user.name}</strong>
                                        <p>${comment.content}</p>
                                        <span class="comment-time">${formatTime(comment.created_at)}</span>
                                    </div>
                                </div>
                            `;
                        });
                        commentsList.innerHTML = commentsHtml;
                        updateCommentCount(videoId, data.comments.length);
                    } else {
                        commentsList.innerHTML = '<div class="no-comments"><p>No comments yet. Be the first to comment!</p></div>';
                        updateCommentCount(videoId, 0);
                    }
                })
                .catch(err => {
                    commentsList.innerHTML = '<div class="no-comments"><p>Failed to load comments.</p></div>';
                });
        }

        // Function to update comment count in UI
        function updateCommentCount(videoId, count) {
            const commentCountElement = document.querySelector(`.comment-count[data-video-id="${videoId}"]`);
            if (commentCountElement) {
                commentCountElement.textContent = count.toLocaleString();
            }
        }

        // Function to post comment
        function postComment(videoId, content) {
            @auth
            const commentInput = document.getElementById('comment-input');
            const postBtn = document.getElementById('post-comment');
            
            commentInput.disabled = true;
            postBtn.disabled = true;
            
            safeFetch(`/videos/${videoId}/comment`, {
                method: 'POST',
                body: JSON.stringify({ content: content })
            })
            .then(response => {
                if (!response.ok) throw new Error('Failed to post');
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    commentInput.value = '';
                    
                    const commentsList = document.getElementById('comments-list');
                    const noCommentsDiv = commentsList.querySelector('.no-comments');
                    if (noCommentsDiv) {
                        noCommentsDiv.remove();
                    }
                    
                    const newCommentHtml = `
                        <div class="comment-item">
                            ${createInitialsAvatar(data.comment.user.name, 'comment-avatar-initials')}
                            <div class="comment-content">
                                <strong>${data.comment.user.name}</strong>
                                <p>${data.comment.content}</p>
                                <span class="comment-time">Just now</span>
                            </div>
                        </div>
                    `;
                    
                    commentsList.insertAdjacentHTML('afterbegin', newCommentHtml);
                    
                    const currentCount = commentsList.querySelectorAll('.comment-item').length;
                    updateCommentCount(videoId, currentCount);
                    
                    showToast('Comment posted successfully!');
                }
            })
            .catch(err => {
                showToast('Failed to post comment');
            })
            .finally(() => {
                commentInput.disabled = false;
                postBtn.disabled = false;
            });
            @else
            window.location.href = '{{ route("login") }}';
            @endauth
        }

        // Utility functions
        function formatTime(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = now - date;
            
            const minutes = Math.floor(diff / 60000);
            const hours = Math.floor(diff / 3600000);
            const days = Math.floor(diff / 86400000);
            
            if (minutes < 1) return 'Just now';
            if (minutes < 60) return `${minutes}m ago`;
            if (hours < 24) return `${hours}h ago`;
            return `${days}d ago`;
        }

        function showToast(message, duration = 3000) {
            const existingToasts = document.querySelectorAll('.toast');
            existingToasts.forEach(toast => toast.remove());
            
            const toast = document.createElement('div');
            toast.className = 'toast';
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #333, #555);
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 1001;
                transition: all 0.3s ease;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                font-size: 14px;
                font-weight: 500;
                max-width: 300px;
                word-wrap: break-word;
            `;
            
            document.body.appendChild(toast);
            
            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateY(0)';
                toast.style.opacity = '1';
            }, 10);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-20px)';
                setTimeout(() => {
                    if (document.body.contains(toast)) {
                        document.body.removeChild(toast);
                    }
                }, 300);
            }, duration);
        }

        // Initialize autoplay for the target video or first video
        setTimeout(() => {
            if (videoPlayers.length > 0 && !isInitialized) {
                if (targetVideoId && targetVideoIndex >= 0) {
                    playVideo(videoPlayers[targetVideoIndex]);
                } else {
                    playVideo(videoPlayers[0]);
                }
                isInitialized = true;
            }
        }, 1000);

        // Handle page visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Pause all videos when page is hidden
                videoPlayers.forEach(video => video.pause());
            } else {
                // Resume current video when page becomes visible
                if (currentVideoIndex >= 0 && videoPlayers[currentVideoIndex]) {
                    playVideo(videoPlayers[currentVideoIndex]);
                }
            }
        });

        // Cleanup function for page unload
        window.addEventListener('beforeunload', () => {
            activeRequests.clear();
            requestCooldowns.clear();
            videoPlayers.forEach(video => {
                video.pause();
                video.currentTime = 0;
            });
        });
    });
</script>
@endpush

@push('styles')
<style>
    * {
        box-sizing: border-box;
    }
    
    body {
        overflow: hidden;
        background-color: #000;
        margin: 0;
        padding: 0;
        user-select: none;
        -webkit-user-select: none;
        -webkit-touch-callout: none;
    }
    
    .reels-container {
        display: flex;
        height: 100vh;
        height: 100dvh; /* Dynamic viewport height for mobile */
        position: relative;
        overflow: hidden;
        width: 100vw;
    }
    
    .video-feed {
        width: 100%;
        height: 100%;
        overflow-y: scroll;
        scroll-snap-type: y mandatory;
        position: relative;
        scrollbar-width: none;
        -ms-overflow-style: none;
        background: #000;
        touch-action: pan-y;
    }
    
    .video-feed::-webkit-scrollbar {
        display: none;
    }
    
    .video-item {
        height: 100%;
        width: 100%;
        position: relative;
        scroll-snap-align: start;
        scroll-snap-stop: always;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-shrink: 0;
    }
    
    .video-wrapper {
        width: 100%;
        height: 100%;
        position: relative;
        display: block;
        overflow: hidden;
    }
    
    .video-player {
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        cursor: pointer;
        background: #000;
        outline: none;
        border: none;
        display: block;
        min-width: 100%;
        min-height: 100%;
    }
    
    /* Manual play button for when autoplay fails */
    .manual-play-btn {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.7);
        border: 3px solid #fff;
        color: #fff;
        font-size: 48px;
        cursor: pointer;
        width: 100px;
        height: 100px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        z-index: 5;
        backdrop-filter: blur(10px);
    }
    
    .manual-play-btn:hover {
        background: rgba(0, 0, 0, 0.8);
        transform: translate(-50%, -50%) scale(1.1);
    }
    
    .manual-play-btn i {
        margin-left: 4px; /* Adjust play icon position */
    }
    
    /* Sound control button */
    .sound-control-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        background: linear-gradient(135deg, #4CAF50, #45a049);
        border: 2px solid #4CAF50;
        color: #fff;
        font-size: 18px;
        cursor: pointer;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 15;
        backdrop-filter: blur(10px);
        box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
    }
    
    .sound-control-btn:hover {
        background: linear-gradient(135deg, #45a049, #3d8b40);
        transform: scale(1.1);
        box-shadow: 0 6px 16px rgba(76, 175, 80, 0.5);
        border-color: #45a049;
    }
    
    .sound-control-btn:active {
        transform: scale(0.95);
    }
    
    .sound-control-btn.muted {
        background: rgba(0, 0, 0, 0.6);
        border-color: rgba(255, 255, 255, 0.8);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    }
    
    .sound-control-btn.muted:hover {
        background: rgba(0, 0, 0, 0.8);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.4);
        border-color: #fff;
    }
    
    .sound-control-btn i {
        font-size: 20px;
        line-height: 1;
    }
    
    .video-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 20px;
        background: linear-gradient(transparent, rgba(0, 0, 0, 0.8));
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: flex-end;
        z-index: 10;
        pointer-events: none;
    }
    
    .video-overlay > * {
        pointer-events: auto;
    }
    
    .video-info {
        flex: 1;
        max-width: calc(100% - 90px);
    }
    
    .user-info {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        gap: 12px;
    }
    
    .user-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 2px solid #fff;
        object-fit: cover;
        flex-shrink: 0;
    }
    
    .user-avatar-initials {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        border: 2px solid #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 16px;
        color: white;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        flex-shrink: 0;
    }
    
    .user-details {
        flex: 1;
        min-width: 0;
    }
    
    .user-details h4 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 600;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .user-details p {
        margin: 0 0 8px 0;
        font-size: 14px;
        opacity: 0.8;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .video-caption {
        margin-top: 8px;
    }
    
    .video-title {
        margin: 0 0 8px 0;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.3;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .video-description {
        font-size: 14px;
        opacity: 0.9;
        line-height: 1.4;
        margin: 0;
        overflow: hidden;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .video-actions {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 18px;
        padding-bottom: 20px;
    }
    
    .action-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 4px;
    }
    
    .action-btn {
        background: rgba(255, 255, 255, 0.15);
        border: none;
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        width: 52px;
        height: 52px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(20px);
        position: relative;
        border: 1px solid rgba(255, 255, 255, 0.1);
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
        text-decoration: none;
        touch-action: manipulation;
    }
    
    .action-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .action-btn i {
        font-size: 20px;
        line-height: 1;
        transition: all 0.3s ease;
    }
    
    .action-btn:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.25);
        transform: scale(1.05);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
    }
    
    .action-btn:active:not(:disabled),
    .action-btn.clicked {
        transform: scale(0.95);
        background: rgba(255, 255, 255, 0.3);
    }
    
    /* Like button specific styles - ENHANCED HIGHLIGHTING */
    .like-btn.liked {
        background: linear-gradient(135deg, #ff1744, #ff5722) !important;
        color: #fff !important;
        border: 2px solid #ff1744 !important;
        box-shadow: 0 6px 20px rgba(255, 23, 68, 0.6) !important;
        transform: scale(1.05) !important;
    }
    
    .like-btn.liked i {
        color: #fff !important;
        text-shadow: 0 0 8px rgba(255, 255, 255, 0.5) !important;
    }
    
    .like-btn:hover.liked {
        background: linear-gradient(135deg, #d50000, #ff1744) !important;
        transform: scale(1.1) !important;
        box-shadow: 0 8px 25px rgba(213, 0, 0, 0.7) !important;
    }
    
    .like-btn.clicked {
        transform: scale(0.9) !important;
        transition: transform 0.1s ease !important;
    }
    
    /* Enhanced heart beat animation */
    @keyframes heartBeat {
        0% { transform: scale(1); }
        14% { transform: scale(1.4); }
        28% { transform: scale(1.2); }
        42% { transform: scale(1.35); }
        70% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    /* Comment button specific styles */
    .comment-btn:hover {
        background: rgba(33, 150, 243, 0.2);
        border-color: rgba(33, 150, 243, 0.3);
    }
    
    .comment-btn.clicked {
        background: rgba(33, 150, 243, 0.3);
    }
    
    /* Share button specific styles */
    .share-btn:hover {
        background: rgba(76, 175, 80, 0.2);
        border-color: rgba(76, 175, 80, 0.3);
    }
    
    .share-btn.clicked {
        background: rgba(76, 175, 80, 0.3);
    }
    
    /* Create button specific styles */
    .create-btn {
        background: linear-gradient(135deg, #ff6b6b, #ff8a80) !important;
        border: 2px solid #fff !important;
        color: #fff !important;
        text-decoration: none !important;
        box-shadow: 0 6px 24px rgba(255, 107, 107, 0.4);
        position: relative;
        overflow: hidden;
    }
    
    .create-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
        transition: left 0.6s ease;
    }
    
    .create-btn:hover {
        background: linear-gradient(135deg, #ff5252, #ff6b6b) !important;
        transform: scale(1.1);
        box-shadow: 0 8px 32px rgba(255, 82, 82, 0.5);
    }
    
    .create-btn:hover::before {
        left: 100%;
    }
    
    .create-btn i {
        font-size: 22px;
        font-weight: bold;
    }
    
    .create-btn-item {
        margin-top: 8px;
    }
    
    .action-item span {
        font-size: 12px;
        font-weight: 600;
        text-align: center;
        min-width: 48px;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
    }
    
    .progress-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background-color: rgba(255, 255, 255, 0.3);
        z-index: 15;
    }
    
    .progress {
        height: 100%;
        background: linear-gradient(90deg, #ff6b6b, #ff8a80);
        width: 0;
        transition: width 0.1s linear;
    }
    
    /* Comments sidebar - FIXED width and hidden by default */
    .comments-sidebar {
        position: absolute;
        top: 0;
        right: -20%; /* Hide completely off-screen */
        width: 20%; /* Increased width to 20% */
        height: 100%;
        background-color: #fff;
        transition: right 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        z-index: 20;
        display: flex;
        flex-direction: column;
        box-shadow: -4px 0 20px rgba(0, 0, 0, 0.15);
        min-width: 280px; /* Increased minimum width for better readability */
        opacity: 0;
        visibility: hidden;
        transform: translateX(100%);
    }
    
    .comments-sidebar.active {
        right: 0;
        opacity: 1;
        visibility: visible;
        transform: translateX(0);
    }
    
    .comments-header {
        padding: 18px 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }
    
    .comments-header h3 {
        margin: 0;
        color: #333;
        font-size: 18px;
        font-weight: 600;
    }
    
    .close-comments {
        background: rgba(0, 0, 0, 0.05);
        border: none;
        font-size: 18px;
        cursor: pointer;
        color: #666;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.3s ease;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .close-comments:hover {
        background: rgba(0, 0, 0, 0.1);
        color: #333;
        transform: scale(1.05);
    }
    
    .comments-list {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
    }
    
    .no-comments {
        text-align: center;
        color: #888;
        padding: 40px 20px;
    }
    
    .loading {
        text-align: center;
        color: #888;
        padding: 40px 20px;
    }
    
    .comment-item {
        display: flex;
        margin-bottom: 16px;
        gap: 12px;
        padding: 12px;
        border-radius: 12px;
        background: rgba(0, 0, 0, 0.02);
        transition: background-color 0.3s ease;
    }
    
    .comment-item:hover {
        background: rgba(0, 0, 0, 0.05);
    }
    
    .comment-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        flex-shrink: 0;
        border: 2px solid #e9ecef;
        background-color: #f8f9fa;
    }
    
    .comment-avatar-initials {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 13px;
        color: white;
        flex-shrink: 0;
        border: 2px solid #e9ecef;
    }
    
    .comment-content {
        flex: 1;
    }
    
    .comment-content strong {
        color: #333;
        font-size: 14px;
        font-weight: 600;
    }
    
    .comment-content p {
        margin: 4px 0;
        color: #333;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .comment-time {
        color: #666;
        font-size: 12px;
    }
    
    .comment-form {
        padding: 20px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 12px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    }
    
    .comment-form input {
        flex: 1;
        padding: 12px 18px;
        border: 2px solid #e9ecef;
        border-radius: 25px;
        font-size: 14px;
        outline: none;
        transition: all 0.3s ease;
        background: #fff;
    }
    
    .comment-form input:focus {
        border-color: #ff6b6b;
        box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
    }
    
    .comment-form input:disabled {
        background-color: #f5f5f5;
        cursor: not-allowed;
    }
    
    .comment-form button {
        background: linear-gradient(135deg, #ff6b6b, #ff8a80);
        color: #fff;
        border: none;
        border-radius: 25px;
        padding: 12px 24px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s ease;
        box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
    }
    
    .comment-form button:hover:not(:disabled) {
        background: linear-gradient(135deg, #ff5252, #ff6b6b);
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(255, 82, 82, 0.4);
    }
    
    .comment-form button:disabled {
        background: #ccc;
        cursor: not-allowed;
        box-shadow: none;
    }
    
    .login-message {
        color: #666;
        font-size: 14px;
        margin: 0;
        text-align: center;
        flex: 1;
    }
    
    .login-message a {
        color: #ff6b6b;
        text-decoration: none;
        font-weight: 600;
    }
    
    .video-error {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0, 0, 0, 0.8);
        color: white;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        z-index: 10;
    }
    
    /* Heart beat animation */
    @keyframes heartBeat {
        0% { transform: scale(1); }
        25% { transform: scale(1.3); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
    }
    
    /* Pulse animation for buttons */
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
        70% { box-shadow: 0 0 0 10px rgba(255, 107, 107, 0); }
        100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0); }
    }
    
    .create-btn:hover {
        animation: pulse 2s infinite;
    }
    
    /* Mobile Optimizations */
    @media (max-width: 768px) {
        /* Heights are inherited from .reels-container, not re-derived from dvh here.
           Restating 100dvh on each element is what let them disagree while the URL
           bar collapsed, showing a strip of the next reel. */
        .reels-container {
            height: 100vh;
            height: 100dvh;
        }
        
        .video-feed { height: 100%; }
        
        .video-item { height: 100%; }
        
        .video-wrapper {
            width: 100%;
            height: 100%;
            border-radius: 0;
            box-shadow: none;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 0;
        }
        
        .manual-play-btn {
            width: 80px;
            height: 80px;
            font-size: 36px;
        }
        
        .sound-control-btn {
            width: 44px;
            height: 44px;
            font-size: 16px;
            top: 15px;
            right: 15px;
        }
        
        .sound-control-btn i {
            font-size: 18px;
        }
        
        .video-overlay {
            padding: 15px;
            border-radius: 0;
        }
        
        .progress-bar {
            border-radius: 0;
        }
        
        .progress {
            border-radius: 0;
        }
        
        .video-info {
            max-width: calc(100% - 75px);
        }
        
        .user-avatar,
        .user-avatar-initials {
            width: 40px;
            height: 40px;
            font-size: 14px;
        }
        
        .user-details h4 {
            font-size: 14px;
        }
        
        .user-details p {
            font-size: 13px;
        }
        
        .video-title {
            font-size: 15px;
        }
        
        .video-description {
            font-size: 13px;
        }
        
        .action-btn {
            width: 48px;
            height: 48px;
            font-size: 18px;
        }
        
        .video-actions {
            gap: 15px;
        }
        
        /* Mobile comments sidebar - full width */
        .comments-sidebar {
            width: 100%;
            right: -100%;
            min-width: unset;
        }
        
        .comment-avatar,
        .comment-avatar-initials {
            width: 32px;
            height: 32px;
            font-size: 11px;
        }
    }
    
    /* Extra small mobile devices */
    @media (max-width: 480px) {
        .video-wrapper {
            width: 100%;
            height: 100%;
        }
        
        .video-player {
            /* 100% of the item, not the viewport. A min-height in viewport units made
               the player taller than the item it sits in, so it overflowed and the
               next reel showed at the seam. object-fit already handles the crop. */
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .video-overlay {
            padding: 12px;
        }
        
        .action-btn {
            width: 44px;
            height: 44px;
            font-size: 16px;
        }
        
        .create-btn i {
            font-size: 18px;
        }
        
        .video-actions {
            gap: 12px;
        }
        
        .manual-play-btn {
            width: 70px;
            height: 70px;
            font-size: 28px;
        }
        
        .sound-control-btn {
            width: 40px;
            height: 40px;
            font-size: 14px;
            top: 12px;
            right: 12px;
        }
        
        .sound-control-btn i {
            font-size: 16px;
        }
        
        .user-avatar,
        .user-avatar-initials {
            width: 36px;
            height: 36px;
            font-size: 12px;
        }
        
        .user-details h4 {
            font-size: 13px;
        }
        
        .user-details p {
            font-size: 12px;
        }
        
        .video-title {
            font-size: 14px;
        }
        
        .video-description {
            font-size: 12px;
        }
    }
    
    /* Tablet specific adjustments */
    @media (min-width: 769px) and (max-width: 1024px) {
        .video-wrapper {
            width: 100%;
            height: 100%;
            border-radius: 0;
            box-shadow: none;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 0;
            min-width: 100%;
            min-height: 100%;
        }
        
        .video-overlay {
            border-radius: 0;
        }
        
        .progress-bar {
            border-radius: 0;
        }
        
        /* Tablet comments sidebar - 30% width */
        .comments-sidebar {
            width: 30%;
            right: -30%;
            min-width: 320px;
            border-radius: 12px 0 0 12px;
        }
        
        .comments-header {
            border-radius: 12px 0 0 0;
        }
        
        .comment-form {
            border-radius: 0 0 0 12px;
        }
    }
    
    /* Desktop specific adjustments */
    @media (min-width: 1025px) {
        .video-wrapper {
            width: 100%;
            height: 100%;
            border-radius: 0;
            box-shadow: none;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            border-radius: 0;
            min-width: 100%;
            min-height: 100%;
        }
        
        .video-overlay {
            border-radius: 0;
        }
        
        .progress-bar {
            border-radius: 0;
        }
        
        /* Desktop comments sidebar - keep 20% and ensure it's hidden */
        .comments-sidebar {
            border-radius: 12px 0 0 12px;
            right: -20%;
            width: 20%;
            min-width: 280px;
        }
        
        .comments-header {
            border-radius: 12px 0 0 0;
        }
        
        .comment-form {
            border-radius: 0 0 0 12px;
        }
    }
    
    /* Landscape orientation on mobile */
    @media (max-width: 768px) and (orientation: landscape) {
        .video-wrapper {
            width: 100%;
            height: 100%;
        }
        
        .video-player {
            /* 100% of the item, not the viewport. A min-height in viewport units made
               the player taller than the item it sits in, so it overflowed and the
               next reel showed at the seam. object-fit already handles the crop. */
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
        }
        
        .video-overlay {
            padding: 10px 15px;
        }
        
        .video-actions {
            gap: 10px;
            padding-bottom: 10px;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            font-size: 14px;
        }
        
        .user-avatar,
        .user-avatar-initials {
            width: 32px;
            height: 32px;
            font-size: 12px;
        }
    }
    
    /* High DPI displays */
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .video-player {
            image-rendering: -webkit-optimize-contrast;
            image-rendering: crisp-edges;
        }
    }
    
    /* Reduced motion preferences */
    @media (prefers-reduced-motion: reduce) {
        .action-btn,
        .video-overlay,
        .comments-sidebar,
        .manual-play-btn {
            transition: none;
        }
        
        .create-btn:hover {
            animation: none;
        }
        
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
        }
        
        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 107, 107, 0.7); }
        }
    }
    
    /* Share Modal Styles */
    .share-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 2000;
        backdrop-filter: blur(5px);
    }
    
    .share-modal {
        background: white;
        border-radius: 16px;
        width: 90%;
        max-width: 500px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        overflow: hidden;
        animation: shareModalIn 0.3s ease-out;
    }
    
    @keyframes shareModalIn {
        from {
            opacity: 0;
            transform: scale(0.8) translateY(20px);
        }
        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }
    
    .share-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    .share-header h3 {
        margin: 0;
        font-size: 18px;
        font-weight: 600;
    }
    
    .share-close {
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.3s ease;
    }
    
    .share-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    
    .share-content {
        padding: 20px;
    }
    
    .share-url-box {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }
    
    .share-url-input {
        flex: 1;
        border: none;
        background: transparent;
        font-size: 14px;
        color: #333;
        outline: none;
    }
    
    .copy-url-btn {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .copy-url-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    
    .share-options {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
    }
    
    .share-option {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        border-radius: 8px;
        text-decoration: none;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
        font-size: 14px;
    }
    
    .share-option:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
    }
    
    .share-option.facebook {
        background: linear-gradient(135deg, #1877f2, #42a5f5);
    }
    
    .share-option.twitter {
        background: linear-gradient(135deg, #1da1f2, #0d8bf0);
    }
    
    .share-option.whatsapp {
        background: linear-gradient(135deg, #25d366, #128c7e);
    }
    
    .share-option.email {
        background: linear-gradient(135deg, #ea4335, #d33b2c);
    }
    
    .share-option i {
        font-size: 18px;
    }
    
    /* Mobile responsive */
    @media (max-width: 480px) {
        .share-modal {
            width: 95%;
            margin: 20px;
        }
        
        .share-options {
            grid-template-columns: 1fr;
        }
        
        .share-header {
            padding: 16px;
        }
        
        .share-content {
            padding: 16px;
        }
    }
    @media (prefers-color-scheme: dark) {
        .comments-sidebar {
            background-color: #1a1a1a;
            color: #fff;
        }
        
        .comments-header {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border-bottom-color: #333;
        }
        
        .comments-header h3 {
            color: #fff;
        }
        
        .comment-form {
            background: linear-gradient(135deg, #2a2a2a, #1a1a1a);
            border-top-color: #333;
        }
        
        .comment-form input {
            background: #333;
            border-color: #444;
            color: #fff;
        }
        
        .comment-form input:focus {
            border-color: #ff6b6b;
        }
        
        .comment-item {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .comment-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .comment-content strong,
        .comment-content p {
            color: #fff;
        }
        
        .comment-time {
            color: #aaa;
        }
        
        .no-comments,
        .loading {
            color: #aaa;
        }
    }
</style>
@endpush

@include('elements.reels-scrubber')
@include('elements.reels-hud')
@include('elements.reels-prefetch')
@endsection