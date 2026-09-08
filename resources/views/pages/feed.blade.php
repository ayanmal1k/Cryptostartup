@extends('layouts.generic')
@section('page_title', __('Video Reels'))

{{-- Page specific CSS --}}
@section('styles')
    {!!
        Minify::stylesheet([
            '/libs/swiper/swiper-bundle.min.css',
            '/libs/photoswipe/dist/photoswipe.css',
            '/css/pages/checkout.css',
            '/libs/photoswipe/dist/default-skin/default-skin.css',
            '/css/pages/feed.css',
            '/css/posts/post.css',
            '/css/pages/search.css',
         ])->withFullUrl()
    !!}
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@stop

{{-- Page specific JS --}}
@section('scripts')
    {!!
        Minify::javascript([
            '/js/PostsPaginator.js',
            '/js/CommentsPaginator.js',
            '/js/Post.js',
            '/js/SuggestionsSlider.js',
            '/js/pages/lists.js',
            '/js/pages/feed.js',
            '/js/pages/checkout.js',
            '/libs/swiper/swiper-bundle.min.js',
            '/js/plugins/media/photoswipe.js',
            '/libs/photoswipe/dist/photoswipe-ui-default.min.js',
            '/js/plugins/media/mediaswipe.js',
            '/js/plugins/media/mediaswipe-loader.js',
         ])->withFullUrl()
    !!}
    
    <!-- Reels JavaScript -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const videoItems = document.querySelectorAll('.video-item');
            const videoPlayers = document.querySelectorAll('.video-player');
            let currentVideoIndex = 0;
            let isScrolling = false;
            
            // Initialize all videos
            videoPlayers.forEach((video, index) => {
                video.setAttribute('playsinline', '');
                video.setAttribute('webkit-playsinline', '');
                video.muted = true; // Start muted for autoplay
                video.load();
                
                // Click to play/pause
                video.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (this.paused) {
                        this.play();
                    } else {
                        this.pause();
                    }
                });
                
                // Double click to toggle sound
                let clickCount = 0;
                video.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    clickCount++;
                    setTimeout(() => {
                        if (clickCount === 2) {
                            toggleSound(this);
                        }
                        clickCount = 0;
                    }, 300);
                });
            });
            
            videoPlayers.forEach((video, index) => {
    video.setAttribute('playsinline', '');
    video.setAttribute('webkit-playsinline', '');
    video.muted = true; // Start muted for autoplay
    video.load();
    
    // Click to play/pause
    video.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (this.paused) {
            this.play();
        } else {
            this.pause();
        }
    });
    
    // Double click to toggle sound
    let clickCount = 0;
    video.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        clickCount++;
        setTimeout(() => {
            if (clickCount === 2) {
                toggleSound(this);
            }
            clickCount = 0;
        }, 300);
    });
});


            let viewedVideos = new Set();
            // Intersection Observer for autoplay
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        const video = entry.target;
        if (entry.isIntersecting && entry.intersectionRatio > 0.7) {
            pauseAllVideosExcept(video);
            video.currentTime = 0;
            video.play().catch(e => console.log('Autoplay failed:', e));
            
             // 🔥 INCREMENT VIEWS WHEN VIDEO STARTS PLAYING
            const videoId = video.closest('.video-item').getAttribute('data-video-id');
            if (videoId) {
                incrementVideoViews(videoId);
            }

            // Update progress bar
            const progressBar = video.closest('.video-item').querySelector('.progress');
            if (progressBar) {
                startProgressBar(video, progressBar);
            }
        } else {
            video.pause();
        }
    });
}, {
    threshold: [0.7],
    rootMargin: '-10% 0px'
});
            
            // Observe all videos
            videoPlayers.forEach(video => observer.observe(video));

            function handleVideoUrlParameter() {
                const urlParams = new URLSearchParams(window.location.search);
                const videoId = urlParams.get('video');
                
                if (videoId) {
                    // Find the video item with the specified ID
                    const targetVideoItem = document.querySelector(`.video-item[data-video-id="${videoId}"]`);
                    
                    if (targetVideoItem) {
                        // Get the index of this video in the list
                        const allVideoItems = document.querySelectorAll('.video-item');
                        const videoIndex = Array.from(allVideoItems).indexOf(targetVideoItem);
                        
                        if (videoIndex !== -1) {
                            // Update current video index
                            currentVideoIndex = videoIndex;
                            
                            // Scroll to the specific video immediately
                            setTimeout(() => {
                                targetVideoItem.scrollIntoView({ 
                                    behavior: 'instant',
                                    block: 'start'
                                });
                                
                                // Ensure the video starts playing
                                const video = targetVideoItem.querySelector('.video-player');
                                if (video) {
                                    pauseAllVideosExcept(video);
                                    video.currentTime = 0;
                                    video.play().catch(e => console.log('Autoplay failed:', e));
                                    
                                    // Update progress bar
                                    const progressBar = targetVideoItem.querySelector('.progress');
                                    if (progressBar) {
                                        startProgressBar(video, progressBar);
                                    }
                                }
                            }, 100);
                        }
                    } else {
                        console.log(`Video with ID ${videoId} not found`);
                    }
                }
            }
            
            // Call this function after all videos are initialized
            handleVideoUrlParameter();
            
            // Touch/scroll navigation
            let touchStartY = 0;
            let touchEndY = 0;
            
            document.addEventListener('touchstart', e => {
                touchStartY = e.changedTouches[0].screenY;
            });
            
            document.addEventListener('touchend', e => {
                touchEndY = e.changedTouches[0].screenY;
                handleSwipe();
            });
            
            // Mouse wheel navigation
            document.addEventListener('wheel', e => {
                if (document.body.classList.contains('comments-open') ||
                    document.body.classList.contains('mobile-sidebar-open') ||
                    document.body.classList.contains('mobile-search-open')) return;
                if (isScrolling) return;
                e.preventDefault();
                
                const direction = e.deltaY > 0 ? 1 : -1;
                scrollToVideo(currentVideoIndex + direction);
                
                isScrolling = true;
                setTimeout(() => isScrolling = false, 800);
            }, { passive: false });
            
            function handleSwipe() {
                if (document.body.classList.contains('comments-open') ||
                    document.body.classList.contains('mobile-sidebar-open') ||
                    document.body.classList.contains('mobile-search-open')) return;
                const swipeDistance = touchStartY - touchEndY;
                if (Math.abs(swipeDistance) < 50) return;
                
                const direction = swipeDistance > 0 ? 1 : -1;
                scrollToVideo(currentVideoIndex + direction);
            }
            
            function scrollToVideo(index) {
                if (index >= 0 && index < videoItems.length) {
                    videoItems[index].scrollIntoView({ 
                        behavior: 'smooth',
                        block: 'start'
                    });
                    currentVideoIndex = index;
                }
            }
            
            function pauseAllVideosExcept(currentVideo) {
                videoPlayers.forEach(video => {
                    if (video !== currentVideo) {
                        video.pause();
                        video.currentTime = 0;
                    }
                });
            }
            
            function toggleSound(video) {
                video.muted = !video.muted;
                const soundBtn = video.closest('.video-wrapper').querySelector('.sound-control-btn');
                if (soundBtn) {
                    soundBtn.innerHTML = video.muted ? 
                        '<i class="fas fa-volume-mute"></i>' : 
                        '<i class="fas fa-volume-up"></i>';
                }
            }
            
            // The seek bar keeps itself in sync from the video's own timeupdate event
            // (elements/reels-scrubber). This used to drive the fill width directly from
            // a requestAnimationFrame loop, which moved the fill but not the drag handle,
            // so the two would drift apart during playback. Kept as a no-op so the three
            // existing call sites do not need to change.
            function startProgressBar() {}
            
            // Add sound control buttons
            videoPlayers.forEach(video => {
                const wrapper = video.closest('.video-wrapper');
                const soundBtn = document.createElement('button');
                soundBtn.className = 'sound-control-btn';
                soundBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
                soundBtn.onclick = () => toggleSound(video);
                wrapper.appendChild(soundBtn);
            });
            
            // Like button functionality with API call
            document.querySelectorAll('.like-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const videoId = this.getAttribute('data-video-id');
                    const countSpan = this.closest('.action-item').querySelector('.action-count');
                    const icon = this.querySelector('i');
                    let count = parseInt(countSpan.textContent);
                    
                    // Toggle UI immediately for better UX
                    const wasLiked = this.classList.contains('liked');
                    
                    if (!wasLiked) {
                        this.classList.add('liked');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        this.style.color = '#ff1744';
                        countSpan.textContent = count + 1;
                    } else {
                        this.classList.remove('liked');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        this.style.color = '#fff';
                        countSpan.textContent = count - 1;
                    }
                    
                    // Make API call
                    fetch(`/videos/${videoId}/like`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update with actual count from server
                            countSpan.textContent = data.likes_count;
                            
                            if (data.is_liked) {
                                this.classList.add('liked');
                                icon.classList.remove('far');
                                icon.classList.add('fas');
                                this.style.color = '#ff1744';
                            } else {
                                this.classList.remove('liked');
                                icon.classList.remove('fas');
                                icon.classList.add('far');
                                this.style.color = '#fff';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Revert UI changes on error
                        if (wasLiked) {
                            this.classList.add('liked');
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            this.style.color = '#ff1744';
                            countSpan.textContent = count;
                        } else {
                            this.classList.remove('liked');
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            this.style.color = '#fff';
                            countSpan.textContent = count;
                        }
                    });
                });
            });
            
            // Comments bottom sheet functionality
            const commentsSidebar = document.getElementById('comments-sidebar');
            const commentsOverlay = document.getElementById('comments-overlay');
            const closeCommentsBtn = document.querySelector('.close-comments');

            function openComments() {
                commentsSidebar.classList.add('active');
                if (commentsOverlay) commentsOverlay.classList.add('active');
                document.body.classList.add('comments-open');
                const videoFeed = document.querySelector('.video-feed');
                if (videoFeed) {
                    videoFeed.dataset.lockedScrollTop = String(videoFeed.scrollTop);
                    videoFeed.style.overflow = 'hidden';
                }
            }

            function closeComments() {
                commentsSidebar.classList.remove('active');
                if (commentsOverlay) commentsOverlay.classList.remove('active');
                document.body.classList.remove('comments-open');
                const videoFeed = document.querySelector('.video-feed');
                if (videoFeed) {
                    videoFeed.style.overflow = '';
                    if (videoFeed.dataset.lockedScrollTop != null) {
                        videoFeed.scrollTop = parseFloat(videoFeed.dataset.lockedScrollTop) || 0;
                    }
                }
            }

            const blockFeedScroll = function(e) {
                if (!document.body.classList.contains('comments-open')) return;
                const inCommentsList = e.target.closest && e.target.closest('.comments-list');
                const inCommentInput = e.target.closest && (
                    e.target.closest('.comment-form') ||
                    e.target.closest('#comment-input')
                );
                if (inCommentsList || inCommentInput) return;
                e.preventDefault();
            };
            document.addEventListener('wheel', blockFeedScroll, { passive: false, capture: true });
            document.addEventListener('touchmove', blockFeedScroll, { passive: false, capture: true });

            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    openComments();
                    const videoId = this.getAttribute('data-video-id');
                    loadComments(videoId);
                });
            });

            if (closeCommentsBtn) {
                closeCommentsBtn.addEventListener('click', closeComments);
            }
            if (commentsOverlay) {
                commentsOverlay.addEventListener('click', closeComments);
            }
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && commentsSidebar.classList.contains('active')) {
                    closeComments();
                }
            });

            // Drag-to-close (Instagram-style) on the grabber / header
            if (commentsSidebar) {
                let dragStartY = 0;
                let dragCurrentY = 0;
                let isDragging = false;

                const dragStart = function(e) {
                    // Ignore drags that begin on the close button
                    if (e.target.closest('.close-comments')) return;
                    isDragging = true;
                    dragStartY = e.touches ? e.touches[0].clientY : e.clientY;
                    dragCurrentY = dragStartY;
                    commentsSidebar.classList.add('dragging');
                };

                const dragMove = function(e) {
                    if (!isDragging) return;
                    const y = e.touches ? e.touches[0].clientY : e.clientY;
                    dragCurrentY = y;
                    const delta = Math.max(0, y - dragStartY); // only allow dragging down
                    commentsSidebar.style.transform = 'translateY(' + delta + 'px)';
                    if (e.cancelable) e.preventDefault();
                };

                const dragEnd = function() {
                    if (!isDragging) return;
                    isDragging = false;
                    commentsSidebar.classList.remove('dragging');
                    commentsSidebar.style.transform = '';

                    const delta = dragCurrentY - dragStartY;
                    const threshold = Math.min(140, commentsSidebar.offsetHeight * 0.25);
                    if (delta > threshold) {
                        closeComments();
                    }
                };

                document.querySelectorAll('[data-comments-drag]').forEach(function(handle) {
                    handle.addEventListener('mousedown', dragStart);
                    handle.addEventListener('touchstart', dragStart, { passive: true });
                });
                document.addEventListener('mousemove', dragMove);
                document.addEventListener('touchmove', dragMove, { passive: false });
                document.addEventListener('mouseup', dragEnd);
                document.addEventListener('touchend', dragEnd);
                document.addEventListener('touchcancel', dragEnd);
            }

            // Quick emoji reactions -> prefill composer
            document.querySelectorAll('.reaction-emoji').forEach(btn => {
                btn.addEventListener('click', function() {
                    const input = document.getElementById('comment-input');
                    if (input) {
                        input.value += this.getAttribute('data-emoji');
                        input.focus();
                    }
                });
            });

            // Toggle like heart on individual comments (client-side only)
            document.addEventListener('click', function(e) {
                const like = e.target.closest('.comment-like');
                if (!like) return;
                like.classList.toggle('liked');
                const countEl = like.querySelector('span');
                if (countEl) {
                    let n = parseInt(countEl.textContent) || 0;
                    countEl.textContent = like.classList.contains('liked') ? n + 1 : Math.max(0, n - 1);
                }
            });
            
            // Share button functionality
            document.querySelectorAll('.share-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const videoId = this.getAttribute('data-video-id');
                    const countSpan = this.closest('.action-item').querySelector('.action-count');
                    let count = parseInt(countSpan.textContent);
                    
                    // Update count immediately
                    countSpan.textContent = count + 1;
                    
                    // Make API call
                    fetch(`/videos/${videoId}/share`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            countSpan.textContent = data.shares_count;
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        countSpan.textContent = count; // Revert on error
                    });
                    
                    // Native share functionality
                    const videoUrl = `${window.location.origin}/videos/${videoId}`;
                    if (navigator.share) {
                        navigator.share({
                            title: 'Check out this video!',
                            url: videoUrl
                        });
                    } else {
                        // Fallback copy to clipboard
                        navigator.clipboard.writeText(videoUrl);
                        showNotification('Link copied to clipboard!');
                    }
                });
            });
            
            // Repost button functionality (Twitter-like)
            document.querySelectorAll('.repost-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const videoId = this.getAttribute('data-video-id');
                    const countSpan = this.closest('.action-item').querySelector('.action-count');
                    const icon = this.querySelector('i');
                    let count = parseInt(countSpan.textContent);
                    
                    // Check if already reposted
                    const wasReposted = this.classList.contains('reposted');
                    
                    if (!wasReposted) {
                        if (confirm('Repost this video to your profile?')) {
                            this.classList.add('reposted');
                            this.style.color = '#17bf63';
                            countSpan.textContent = count + 1;
                            
                            // Make API call
                            fetch(`/videos/${videoId}/repost`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    countSpan.textContent = data.reposts_count;
                                    showNotification('Video reposted successfully!');
                                } else {
                                    // Revert on error
                                    this.classList.remove('reposted');
                                    this.style.color = '#fff';
                                    countSpan.textContent = count;
                                    showNotification(data.message || 'Failed to repost video');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                // Revert on error
                                this.classList.remove('reposted');
                                this.style.color = '#fff';
                                countSpan.textContent = count;
                                showNotification('Failed to repost video');
                            });
                        }
                    } else {
                        // Undo repost
                        if (confirm('Remove this repost?')) {
                            this.classList.remove('reposted');
                            this.style.color = '#fff';
                            countSpan.textContent = count - 1;
                            
                            // Make API call to undo repost
                            fetch(`/videos/${videoId}/unrepost`, {
                                method: 'DELETE',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                                }
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    countSpan.textContent = data.reposts_count;
                                    showNotification('Repost removed successfully!');
                                } else {
                                    // Revert on error
                                    this.classList.add('reposted');
                                    this.style.color = '#17bf63';
                                    countSpan.textContent = count;
                                    showNotification(data.message || 'Failed to remove repost');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                // Revert on error
                                this.classList.add('reposted');
                                this.style.color = '#17bf63';
                                countSpan.textContent = count;
                                showNotification('Failed to remove repost');
                            });
                        }
                    }
                });
            });
            
            // Upload button functionality
            document.querySelectorAll('.upload-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.location.href = '/videos/create';
                });
            });
            
            // Notification function
            function showNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'notification';
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('show');
                }, 100);
                
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }
            
            // ===== Profile Share Functionality =====
            const profileModal = document.getElementById('profile-modal');
            const profileModalClose = document.querySelector('.profile-modal-close');
            
            // Profile click functionality
            document.querySelectorAll('.user-info').forEach(userInfo => {
                userInfo.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const userId = this.getAttribute('data-user-id');
                    const usernameForUrl = this.getAttribute('data-user-username') || (this.querySelector('.user-details p') && (this.querySelector('.user-details p').textContent || '').replace(/^@/, '').trim()) || '';
                    const userName = this.querySelector('.user-details h4').textContent;
                    const userUsername = this.querySelector('.user-details p').textContent;
                    const userAvatar = this.querySelector('.user-avatar-initials').style.background;
                    const userInitials = this.querySelector('.user-avatar-initials').textContent;
                    
                    openProfileModal(userId, userName, userUsername, userAvatar, userInitials, usernameForUrl);
                });
            });
            
            function openProfileModal(userId, userName, userUsername, userAvatar, userInitials, usernameForUrl) {
                if (profileModal) {
                    // Update modal content
                    document.getElementById('profile-modal-avatar').style.background = userAvatar;
                    document.getElementById('profile-modal-avatar').textContent = userInitials;
                    document.getElementById('profile-modal-name').textContent = userName;
                    document.getElementById('profile-modal-username').textContent = userUsername;
                    
                    // Username for URLs (from data-user-username or strip @ from display text)
                    const urlUsername = (usernameForUrl || (userUsername || '').replace(/^@/, '').trim());
                    
                    // Set user ID and username for sharing / view profile
                    document.getElementById('share-profile-btn').setAttribute('data-user-id', userId);
                    document.getElementById('share-profile-btn').setAttribute('data-username', urlUsername);
                    document.getElementById('view-profile-btn').setAttribute('data-user-id', userId);
                    document.getElementById('view-profile-btn').setAttribute('data-username', urlUsername);
                    
                    profileModal.classList.add('active');
                }
            }
            
            if (profileModalClose) {
                profileModalClose.addEventListener('click', function() {
                    profileModal.classList.remove('active');
                });
            }
            
            // Close profile modal when clicking outside
            if (profileModal) {
                document.addEventListener('click', function(e) {
                    if (profileModal.classList.contains('active') && 
                        e.target === profileModal) {
                        profileModal.classList.remove('active');
                    }
                });
            }
            
            // Share profile functionality (use username for URL so shared links work)
            document.getElementById('share-profile-btn').addEventListener('click', function() {
                const userId = this.getAttribute('data-user-id');
                const userName = document.getElementById('profile-modal-name').textContent;
                const username = this.getAttribute('data-username') || (document.getElementById('profile-modal-username').textContent || '').replace(/^@/, '').trim();
                const profileUrl = username ? window.location.origin + '/' + encodeURIComponent(username) : window.location.origin + '/' + encodeURIComponent(userName);
                
                if (navigator.share) {
                    navigator.share({
                        title: `Check out ${userName}'s profile!`,
                        text: `See amazing videos from ${userName}`,
                        url: profileUrl
                    }).then(() => {
                        console.log('Profile shared successfully');
                    }).catch(err => {
                        console.log('Error sharing profile:', err);
                        fallbackShareProfile(profileUrl, userName);
                    });
                } else {
                    fallbackShareProfile(profileUrl, userName);
                }
            });
            
            function fallbackShareProfile(profileUrl, userName) {
                navigator.clipboard.writeText(profileUrl).then(() => {
                    showShareNotification(`${userName}'s profile link copied to clipboard!`);
                }).catch(err => {
                    console.error('Could not copy text: ', err);
                    showShareNotification('Unable to copy link. Please try again.');
                });
            }
            
            function showShareNotification(message) {
                const notification = document.createElement('div');
                notification.className = 'share-notification';
                notification.textContent = message;
                document.body.appendChild(notification);
                
                setTimeout(() => {
                    notification.classList.add('show');
                }, 100);
                
                setTimeout(() => {
                    notification.classList.remove('show');
                    setTimeout(() => {
                        document.body.removeChild(notification);
                    }, 300);
                }, 3000);
            }
            
            // View profile functionality (use username for URL, not display name)
            document.getElementById('view-profile-btn').addEventListener('click', function() {
                const username = this.getAttribute('data-username');
                if (username) {
                    window.location.href = '/' + encodeURIComponent(username);
                }
            });
            
// 🔥 ADD THIS NEW FUNCTION FOR VIEWS TRACKING
function incrementVideoViews(videoId) {
    console.log('📈 Incrementing views for video:', videoId);
    
    fetch(`/videos/${videoId}/views`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Views incremented successfully:', data.views_count);
            console.log('📊 Response data:', data);
        } else {
            console.error('❌ Views increment failed:', data);
        }
    })
    .catch(error => {
        console.error('❌ Error incrementing views:', error);
    });
}
            
            // Update header profile link dynamically
            const headerProfileLink = document.querySelector('a[href="/profile"]');
            if (headerProfileLink) {
                const currentUserName = '{{ Auth::user()->name ?? "Profile" }}';
                headerProfileLink.href = '/' + currentUserName;
            }
            
            // ===== Search Functionality =====
            const searchBtn = document.getElementById('search-btn');
            const searchModal = document.getElementById('search-modal');
            const searchModalClose = document.querySelector('.search-modal-close');
            const searchInput = document.getElementById('search-input');
            const searchResults = document.getElementById('search-results');
            
            if (searchBtn && searchModal) {
                searchBtn.addEventListener('click', function() {
                    searchModal.classList.add('active');
                    if (searchInput) {
                        setTimeout(() => searchInput.focus(), 300);
                    }
                });
            }
            
            if (searchModalClose) {
                searchModalClose.addEventListener('click', function() {
                    searchModal.classList.remove('active');
                });
            }
            
            // Close search modal when clicking outside
            if (searchModal) {
                document.addEventListener('click', function(e) {
                    if (searchModal.classList.contains('active') && 
                        e.target === searchModal) {
                        searchModal.classList.remove('active');
                    }
                });
            }
            
            // ===== Comments Header Close Button =====
            const commentsCloseHeader = document.getElementById('comments-close-header');
            
            // Show/hide header close button when comments open/close
            const originalCommentsToggle = () => {
                if (commentsSidebar && commentsCloseHeader) {
                    const observer = new MutationObserver(function(mutations) {
                        mutations.forEach(function(mutation) {
                            if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                                if (commentsSidebar.classList.contains('active')) {
                                    commentsCloseHeader.classList.add('active');
                                } else {
                                    commentsCloseHeader.classList.remove('active');
                                }
                            }
                        });
                    });
                    
                    observer.observe(commentsSidebar, {
                        attributes: true,
                        attributeFilter: ['class']
                    });
                }
            };
            
            originalCommentsToggle();
            
            if (commentsCloseHeader) {
                commentsCloseHeader.addEventListener('click', function() {
                    if (commentsSidebar) {
                        commentsSidebar.classList.remove('active');
                    }
                });
            }
            
            // Search input functionality with API
            if (searchInput && searchResults) {
                let searchTimeout;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    if (query.length > 2) {
                        searchTimeout = setTimeout(() => {
                            performSearch(query);
                        }, 500);
                    } else {
                        searchResults.innerHTML = '<div class="search-placeholder">Type at least 3 characters to search...</div>';
                    }
                });
            }
            
            function performSearch(query) {
                if (!searchResults) return;
                
                searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
                
                // Make API call to search
                fetch(`/search/videos?q=${encodeURIComponent(query)}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.videos.length > 0) {
                        const resultsHtml = data.videos.map((video, index) => `
                            <div class="search-result-item" onclick="goToVideo('${video.id}')">
                                <div class="search-result-info">
                                    <h4>${video.title}</h4>
                                    <p>By ${video.user.name}</p>
                                </div>
                            </div>
                        `).join('');
                        searchResults.innerHTML = resultsHtml;
                    } else {
                        searchResults.innerHTML = '<div class="no-search-results">No videos found for "' + query + '"</div>';
                    }
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="no-search-results">Search failed. Please try again.</div>';
                });
            }
            
            // Navigate to specific video
            window.goToVideo = function(videoId) {
                const videoElement = document.querySelector(`.video-item[data-video-id="${videoId}"]`);
                if (videoElement) {
                    videoElement.scrollIntoView({ behavior: 'smooth' });
                    if (searchModal) {
                        searchModal.classList.remove('active');
                    }
                }
            }
            
            // Comments functionality
            function loadComments(videoId) {
                const commentsList = document.getElementById('comments-list');
                commentsList.innerHTML = '<div class="loading">Loading comments...</div>';
                
                // Fetch comments from API
                fetch(`/videos/${videoId}/comments`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.comments.length > 0) {
                        const commentsHtml = data.comments.map(comment => `
                            <div class="comment-item">
                                <div class="comment-avatar-initials" style="background: ${getRandomColor()};">${getUserInitials(comment.user.name)}</div>
                                <div class="comment-content">
                                    <div class="comment-head">
                                        <strong>${comment.user.name}</strong>
                                        <span class="comment-time">${formatTime(comment.created_at)}</span>
                                    </div>
                                    <p>${comment.content}</p>
                                    <div class="comment-actions">
                                        <span class="comment-reply">Reply</span>
                                    </div>
                                </div>
                                <div class="comment-like">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
                                    <span>0</span>
                                </div>
                            </div>
                        `).join('');
                        commentsList.innerHTML = commentsHtml;
                    } else {
                        commentsList.innerHTML = '<div class="no-comments"><p>No comments yet. Be the first to comment!</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading comments:', error);
                    commentsList.innerHTML = '<div class="no-comments"><p>Failed to load comments.</p></div>';
                });
            }
            
            // Helper functions
            function getRandomColor() {
                const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'];
                return colors[Math.floor(Math.random() * colors.length)];
            }
            
            function getUserInitials(name) {
                const parts = name.split(' ');
                return parts.length >= 2 ? 
                    (parts[0][0] + parts[1][0]).toUpperCase() : 
                    name.substring(0, 2).toUpperCase();
            }
            
            function formatTime(dateString) {
                const date = new Date(dateString);
                const now = new Date();
                const diff = now - date;
                
                const minutes = Math.floor(diff / (1000 * 60));
                const hours = Math.floor(diff / (1000 * 60 * 60));
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                
                if (minutes < 60) {
                    return `${minutes}m ago`;
                } else if (hours < 24) {
                    return `${hours}h ago`;
                } else {
                    return `${days}d ago`;
                }
            }
            
            // Post comment functionality
            const commentInput = document.getElementById('comment-input');
            const postCommentBtn = document.getElementById('post-comment');
            let currentVideoIdForComments = null;
            
            // Update current video ID when comments are opened
            document.querySelectorAll('.comment-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    currentVideoIdForComments = this.getAttribute('data-video-id');
                });
            });
            
            if (commentInput && postCommentBtn) {
                postCommentBtn.addEventListener('click', function() {
                    const comment = commentInput.value.trim();
                    if (comment && currentVideoIdForComments) {
                        // Disable button to prevent double submission
                        postCommentBtn.disabled = true;
                        
                        // Make API call to post comment
                        fetch(`/videos/${currentVideoIdForComments}/comments`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                content: comment
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Add comment to list
                                const commentsList = document.getElementById('comments-list');
                                const newComment = document.createElement('div');
                                newComment.className = 'comment-item';
                                newComment.innerHTML = `
                                    <div class="comment-avatar-initials" style="background: ${getRandomColor()};">${getUserInitials(data.comment.user.name)}</div>
                                    <div class="comment-content">
                                        <div class="comment-head">
                                            <strong>${data.comment.user.name}</strong>
                                            <span class="comment-time">Just now</span>
                                        </div>
                                        <p>${data.comment.content}</p>
                                        <div class="comment-actions">
                                            <span class="comment-reply">Reply</span>
                                        </div>
                                    </div>
                                    <div class="comment-like">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
                                        <span>0</span>
                                    </div>
                                `;
                                
                                // Remove "no comments" message if exists
                                const noComments = commentsList.querySelector('.no-comments');
                                if (noComments) {
                                    noComments.remove();
                                }
                                
                                commentsList.insertBefore(newComment, commentsList.firstChild);
                                commentInput.value = '';
                                
                                // Update comment count in UI
                                const currentVideo = document.querySelector(`.video-item[data-video-id="${currentVideoIdForComments}"]`);
                                const commentCountSpan = currentVideo.querySelector('.comment-btn').closest('.action-item').querySelector('.action-count');
                                if (commentCountSpan) {
                                    const count = parseInt(commentCountSpan.textContent);
                                    commentCountSpan.textContent = count + 1;
                                }
                                
                                showNotification('Comment posted successfully!');
                            } else {
                                showNotification(data.message || 'Failed to post comment');
                            }
                        })
                        .catch(error => {
                            console.error('Error posting comment:', error);
                            showNotification('Failed to post comment');
                        })
                        .finally(() => {
                            // Re-enable button
                            postCommentBtn.disabled = false;
                        });
                    }
                });
                
                commentInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        postCommentBtn.click();
                    }
                });
            }
        });
    </script>
@stop

@section('content')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Top overlay controls (mobile sidebar + search) -->
    <div class="top-header">
        <button id="header-menu-btn" class="header-menu-btn" type="button" aria-label="{{ __('Menu') }}">
            <i class="fas fa-bars"></i>
        </button>
        <button id="search-btn" class="search-btn" type="button" aria-label="{{ __('Search') }}">
            <i class="fas fa-search"></i>
        </button>
    </div>

    <!-- Mobile menu handled by template/mobile-sidebar -->

    <!-- Search Modal (legacy — hidden) -->
    <div class="search-modal" id="search-modal">
        <div class="search-modal-content">
            <div class="search-modal-header">
                <h3>Search Videos</h3>
                <button class="search-modal-close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="search-input-container">
                <input type="text" id="search-input" placeholder="Search for videos..." autocomplete="off">
                <i class="fas fa-search search-icon"></i>
            </div>
            <div class="search-results" id="search-results">
                <div class="search-placeholder">Type at least 3 characters to search...</div>
            </div>
        </div>
    </div>

    <!-- Profile Modal -->
    <div class="profile-modal" id="profile-modal">
        <div class="profile-modal-content">
            <div class="profile-grabber"></div>
            <div class="profile-modal-header">
                <h3>Profile</h3>
                <button class="profile-modal-close" type="button" aria-label="Close">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                </button>
            </div>
            <div class="profile-info">
                <div class="profile-avatar">
                    <div class="profile-avatar-initials" id="profile-modal-avatar"></div>
                </div>
                <div class="profile-details">
                    <h3 id="profile-modal-name"></h3>
                    <p id="profile-modal-username"></p>
                </div>
            </div>
            <div class="profile-actions">
                <button class="profile-action-btn view-profile-btn" id="view-profile-btn" type="button">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    <span>View Profile</span>
                </button>
                <button class="profile-action-btn share-profile-btn" id="share-profile-btn" type="button">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                    <span>Share</span>
                </button>
            </div>
        </div>
    </div>

    <div class="reels-container">
        <div class="video-feed">
            @if($videos && $videos->count() > 0)
                @foreach($videos as $video)
                    <div class="video-item" data-video-id="{{ $video->id }}">
                        <div class="video-wrapper">
                            <video 
                                src="{{ $video->video_url }}"
                                loop
                                muted
                                playsinline
                                preload="metadata"
                                class="video-player"
                                webkit-playsinline
                            ></video>
                            
                            <div class="video-overlay">
                                <div class="video-info">
                                    <div class="user-info clickable" data-user-id="{{ $video->user->id }}" data-user-username="{{ $video->user->username ?? strtolower(str_replace(' ', '', $video->user->name)) }}">
                                        <div class="user-avatar-initials" style="background: {{ ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'][rand(0,4)] }};">
                                            {{ strtoupper(substr($video->user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $video->user->name)[1] ?? '', 0, 1)) }}
                                        </div>
                                        <div class="user-details">
                                            <h4>{{ $video->user->name }}</h4>
                                            <p>{{ '@' . ($video->user->username ?? strtolower(str_replace(' ', '', $video->user->name))) }}</p>
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
                                        <button class="action-btn like-btn @if($video->is_liked) liked @endif" data-video-id="{{ $video->id }}">
                                            <i class="@if($video->is_liked) fas @else far @endif fa-heart"></i>
                                        </button>
                                        <span class="action-count">{{ $video->likes_count }}</span>
                                    </div>
                                    
                                    <div class="action-item">
                                        <button class="action-btn comment-btn" data-video-id="{{ $video->id }}">
                                            <i class="fas fa-comment"></i>
                                        </button>
                                        <span class="action-count">{{ $video->comments_count }}</span>
                                    </div>
                                    
                                    <div class="action-item">
                                        <button class="action-btn share-btn" data-video-id="{{ $video->id }}">
                                            <i class="fas fa-share"></i>
                                        </button>
                                        <span class="action-count">{{ $video->shares_count }}</span>
                                    </div>
                                    
                                    <div class="action-item">
                                        <button class="action-btn repost-btn @if($video->is_reposted) reposted @endif" data-video-id="{{ $video->id }}">
                                            <i class="fas fa-retweet"></i>
                                        </button>
                                        <span class="action-count">{{ $video->reposts_count }}</span>
                                    </div>
                                    
                                    <div class="action-item">
                                        <button class="action-btn upload-btn">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress-bar">
                                <div class="progress"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="no-videos">
                    <i class="fas fa-video"></i>
                    <h3>No videos available</h3>
                    <p>Be the first to share a video!</p>
                    <a href="/videos/create" class="upload-first-video-btn">
                        <i class="fas fa-plus"></i>
                        Upload Your First Video
                    </a>
                </div>
            @endif
        </div>

        <!-- Comments Bottom Sheet -->
        <div class="comments-overlay" id="comments-overlay"></div>
        <div class="comments-sidebar" id="comments-sidebar">
            <div class="comments-grabber" data-comments-drag></div>
            <div class="comments-header" data-comments-drag>
                <h3>Comments</h3>
                <button class="close-comments" aria-label="Close comments">
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                </button>
            </div>

            <div class="comments-list" id="comments-list">
                <div class="no-comments">
                    <p>No comments yet. Be the first to comment!</p>
                </div>
            </div>

            <div class="comment-reactions">
                <button type="button" class="reaction-emoji" data-emoji="❤️">❤️</button>
                <button type="button" class="reaction-emoji" data-emoji="🙌">🙌</button>
                <button type="button" class="reaction-emoji" data-emoji="🔥">🔥</button>
                <button type="button" class="reaction-emoji" data-emoji="👏">👏</button>
                <button type="button" class="reaction-emoji" data-emoji="😢">😢</button>
                <button type="button" class="reaction-emoji" data-emoji="😍">😍</button>
                <button type="button" class="reaction-emoji" data-emoji="😮">😮</button>
                <button type="button" class="reaction-emoji" data-emoji="😂">😂</button>
            </div>

            <div class="comment-form">
                <div class="comment-form__avatar">{{ strtoupper(substr(optional(Auth::user())->name ?? 'U', 0, 1)) }}</div>
                <input type="text" id="comment-input" placeholder="What do you think of this?" maxlength="500">
                <button id="post-comment" aria-label="Post comment">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="20" x2="12" y2="5"/><polyline points="6 11 12 5 18 11"/></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    <nav class="bottom-nav">
        <a href="/feed" class="bottom-nav-item active">
            <i class="fas fa-home bottom-nav-icon"></i>
            <span class="bottom-nav-text">Home</span>
        </a>

        <a href="/posts/create" class="bottom-nav-item">
            <i class="fas fa-video bottom-nav-icon"></i>
            <span class="bottom-nav-text">Video</span>
        </a>
        <a href="{{ route('cryptocurrency.wallet') }}" class="bottom-nav-item">
            <i class="fas fa-wallet bottom-nav-icon"></i>
            <span class="bottom-nav-text">Wallet</span>
        </a>
        <a href="{{ route('cryptocurrency.marketplace') }}" class="bottom-nav-item">
            <i class="fas fa-store bottom-nav-icon"></i>
            <span class="bottom-nav-text">Marketplace</span>
        </a>

        @auth
        <a href="{{ route('profile', ['username' => Auth::user()->username ?? Auth::user()->id]) }}" class="bottom-nav-item">
            <i class="fas fa-user bottom-nav-icon"></i>
            <span class="bottom-nav-text">Profile</span>
        </a>
        @else
        <a href="{{ route('login') }}" class="bottom-nav-item">
            <i class="fas fa-user bottom-nav-icon"></i>
            <span class="bottom-nav-text">Profile</span>
        </a>
        @endauth
    </nav>

    <!-- Enhanced Reels Styles -->
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        body {
            overflow: hidden;
            background-color: #000;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
        
        /* Video container padding for bottom navigation */
        .video-container,
        .reels-container {
            padding-bottom: 80px;
        }
        
        /* Ensure full height for reels */
        html, body {
            height: 100vh !important;
            height: 100dvh !important;
            overflow: hidden !important;
        }
        
        /* Legacy modals replaced by mobile-sidebar */
        .header-modal,
        .search-modal {
            display: none !important;
        }

        /* Top header overlay (Fanfix-style triggers) */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1002;
            padding: 15px 20px;
            background: linear-gradient(rgba(0, 0, 0, 0.55), transparent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: none;
        }

        .top-header .header-menu-btn,
        .top-header .search-btn {
            pointer-events: auto;
        }

        /* Transparent Header Styles */
        .transparent-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1002;
            padding: 20px;
            background: transparent;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header-menu-btn,
        .search-btn {
            position: relative;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), color 0.2s ease, filter 0.25s ease;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: none;
            overflow: visible;
        }

        .header-menu-btn::before,
        .search-btn::before {
            display: none;
        }

        .header-menu-btn i,
        .search-btn i,
        .header-menu-btn svg,
        .search-btn svg {
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55));
        }
        
        .header-menu-btn:hover,
        .search-btn:hover {
            background: transparent;
            border: none;
            transform: scale(1.08);
            box-shadow: none;
        }

        .header-menu-btn:hover i,
        .search-btn:hover i,
        .header-menu-btn:hover svg,
        .search-btn:hover svg {
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.35)) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55));
        }

        .header-menu-btn:active,
        .search-btn:active {
            transform: scale(0.94);
        }
        
        /* Bottom Modal Styles */
        .header-modal,
        .search-modal {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1003;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .header-modal.active,
        .search-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        .header-modal-content,
        .search-modal-content {
            background: #fff;
            border-radius: 20px 20px 0 0;
            padding: 0;
            width: 100%;
            max-height: 70vh;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .header-modal.active .header-modal-content,
        .search-modal.active .search-modal-content {
            transform: translateY(0);
        }
        
        .header-modal-header,
        .search-modal-header {
            padding: 20px 20px 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            position: relative;
        }
        
        /* Add drag handle to bottom modals */
        .header-modal-header::before,
        .search-modal-header::before {
            content: '';
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            width: 40px;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
        }
        
        .header-modal-header h3,
        .search-modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        .header-modal-close,
        .search-modal-close {
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
        
        .header-modal-close:hover,
        .search-modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
            transform: scale(1.05);
        }
        
        /* Header Menu Items */
        .header-menu-items {
            padding: 20px;
            background: #fff;
        }
        
        .header-menu-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 16px 20px;
            text-decoration: none;
            color: #333;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 500;
            border: 1px solid #f0f0f0;
        }
        
        .header-menu-item:hover {
            background: linear-gradient(135deg, #ff6b6b, #ff8a80);
            color: #fff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
        }
        
        .header-menu-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
        }
        
        
        /* Search Modal Styles */
        .search-input-container {
            position: relative;
            padding: 20px;
            background: #fff;
        }
        
        .search-input-container input {
            width: 100%;
            padding: 16px 50px 16px 20px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .search-input-container input:focus {
            border-color: #ff6b6b;
            box-shadow: 0 0 0 3px rgba(255, 107, 107, 0.1);
            background: #fff;
        }
        
        .search-icon {
            position: absolute;
            right: 35px;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
            font-size: 18px;
        }
        
        .search-results {
            max-height: 300px;
            overflow-y: auto;
            padding: 0 20px 20px;
            background: #fff;
        }
        
        .search-loading,
        .no-search-results,
        .search-placeholder {
            text-align: center;
            padding: 40px 20px;
            color: #666;
            font-size: 14px;
        }
        
        .search-result-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 10px;
            border: 1px solid #f0f0f0;
        }
        
        .search-result-item:hover {
            background: #f8f9fa;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        
        .search-result-info {
            flex: 1;
        }
        
        .search-result-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            font-weight: 600;
            color: #333;
            line-height: 1.3;
        }
        
        .search-result-info p {
            margin: 0;
            font-size: 13px;
            color: #666;
        }
        
        /* Profile Modal — neutral flat dark */
        .profile-modal {
            position: fixed;
            inset: 0;
            z-index: 1003;
            background: rgba(0, 0, 0, 0.55);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.25s ease, visibility 0.25s ease;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .profile-modal.active {
            opacity: 1;
            visibility: visible;
        }

        .profile-modal .profile-modal-content {
            background: #1c1c1e !important;
            border-radius: 20px 20px 0 0 !important;
            padding: 0 !important;
            width: 100%;
            max-width: 480px;
            max-height: 70vh;
            overflow: hidden;
            transform: translateY(100%);
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-bottom: none;
            box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.45);
        }

        .profile-modal.active .profile-modal-content {
            transform: translateY(0);
        }

        .profile-grabber {
            width: 40px;
            height: 4px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.25);
            margin: 10px auto 0;
            flex-shrink: 0;
        }

        .profile-modal .profile-modal-header {
            padding: 14px 16px 14px !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
            display: flex;
            justify-content: center;
            align-items: center;
            background: transparent !important;
            position: relative;
        }

        .profile-modal .profile-modal-header::before {
            display: none !important;
        }

        .profile-modal .profile-modal-header h3 {
            margin: 0;
            color: #f5f5f7 !important;
            font-size: 16px;
            font-weight: 700;
        }

        .profile-modal .profile-modal-close {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.08) !important;
            border: none;
            cursor: pointer;
            color: #f5f5f7 !important;
            padding: 0;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .profile-modal .profile-modal-close:hover {
            background: rgba(255, 255, 255, 0.14) !important;
            color: #fff !important;
            transform: translateY(-50%);
        }

        .profile-info {
            padding: 28px 20px 24px;
            background: transparent;
            text-align: center;
        }

        .profile-avatar {
            display: flex;
            justify-content: center;
            margin-bottom: 16px;
        }

        .profile-avatar-initials {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            border: 2px solid rgba(203, 12, 159, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 28px;
            color: white;
            box-shadow: none;
            letter-spacing: 0.02em;
        }

        .profile-details h3 {
            margin: 0 0 6px 0;
            font-size: 20px;
            font-weight: 700;
            color: #f5f5f7;
            letter-spacing: -0.01em;
        }

        .profile-details p {
            margin: 0;
            font-size: 14px;
            color: #8e8e93;
            font-weight: 500;
        }

        .profile-actions {
            padding: 0 16px calc(20px + env(safe-area-inset-bottom));
            background: transparent;
            display: flex;
            gap: 10px;
        }

        .profile-action-btn {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 14px 16px;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s ease, opacity 0.2s ease;
            text-decoration: none;
            box-shadow: none !important;
        }

        .profile-action-btn svg {
            flex-shrink: 0;
        }

        .view-profile-btn {
            background: #cb0c9f !important;
            color: #fff !important;
        }

        .view-profile-btn:hover {
            background: #830866 !important;
            transform: none;
            box-shadow: none !important;
        }

        .share-profile-btn {
            background: rgba(255, 255, 255, 0.08) !important;
            color: #f5f5f7 !important;
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        .share-profile-btn:hover {
            background: rgba(255, 255, 255, 0.14) !important;
            transform: none;
            box-shadow: none !important;
        }

        .profile-action-btn i {
            font-size: 16px;
        }
        
        /* Notification Styles */
        .notification,
        .share-notification {
            position: fixed;
            top: 100px;
            left: 50%;
            transform: translateX(-50%) translateY(-20px);
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            padding: 12px 24px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 500;
            z-index: 2000;
            opacity: 0;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .notification.show,
        .share-notification.show {
            opacity: 1;
            transform: translateX(-50%) translateY(0);
        }
        
        /* Comments Close Button in Header */
        .comments-close-header {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1004;
            background: rgba(0, 0, 0, 0.7);
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
        }
        
        .comments-close-header.active {
            display: flex;
        }
        
        .comments-close-header:hover {
            background: rgba(0, 0, 0, 0.9);
            transform: scale(1.05);
        }
        
        .reels-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            height: 100dvh;
            background: #000;
            z-index: 1000;
        }
        
        .video-feed {
            width: 100%;
            height: 100vh;
            height: 100dvh;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        .video-feed::-webkit-scrollbar {
            display: none;
        }
        
        .video-item {
            width: 100%;
            height: 100vh;
            height: 100dvh;
            position: relative;
            scroll-snap-align: start;
            scroll-snap-stop: always;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .video-wrapper {
            width: 100%;
            height: 100%;
            position: relative;
            overflow: hidden;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            cursor: pointer;
            background: #000;
        }
        
        .sound-control-btn {
            position: absolute;
            top: 80px;
            right: 20px;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 15;
            transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), filter 0.25s ease;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: none;
            overflow: visible;
        }

        .sound-control-btn::before {
            display: none;
        }

        .sound-control-btn i,
        .sound-control-btn svg {
            position: relative;
            z-index: 1;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55));
        }
        
        .sound-control-btn:hover {
            background: transparent;
            transform: scale(1.1);
            box-shadow: none;
        }

        .sound-control-btn:hover i,
        .sound-control-btn:hover svg {
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.35)) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55));
        }

        .sound-control-btn:active {
            transform: scale(0.94);
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
        }
        
        .video-info {
            flex: 0 0 auto;
            max-width: calc(100% - 90px);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: flex-start;
        }
        
        .video-actions {
            flex: 0 0 auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
            gap: 12px;
            width: 100%;
        }
        
        .user-details {
            text-align: left;
        }
        
        .user-details h4,
        .user-details p {
            text-align: left;
        }
        
        /* Make user info clickable */
        .user-info.clickable {
            cursor: pointer;
            transition: all 0.3s ease;
            padding: 8px;
            border-radius: 12px;
            margin: -8px -8px 7px -8px;
        }
        
        .user-info.clickable:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.02);
        }
        
        .user-info.clickable:active {
            transform: scale(0.98);
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
            flex-shrink: 0;
        }
        
        .user-details h4 {
            margin: 0 0 4px 0;
            font-size: 16px;
            font-weight: 600;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
        }
        
        .user-details p {
            margin: 0;
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
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .video-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            padding-bottom: 100px;
        }
        
        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            min-width: 48px;
        }
        
        .action-btn {
            position: relative;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 28px;
            line-height: 1;
            cursor: pointer;
            width: 44px;
            height: 44px;
            padding: 0;
            margin: 0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), color 0.2s ease;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: none;
            overflow: visible;
            isolation: auto;
        }

        .action-btn::before,
        .action-btn::after {
            display: none;
        }

        .action-btn i,
        .action-btn svg {
            position: relative;
            z-index: 1;
            font-size: inherit;
            width: 1em;
            height: 1em;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55));
            transition: transform 0.25s ease, filter 0.25s ease;
        }
        
        .action-btn:hover {
            background: transparent;
            border: none;
            transform: translateY(-2px) scale(1.08);
            box-shadow: none;
        }

        .action-btn:hover::after {
            display: none;
        }

        .action-btn:hover i,
        .action-btn:hover svg {
            transform: scale(1.04);
            filter: drop-shadow(0 0 10px rgba(255, 255, 255, 0.35)) drop-shadow(0 2px 6px rgba(0, 0, 0, 0.55));
        }
        
        .action-btn:active {
            transform: scale(0.92);
        }
        
        .like-btn.liked {
            background: transparent;
            color: #ff4d6d;
            border: none;
            box-shadow: none;
        }

        .like-btn.liked i {
            filter: drop-shadow(0 0 10px rgba(255, 77, 109, 0.75)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.45));
        }
        
        .repost-btn.reposted {
            background: transparent;
            color: #3dff9a;
            border: none;
            box-shadow: none;
        }

        .repost-btn.reposted i {
            filter: drop-shadow(0 0 10px rgba(61, 255, 154, 0.65)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.45));
        }
        
        .upload-btn {
            background: transparent;
            color: #5ab0ff;
            border: none;
            box-shadow: none;
            font-size: 26px;
        }

        .upload-btn::before {
            display: none;
        }

        .upload-btn:hover {
            background: transparent;
            border: none;
            box-shadow: none;
            color: #8cc8ff;
        }

        .upload-btn:hover i {
            filter: drop-shadow(0 0 12px rgba(90, 176, 255, 0.7)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.45));
        }
        
        .action-count {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: 0.01em;
            text-shadow:
                0 1px 2px rgba(0, 0, 0, 0.75),
                0 0 10px rgba(0, 0, 0, 0.35);
            min-height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }
        
        {{-- Seek bar styles now live in elements/reels-scrubber --}}
        
        /* Comments Bottom Sheet — Instagram style */
        .comments-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease;
        }

        .comments-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .comments-sidebar {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 0;
            height: 72vh;
            max-height: 760px;
            background: #1c1c1e;
            border-radius: 20px 20px 0 0;
            transition: transform 0.35s cubic-bezier(0.22, 1, 0.36, 1);
            z-index: 1001;
            display: flex;
            flex-direction: column;
            box-shadow: 0 -8px 32px rgba(0, 0, 0, 0.5);
            transform: translateY(100%);
            overflow: hidden;
            overscroll-behavior: contain;
        }

        .comments-sidebar.active {
            transform: translateY(0);
        }

        .comments-grabber {
            width: 40px;
            height: 4px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.25);
            margin: 10px auto 4px;
            flex-shrink: 0;
            cursor: grab;
            touch-action: none;
        }

        .comments-header {
            padding: 8px 16px 14px;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            flex-shrink: 0;
            cursor: grab;
            touch-action: none;
        }

        .comments-sidebar.dragging {
            transition: none !important;
        }

        .comments-sidebar.dragging .comments-grabber,
        .comments-sidebar.dragging .comments-header {
            cursor: grabbing;
        }

        /* Keep the close button clickable, not a drag handle */
        .comments-header .close-comments {
            cursor: pointer;
            touch-action: auto;
        }

        .comments-header h3 {
            margin: 0;
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }

        .close-comments {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            color: #fff;
            padding: 6px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .close-comments:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .comments-list {
            flex: 1;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            padding: 12px 16px 8px;
            min-height: 0;
        }

        .no-comments,
        .loading {
            text-align: center;
            color: #8e8e93;
            padding: 48px 20px;
            font-size: 14px;
        }

        .comment-item {
            display: flex;
            margin-bottom: 20px;
            gap: 12px;
            align-items: flex-start;
        }

        .comment-item.is-reply {
            margin-left: 44px;
        }

        .comment-avatar-initials {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            color: white;
            flex-shrink: 0;
        }

        .comment-content {
            flex: 1;
            min-width: 0;
        }

        .comment-head {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .comment-content strong {
            color: #f5f5f7;
            font-size: 13px;
            font-weight: 600;
        }

        .comment-time {
            color: #8e8e93;
            font-size: 12px;
        }

        .comment-content p {
            margin: 3px 0 0;
            color: #e5e5ea;
            font-size: 14px;
            line-height: 1.4;
            word-wrap: break-word;
        }

        .comment-actions {
            display: flex;
            gap: 16px;
            margin-top: 6px;
        }

        .comment-actions span {
            color: #8e8e93;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
        }

        .comment-like {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            color: #8e8e93;
            flex-shrink: 0;
            cursor: pointer;
            padding-top: 4px;
        }

        .comment-like svg {
            width: 16px;
            height: 16px;
        }

        .comment-like.liked {
            color: #ff3040;
        }

        .comment-like span {
            font-size: 11px;
        }

        /* Emoji quick reactions */
        .comment-reactions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 18px;
            gap: 4px;
            flex-shrink: 0;
        }

        .reaction-emoji {
            background: transparent;
            border: none;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            padding: 2px;
            transition: transform 0.15s ease;
        }

        .reaction-emoji:hover {
            transform: scale(1.25);
        }

        /* Composer */
        .comment-form {
            padding: 8px 12px calc(12px + env(safe-area-inset-bottom));
            display: flex;
            gap: 10px;
            align-items: center;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            background: #1c1c1e;
            flex-shrink: 0;
        }

        .comment-form__avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            flex-shrink: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            color: #fff;
            background: linear-gradient(135deg, #cb0c9f, #830866);
        }

        .comment-form input {
            flex: 1;
            padding: 11px 16px;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 22px;
            font-size: 14px;
            outline: none;
            transition: border-color 0.2s ease;
            background: #2c2c2e;
            color: #fff;
        }

        .comment-form input::placeholder {
            color: #8e8e93;
        }

        .comment-form input:focus {
            border-color: rgba(203, 12, 159, 0.6);
        }

        .comment-form button {
            background: #cb0c9f;
            color: #fff;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            flex-shrink: 0;
            transition: background 0.2s ease, opacity 0.2s ease;
        }

        .comment-form button:hover:not(:disabled) {
            background: #830866;
        }

        .comment-form button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Hide top header & bottom nav while comments are open */
        body.comments-open .top-header,
        body.comments-open .transparent-header,
        body.comments-open .video-actions,
        body.comments-open .sound-control-btn {
            opacity: 0 !important;
            pointer-events: none !important;
        }

        body.comments-open .bottom-nav {
            display: none !important;
        }

        body.comments-open .video-feed {
            overflow: hidden !important;
            touch-action: none;
            overscroll-behavior: none;
        }
        
        .no-videos {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: calc(100vh - 78px);
            width: 100%;
            color: #f8fafc;
            text-align: center;
            padding: 24px 16px;
            position: relative;
            isolation: isolate;
            animation: emptyStateFloat 4.6s ease-in-out infinite;
        }

        .no-videos::before {
            content: '';
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -58%);
            background: radial-gradient(circle, rgba(236, 72, 153, 0.22) 0%, rgba(236, 72, 153, 0.08) 40%, transparent 72%);
            filter: blur(28px);
            z-index: -1;
            pointer-events: none;
        }

        .no-videos::after {
            content: '';
            position: absolute;
            width: min(92vw, 540px);
            height: min(62vh, 460px);
            border-radius: 30px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(165deg, rgba(255, 255, 255, 0.08) 0%, rgba(255, 255, 255, 0.03) 36%, rgba(8, 8, 14, 0.28) 100%);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.15), 0 22px 60px rgba(0, 0, 0, 0.45);
            z-index: -2;
            pointer-events: none;
        }

        @keyframes emptyStateFloat {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .no-videos > * {
            position: relative;
            z-index: 1;
        }

        .no-videos i {
            width: 78px;
            height: 78px;
            border-radius: 22px;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            color: rgba(244, 244, 245, 0.92);
            background: linear-gradient(145deg, rgba(255, 255, 255, 0.14), rgba(255, 255, 255, 0.05));
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.2), 0 14px 28px rgba(0, 0, 0, 0.4);
        }

        .no-videos h3 {
            margin-bottom: 8px;
            font-size: clamp(1.45rem, 2.2vw, 1.9rem);
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
        }

        .no-videos p {
            max-width: 360px;
            margin: 0 0 30px;
            color: rgba(226, 232, 240, 0.78);
            font-size: 1.02rem;
        }

        .upload-first-video-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, #fb7185 0%, #f43f5e 45%, #ec4899 100%);
            color: #fff;
            text-decoration: none;
            padding: 14px 26px;
            border-radius: 999px;
            font-size: 0.95rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            border: 1px solid rgba(255, 255, 255, 0.28);
            box-shadow: 0 8px 22px rgba(244, 63, 94, 0.38), 0 0 0 1px rgba(255, 255, 255, 0.08) inset;
            transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
        }

        .upload-first-video-btn:hover {
            color: #fff;
            text-decoration: none;
            transform: translateY(-2px);
            filter: saturate(1.07);
            box-shadow: 0 14px 30px rgba(244, 63, 94, 0.45), 0 0 26px rgba(236, 72, 153, 0.33);
        }

        .upload-first-video-btn i {
            font-size: 14px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.22);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            line-height: 1;
            flex: 0 0 22px;
        }

        .no-videos::after {
            background:
                radial-gradient(circle at 22% 20%, rgba(244, 114, 182, 0.2) 0%, transparent 38%),
                radial-gradient(circle at 82% 86%, rgba(56, 189, 248, 0.14) 0%, transparent 42%),
                linear-gradient(160deg, rgba(255, 255, 255, 0.12) 0%, rgba(244, 114, 182, 0.08) 35%, rgba(15, 23, 42, 0.46) 100%);
        }

        @media (prefers-reduced-motion: reduce) {
            .no-videos {
                animation: none;
            }

            .no-videos::after {
                animation: none;
            }
        }
        
        /* Mobile Optimizations */
        @media (max-width: 768px) {
            .no-videos {
                min-height: calc(100vh - 122px);
                padding: 16px 14px 74px;
            }

            .transparent-header {
                padding: 15px;
            }
            
            .header-menu-btn,
            .search-btn {
                width: 44px;
                height: 44px;
                font-size: 16px;
            }
            
            .comments-close-header {
                width: 44px;
                height: 44px;
                font-size: 18px;
                top: 15px;
                right: 15px;
            }
            
            .comments-sidebar {
                height: 82vh;
                max-height: none;
            }
            
            .video-overlay {
                padding: 15px;
                justify-content: space-between !important;
                align-items: flex-end !important;
            }
            
            .video-info {
                max-width: calc(100% - 75px);
                display: flex;
                flex-direction: column;
                align-items: flex-start !important;
                justify-content: flex-start !important;
                margin: 0 !important;
                padding: 0 !important;
                flex: 0 0 auto;
            }
            
            .video-actions {
                flex: 0 0 auto;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: flex-end !important;
            }
            
            .user-info {
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                width: auto !important;
                margin-left: 0 !important;
                margin-right: auto !important;
                margin-bottom: 15px;
            }
            
            .user-avatar-initials {
                width: 40px;
                height: 40px;
                font-size: 14px;
                margin-right: 0 !important;
            }
            
            .user-details {
                text-align: left !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .user-details h4 {
                font-size: 14px;
                text-align: left !important;
                margin: 0 0 4px 0 !important;
            }
            
            .user-details p {
                font-size: 13px;
                text-align: left !important;
                margin: 0 !important;
            }
            
            .video-title {
                font-size: 15px;
            }
            
            .video-description {
                font-size: 13px;
            }
            
            .action-btn {
                width: 44px;
                height: 44px;
                font-size: 26px;
            }
            
            .video-actions {
                gap: 16px;
                padding-bottom: 100px;
            }

            .action-item {
                gap: 2px;
            }
            
            .action-count {
                font-size: 12px;
            }
            
            .sound-control-btn {
                width: 44px;
                height: 44px;
                font-size: 16px;
                top: 70px;
                right: 15px;
            }
            
            .profile-avatar-initials {
                width: 70px;
                height: 70px;
                font-size: 20px;
            }
            
            .profile-details h3 {
                font-size: 18px;
            }
            
            .profile-action-btn {
                font-size: 13px;
                padding: 12px 14px;
            }
            
            .profile-action-btn i {
                font-size: 16px;
            }
            
            .upload-first-video-btn {
                font-size: 14px;
                padding: 14px 28px;
            }
        }
        
        /* Tablet responsive */
        @media (min-width: 769px) and (max-width: 1024px) {
            .comments-sidebar {
                left: 50%;
                right: auto;
                width: 480px;
                transform: translate(-50%, 100%);
            }
            .comments-sidebar.active {
                transform: translate(-50%, 0);
            }
        }

        @media (min-width: 1025px) {
            .comments-sidebar {
                left: 50%;
                right: auto;
                width: 500px;
                transform: translate(-50%, 100%);
            }
            .comments-sidebar.active {
                transform: translate(-50%, 0);
            }
        }
        
        /* Ensure full coverage on all devices */
        @media (orientation: landscape) and (max-width: 768px) {
            .transparent-header {
                padding: 10px 15px;
            }
            
            .video-overlay {
                padding: 10px 15px;
            }
            
            .video-actions {
                gap: 14px;
                padding-bottom: 90px;
            }
            
            .action-btn {
                width: 42px;
                height: 42px;
                font-size: 24px;
            }

            .upload-btn {
                font-size: 22px;
            }
            
            .action-count {
                font-size: 11px;
            }
            
            .video-overlay {
                justify-content: space-between !important;
                align-items: flex-end !important;
            }
            
            .video-info {
                align-items: flex-start !important;
                justify-content: flex-start !important;
                margin: 0 !important;
                padding: 0 !important;
                flex: 0 0 auto;
            }
            
            .video-actions {
                flex: 0 0 auto;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: flex-end !important;
            }
            
            .user-info {
                justify-content: flex-start !important;
                margin-left: 0 !important;
                margin-right: auto !important;
            }
            
            .user-avatar-initials {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            
            .user-details {
                text-align: left !important;
            }
            
            .user-details h4,
            .user-details p {
                text-align: left !important;
            }
            
            .sound-control-btn {
                top: 50px;
            }
            
            .profile-modal-content {
                max-height: 80vh;
            }
            
            .profile-avatar-initials {
                width: 60px;
                height: 60px;
                font-size: 18px;
            }
            
            .profile-details h3 {
                font-size: 18px;
            }
            
            .profile-action-btn {
                font-size: 14px;
                padding: 12px 16px;
            }
        }
        
        /* Bottom Navigation Bar */
        .bottom-nav {
            position: fixed;
            bottom: calc(8px + env(safe-area-inset-bottom));
            left: 10px;
            right: 10px;
            background:
                radial-gradient(circle at 15% 0%, rgba(236, 72, 153, 0.2) 0%, transparent 35%),
                radial-gradient(circle at 85% 100%, rgba(56, 189, 248, 0.15) 0%, transparent 40%),
                linear-gradient(180deg, rgba(13, 14, 24, 0.94) 0%, rgba(6, 7, 14, 0.95) 100%);
            backdrop-filter: blur(22px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 8px;
            display: flex;
            justify-content: space-around;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 14px 40px rgba(0, 0, 0, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.12);
        }
        
        .bottom-nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: rgba(226, 232, 240, 0.72);
            transition: all 0.25s ease;
            padding: 9px 8px 7px;
            border-radius: 12px;
            flex: 1;
            max-width: 120px;
            position: relative;
            overflow: hidden;
        }
        
        .bottom-nav-item.active {
            color: #fff;
            background: linear-gradient(135deg, rgba(244, 114, 182, 0.24), rgba(56, 189, 248, 0.14));
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.28), 0 8px 24px rgba(236, 72, 153, 0.28);
        }

        .bottom-nav-item.active::after {
            content: '';
            position: absolute;
            left: 18%;
            right: 18%;
            bottom: 0;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg, rgba(244, 114, 182, 0.9), rgba(56, 189, 248, 0.9));
        }
        
        .bottom-nav-item:hover {
            color: #fff;
            transform: translateY(-1px) scale(1.01);
        }
        
        .bottom-nav-icon {
            font-size: 14px;
            margin-bottom: 4px;
            filter: drop-shadow(0 0 8px rgba(255, 255, 255, 0.1));
        }
        
        .bottom-nav-text {
            font-size: 9px;
            font-weight: 600;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }
        
        
        /* Ensure bottom navigation is visible */
        .bottom-nav {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
            pointer-events: auto !important;
        }
        
        /* JavaScript for Bottom Navigation */
        document.addEventListener('DOMContentLoaded', function() {
            // Handle bottom navigation active state
            const currentPath = window.location.pathname;
            const bottomNavItems = document.querySelectorAll('.bottom-nav-item');
            
            bottomNavItems.forEach(item => {
                item.classList.remove('active');
                const href = item.getAttribute('href');
                
                if (currentPath === href || (href === '/feed' && currentPath === '/')) {
                    item.classList.add('active');
                }
            });
        });
        
    </style>
@include('elements.reels-scrubber')
@stop