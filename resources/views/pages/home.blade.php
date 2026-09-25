@extends('layouts.generic')

@section('page_description', getSetting('site.description'))
@section('share_url', route('home'))
@section('share_title', getSetting('site.name') . ' - ' . getSetting('site.slogan'))
@section('share_description', getSetting('site.description'))
@section('share_type', 'article')
@section('share_img', GenericHelper::getOGMetaImage())

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
    {{-- Font Awesome 6.4.0 is already loaded globally by template/head --}}
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        
        /* Legacy modals replaced by mobile-sidebar */
        .header-modal,
        .search-modal {
            display: none !important;
        }
        
        /* Hide header on home page - TikTok style */
        header, .header, .navbar, nav.navbar, .site-header, footer, .footer {
            display: none !important;
        }
        
        body {
            overflow: hidden !important;
            background-color: #000 !important;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
        }
        
        /* Ensure full height for reels */
        html, body {
            height: 100vh !important;
            height: 100dvh !important;
            overflow: hidden !important;
        }
        
        .reels-container {
            position: fixed;
            /* One source of truth for the viewport height. Container, scroller and
               item each resolved 100dvh independently, and dvh changes as the mobile
               URL bar collapses -- so they did not have to agree at the same instant,
               and a sliver of the next reel showed through. The fixed container is
               the box; everything inside takes 100% of it. */
            inset: 0;
            background: #000;
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .video-feed {
            width: 100%;
            max-width: 100%;
            height: 100%;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
            scrollbar-width: none;
            -ms-overflow-style: none;
            margin: 0 auto;
        }
        
        .video-feed::-webkit-scrollbar {
            display: none;
        }
        
        .video-item {
            width: 100%;
            height: 100%;
            position: relative;
            scroll-snap-align: start;
            scroll-snap-stop: always;
            display: flex;
            justify-content: center;
            align-items: center;
            max-width: 100%;
            margin: 0;
        }
        
        .video-wrapper {
            width: 100%;
            height: 100%;
            max-width: 100%;
            position: relative;
            overflow: hidden;
            margin: 0;
            pointer-events: none;
        }
        
        .video-player {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            cursor: pointer;
            pointer-events: auto;
            background: #000;
            max-width: 100%;
        }
        
        .video-overlay {
            position: absolute;
            bottom: 64px;
            left: 0;
            right: 0;
            padding: 60px 90px 18px 16px;
            background: linear-gradient(transparent, rgba(0, 0, 0, 0.55) 55%, rgba(0, 0, 0, 0.75));
            color: #fff;
            z-index: 10;
            pointer-events: auto;
        }
        
        .video-info {
            max-width: 100%;
            text-align: left;
        }
        
        /* Right Action Rail (Instagram Reels style) */
        .video-actions-left {
            position: absolute;
            right: 10px;
            bottom: 96px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            z-index: 15;
            pointer-events: auto;
        }
        
        /* Right Navigation Controls */
        .video-navigation-right {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            display: none;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            z-index: 15;
            pointer-events: auto;
        }
        
        .nav-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            pointer-events: auto;
        }
        
        .nav-btn:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1);
        }
        
        .nav-btn:active {
            transform: scale(0.95);
        }
        
        .user-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
        }

        .user-info {
            display: inline-flex;
            flex-direction: row !important;
            align-items: center;
            gap: 10px;
        }
        
        .user-info.clickable {
            cursor: pointer;
            transition: transform 0.2s ease;
            background: transparent !important;
            backdrop-filter: none !important;
            pointer-events: auto;
            flex-shrink: 0;
        }

        .user-details {
            display: flex;
            align-items: center;
        }
        
        .user-info.clickable:hover {
            background: transparent !important;
            transform: none;
        }
        
        .user-avatar-initials {
            position: relative;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: 2px solid #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 13px;
            color: white;
            flex-shrink: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
            transition: transform 0.2s ease;
        }

        .user-avatar-initials::before {
            content: '';
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            background: linear-gradient(45deg, #f9ce34, #ee2a7b, #6228d7);
            z-index: -1;
        }
        
        .user-info.clickable:hover .user-avatar-initials {
            transform: scale(1.06);
        }
        
        .user-details h4 {
            margin: 0;
            font-size: 15px;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
            letter-spacing: 0.2px;
        }

        .follow-btn {
            appearance: none;
            border: 1px solid rgba(255, 255, 255, 0.85);
            background: transparent;
            color: #fff;
            font-size: 13px;
            font-weight: 700;
            padding: 5px 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            pointer-events: auto;
        }

        .follow-btn:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .follow-btn.following {
            border-color: rgba(255, 255, 255, 0.4);
            color: rgba(255, 255, 255, 0.75);
            font-weight: 600;
        }
        
        .video-caption {
            margin-top: 4px;
            text-align: left;
        }
        
        .video-description {
            font-size: 14px;
            opacity: 0.95;
            line-height: 1.4;
            margin: 0;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .video-audio,
        .video-location {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            font-size: 13px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.92);
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.5);
            max-width: 100%;
            overflow: hidden;
        }

        .video-audio i,
        .video-location i {
            font-size: 12px;
            flex-shrink: 0;
        }

        .video-audio__marquee {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .video-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 18px;
            padding-bottom: 0;
        }
        
        .action-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
            min-width: 48px;
            pointer-events: auto;
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
            filter: none;
            pointer-events: auto;
            overflow: visible;
            isolation: auto;
        }

        .action-btn::before {
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

        .save-btn.saved {
            background: transparent;
            color: #ffd54f;
            border: none;
            box-shadow: none;
        }

        .save-btn.saved i {
            filter: drop-shadow(0 0 10px rgba(255, 213, 79, 0.65)) drop-shadow(0 2px 4px rgba(0, 0, 0, 0.45));
        }

        .more-btn {
            font-size: 26px;
            background: transparent;
            color: #5ab0ff;
            border: none;
            box-shadow: none;
        }

        /* Profile thumbnail at bottom of rail (Instagram style) */
        .action-profile {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            margin-top: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-weight: 700;
            font-size: 13px;
            border: 1.5px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.5);
            cursor: pointer;
            transition: transform 0.2s ease;
            pointer-events: auto;
        }

        .action-profile:hover {
            transform: scale(1.08);
        }
        
        .action-count {
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            line-height: 1.15;
            letter-spacing: 0.01em;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.7);
            min-height: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 0;
            white-space: nowrap;
        }
        
        {{-- Seek bar styles now live in elements/reels-scrubber --}}
        .no-comments,
        .loading {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--cs-muted);
            padding: 48px 24px;
            min-height: 180px;
            gap: 8px;
        }

        .no-comments p,
        .loading p {
            margin: 0;
            font-size: 0.92rem;
            color: var(--cs-text-2);
        }

        body.comments-open {
            overflow: hidden !important;
        }

        body.comments-open .video-feed {
            overflow: hidden !important;
            touch-action: none;
            overscroll-behavior: none;
        }

        body.comments-open .bottom-nav,
        body.comments-open .top-header {
            pointer-events: none;
            visibility: hidden;
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
        
        /* Desktop sizing lives in elements/reels-scrubber, which derives the column
           from the feed height so it is a true 9:16 short rather than a fixed 692px
           slab that changed shape with the window. */
        
        /* Mobile Optimizations - Full Screen */
        @media (max-width: 768px) {
            .no-videos {
                min-height: calc(100vh - 122px);
                padding: 16px 14px 74px;
            }

            .reels-container {
                justify-content: flex-start;
            }
            
            .video-feed {
                max-width: 100%;
                width: 100%;
            }
            
            .video-item {
                max-width: 100%;
            }
            
            .video-wrapper {
                max-width: 100%;
            }
            
            .video-player {
                max-width: 100%;
            }
            
            /* Comments bottom sheet styles are defined globally above */

            .video-overlay {
                padding: 50px 78px 14px 14px;
                bottom: 55px;
            }
            
            .video-info {
                max-width: 100%;
                text-align: left;
            }
            
            .video-actions-left {
                bottom: 90px;
                right: 8px;
            }
            
            .user-avatar-initials {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            
            .user-details h4 {
                font-size: 14px;
            }
            
            .video-description {
                font-size: 13px;
            }
            
            .action-btn {
                width: 44px;
                height: 44px;
                font-size: 26px;
            }

            .upload-btn,
            .more-btn {
                width: 44px;
                height: 44px;
                font-size: 24px;
            }
            
            .video-actions {
                gap: 16px;
                padding-bottom: 0;
            }
            
            .video-actions-left {
                bottom: 90px;
                gap: 16px;
            }
            
            .video-navigation-right {
                right: 10px;
                gap: 15px;
            }
            
            .nav-btn {
                width: 44px;
                height: 44px;
                font-size: 20px;
            }
            
            .action-count {
                font-size: 12px;
            }
        }
        
        /* Top Header Styles */
        .top-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1002;
            /* No inset: the buttons are 46px hit areas that carry their own spacing,
               so padding here only pushed them away from the edge twice over. */
            padding: 0;
            background: linear-gradient(rgba(0, 0, 0, 0.6), transparent);
            display: flex;
            justify-content: space-between;
            align-items: center;
            pointer-events: auto;
        }
        
        .header-menu-btn,
        .search-btn {
            position: relative;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), filter 0.25s ease;
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            box-shadow: none;
            pointer-events: auto;
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
        
        /* Gamification: streak / level pill in the top header */
        .home-streak-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            border-radius: 999px;
            color: #fff;
            font-size: 0.78rem;
            font-weight: 600;
            line-height: 1;
            text-decoration: none;
            background: rgba(0, 0, 0, 0.32);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.6);
            transition: transform 0.25s cubic-bezier(0.22, 1, 0.36, 1), background 0.25s ease;
            pointer-events: auto;
        }

        .home-streak-pill:hover,
        .home-streak-pill:focus {
            color: #fff;
            text-decoration: none;
            transform: scale(1.04);
            background: rgba(131, 8, 102, 0.55);
        }

        .home-streak-pill__sep {
            width: 1px;
            height: 12px;
            background: rgba(255, 255, 255, 0.28);
        }

        /* Count of quests ready to claim plus drops waiting to be opened. */
        .home-streak-pill__badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 17px;
            height: 17px;
            padding: 0 5px;
            border-radius: 99px;
            background: #e6295b;
            color: #fff;
            font-size: 11px;
            font-weight: 700;
            line-height: 1;
        }

        body.comments-open .home-streak-pill {
            opacity: 0;
            pointer-events: none;
        }

        @media (max-width: 360px) {
            .home-streak-pill {
                padding: 6px 10px;
                font-size: 0.7rem;
                gap: 6px;
            }
        }

        /* Bottom Modal Styles */
        .header-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 10005 !important; /* Higher than bottom nav (usually z-index: 3) */
            background: rgba(0, 0, 0, 0.7);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            overflow: hidden;
        }
        
        .header-modal.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Prevent body scroll when modal is open */
        body.modal-open {
            overflow: hidden;
            position: fixed;
            width: 100%;
        }
        
        .header-modal-content {
            background: #fff;
            border-radius: 20px 20px 0 0;
            padding: 0;
            width: 100%;
            max-height: 85vh;
            overflow-y: auto;
            overflow-x: hidden;
            transform: translateY(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: absolute;
            bottom: 0;
            display: flex;
            flex-direction: column;
            z-index: 10006 !important;
        }
        
        .header-modal.active .header-modal-content {
            transform: translateY(0);
        }
        
        .header-modal-header {
            padding: 20px 20px 16px 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            position: sticky;
            top: 0;
            z-index: 10;
            flex-shrink: 0;
        }
        
        .header-modal-header::before {
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
        
        .header-modal-header h3 {
            margin: 0;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }
        
        .header-modal-close {
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
        
        .header-modal-close:hover {
            background: rgba(0, 0, 0, 0.1);
            color: #333;
            transform: scale(1.05);
        }
        
        /* Header Menu Items */
        .header-menu-items {
            padding: 20px;
            background: #fff;
            flex: 1;
            overflow-y: auto;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 120px; /* Extra padding to ensure all items are visible above bottom nav */
            min-height: 0;
        }
        
        .header-menu-item {
            display: flex !important;
            align-items: center !important;
            gap: 15px;
            padding: 16px 20px;
            text-decoration: none;
            color: #1a1a1a !important;
            border-radius: 12px;
            transition: all 0.3s ease;
            margin-bottom: 12px;
            font-size: 16px;
            font-weight: 600;
            background: #ffffff !important;
            border: 2px solid #e9ecef !important;
            width: 100%;
            box-sizing: border-box;
            position: relative;
            min-height: 56px;
        }
        
        .header-menu-item:last-child {
            margin-bottom: 0;
        }
        
        .header-menu-item:hover,
        .header-menu-item:active,
        .header-menu-item:focus {
            background: linear-gradient(135deg, #667eea, #764ba2) !important;
            color: #ffffff !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            border-color: transparent !important;
            text-decoration: none;
        }
        
        .header-menu-item i {
            font-size: 20px;
            width: 24px;
            text-align: center;
            flex-shrink: 0;
            color: inherit;
        }
        
        .header-menu-item span {
            flex: 1;
            font-weight: 500;
            color: inherit;
        }
        
        /* TikTok-Style Full Screen Search */
        .search-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1004;
            background: #000;
            display: flex;
            flex-direction: column;
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            opacity: 0;
            visibility: hidden;
        }
        
        .search-modal.active {
            transform: translateX(0);
            opacity: 1;
            visibility: visible;
        }
        
        .search-header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .search-back-btn {
            background: transparent;
            border: none;
            color: #fff;
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: background 0.2s ease;
        }
        
        .search-back-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .search-input-wrapper {
            flex: 1;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            padding: 10px 16px;
        }
        
        .search-input-icon {
            color: rgba(255, 255, 255, 0.6);
            font-size: 18px;
        }
        
        .search-input-wrapper input {
            flex: 1;
            background: transparent;
            border: none;
            color: #fff;
            font-size: 16px;
            outline: none;
            padding: 0;
        }
        
        .search-input-wrapper input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
        
        .search-clear-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
            cursor: pointer;
            padding: 4px;
            display: none;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .search-clear-btn.visible {
            display: flex;
        }
        
        .search-clear-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .search-send-btn {
            background: transparent;
            border: none;
            color: rgba(255, 255, 255, 0.6);
            font-size: 20px;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.2s ease;
        }
        
        .search-send-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
        }
        
        .search-content {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
            background: #000;
        }
        
        .search-suggestions {
            margin-bottom: 24px;
        }
        
        /* Grid layout for video results */
        .search-suggestions.video-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        
        @media (max-width: 1400px) {
            .search-suggestions.video-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }
        
        @media (max-width: 900px) {
            .search-suggestions.video-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 600px) {
            .search-suggestions.video-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
        }
        
        .search-suggestion-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 12px 0;
            cursor: pointer;
            transition: opacity 0.2s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .search-suggestion-item:last-child {
            border-bottom: none;
        }
        
        .search-suggestion-item:hover {
            opacity: 0.8;
        }
        
        .suggestion-icon {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: rgba(255, 255, 255, 0.6);
            font-size: 18px;
        }
        
        .suggestion-text {
            flex: 1;
            color: #fff;
            font-size: 16px;
            font-weight: 400;
        }
        
        .user-profile-suggestion {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            cursor: pointer;
            transition: opacity 0.2s ease;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .user-profile-suggestion:last-child {
            border-bottom: none;
        }
        
        .user-profile-suggestion:hover {
            opacity: 0.8;
        }
        
        .user-avatar-search {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, #FF6B6B, #4ECDC4);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .user-avatar-search img {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
        }
        
        .user-info-search {
            flex: 1;
            min-width: 0;
        }
        
        .user-info-search .username {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }
        
        .user-info-search .verified-badge {
            color: #1DA1F2;
            font-size: 14px;
        }
        
        .user-info-search .user-desc {
            color: rgba(255, 255, 255, 0.6);
            font-size: 14px;
            margin: 0;
        }
        
        .search-loading,
        .no-search-results,
        .search-placeholder {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255, 255, 255, 0.6);
            font-size: 16px;
        }
        
        .search-result-video {
            display: flex;
            flex-direction: column;
            cursor: pointer;
            transition: all 0.3s ease;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .search-result-video:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(255, 107, 107, 0.3);
        }
        
        .video-thumbnail {
            width: 100%;
            height: 200px;
            border-radius: 8px 8px 0 0;
            background: rgba(255, 255, 255, 0.1);
            overflow: hidden;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .video-thumbnail img,
        .video-thumbnail video {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .video-overlay-stats {
            position: absolute;
            bottom: 8px;
            left: 8px;
            right: 8px;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            padding: 6px 10px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 12px;
            color: #fff;
            font-weight: 600;
        }
        
        .video-overlay-stats i {
            color: #ff6b6b;
            font-size: 14px;
        }
        
        .video-info-search {
            padding: 12px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0 0 8px 8px;
        }
        
        .video-info-search h4 {
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            margin: 0 0 6px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
            min-height: 36px;
        }
        
        .video-info-search p {
            color: rgba(255, 255, 255, 0.7);
            font-size: 12px;
            margin: 0 0 4px 0;
            display: flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .video-info-search p i {
            font-size: 11px;
            opacity: 0.8;
            flex-shrink: 0;
        }
        
        .video-views {
            font-size: 11px !important;
            color: rgba(255, 255, 255, 0.5) !important;
        }
        
        @media (max-width: 768px) {
            .search-header {
                padding: 10px 12px;
            }
            
            .search-input-wrapper {
                padding: 8px 12px;
            }
            
            .search-content {
                padding: 12px;
            }
            
            .user-avatar-search {
                width: 44px;
                height: 44px;
                font-size: 16px;
            }
            
            .video-thumbnail {
                width: 100px;
                height: 134px;
            }
        }
        
        /* Bottom Navigation Bar */
        
        

        
        
        
        
        
        /* Mobile adjustments */
        @media (max-width: 768px) {
            .user-avatar-initials {
                width: 32px;
                height: 32px;
                font-size: 12px;
                border-width: 2px;
            }
            
            .user-details h4 {
                font-size: 14px;
            }
            
            .user-info {
                gap: 10px;
            }
            
            .user-info.clickable {
                padding: 0;
                margin: 0;
                background: transparent !important;
                backdrop-filter: none !important;
            }
            
            .user-info.clickable:hover {
                background: transparent !important;
            }
            
            .top-header {
                padding: 0;
            }
            
            .header-menu-btn,
            .search-btn {
                width: 40px;
                height: 40px;
                font-size: 16px;
            }
            

            
            
            
            /* Mobile Menu Modal Fixes */
            .header-modal {
                z-index: 1005 !important;
            }
            
            .header-modal {
                z-index: 10005 !important;
            }
            
            .header-modal-content {
                max-height: 90vh !important;
                bottom: 0 !important;
                z-index: 10006 !important;
            }
            
            .header-menu-items {
                padding-bottom: 150px !important; /* Extra padding for bottom nav */
                max-height: calc(90vh - 80px);
            }
            
            .header-menu-item {
                background: #ffffff !important;
                border: 2px solid #e9ecef !important;
                color: #1a1a1a !important;
                font-weight: 600;
                min-height: 56px;
                display: flex !important;
                align-items: center !important;
            }
            
            .header-menu-item:hover,
            .header-menu-item:active,
            .header-menu-item:focus {
                background: linear-gradient(135deg, #667eea, #764ba2) !important;
                color: #ffffff !important;
                border-color: transparent !important;
            }
            
            .header-menu-item i {
                color: inherit;
            }
            
            .header-menu-item span {
                display: block !important;
            }
        }
    </style>
    {{-- Reels chrome, in the head on purpose.

         The comment sheet and the seek bar are both emitted inside the feed near
         the top of the page, but their CSS used to ship with the scripts at the
         very bottom of the body -- 66KB and 148KB further down the response.
         Until it arrived the sheet had no position and no transform, so it painted
         as a fully open panel in normal flow and then snapped shut, and the seek
         bar rendered as a loose block. That is the flash on first load. --}}
    @include('elements.reels-sheets')
    @include('elements.reels-scrubber-style')
@stop

@section('content')
{{-- Installed first: play()/pause() must be made race-safe before any script below calls them --}}
@include('elements.reels-playback')
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Top Left Menu Button -->
    <div class="top-header">
        <div class="top-header__left">
            <button id="header-menu-btn" class="header-menu-btn">
                <i class="fas fa-bars"></i>
            </button>
            @include('elements.reels-live-button')
        </div>
        @auth
            {{-- Gamification: level + daily streak. Points at the rewards hub, which is
                 where there is actually something to do, rather than the badge wall. --}}
            <a href="{{ route('engagement.hub') }}" class="home-streak-pill" aria-label="{{ __('Rewards') }}">
                <span>🔥 {{ Auth::user()->streak_count ?? 0 }}</span>
                <span class="home-streak-pill__sep" aria-hidden="true"></span>
                <span>Lv {{ Auth::user()->level ?? 1 }}</span>
                @if(($gmRewardsBadge ?? 0) > 0)
                    <span class="home-streak-pill__sep" aria-hidden="true"></span>
                    <span class="home-streak-pill__badge">{{ $gmRewardsBadge }}</span>
                @endif
            </a>
        @endauth
        <button id="search-btn" class="search-btn">
            <i class="fas fa-search"></i>
        </button>
    </div>

    <!-- Mobile menu / gamification + crypto links handled by template/mobile-sidebar
         (Achievements, Leaderboard, Wallet, Token Marketplace, NFT Marketplace, XP + streak card) -->

    <!-- TikTok-Style Full Screen Search (legacy — hidden, use mobile-search) -->
    <div class="search-modal" id="search-modal">
        <div class="search-header">
            <button class="search-back-btn" id="search-back-btn">
                <i class="fas fa-arrow-left"></i>
            </button>
            <div class="search-input-wrapper">
                <i class="fas fa-search search-input-icon"></i>
                <input type="text" id="search-input" placeholder="Search" autocomplete="off">
                <button class="search-clear-btn" id="search-clear-btn">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <button class="search-send-btn" id="search-send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
        <div class="search-content" id="search-results">
            <div class="search-placeholder">Start typing to search...</div>
        </div>
    </div>

    @php
        $fmtCount = function ($n) {
            $n = (int) $n;
            if ($n >= 1000000) { return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M'; }
            if ($n >= 1000) { return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K'; }
            return number_format($n);
        };
    @endphp

    <div class="reels-container">
        <div class="video-feed">
            @if($videos && $videos->count() > 0)
                @foreach($videos as $video)
                    <div class="video-item" data-video-id="{{ $video->id }}">
                        <div class="video-wrapper">
                            <video 
                                src="{{ $video->video_url }}"
                                autoplay
                                loop
                                muted
                                playsinline
                                preload="{{ $loop->first ? 'auto' : 'none' }}"
                                class="video-player"
                                webkit-playsinline
                                @if(!empty($video->poster_url)) poster="{{ $video->poster_url }}" @endif
                            ></video>
                            
                            <!-- Right Action Rail (Instagram Reels style) -->
                            @php
                                $savesCount = $video->saves_count ?? max(1, intdiv((int) $video->likes_count, 5));
                            @endphp
                            <div class="video-actions-left">
                                <div class="action-item">
                                    <button class="action-btn like-btn @if($video->is_liked) liked @endif" data-video-id="{{ $video->id }}">
                                        <i class="@if($video->is_liked) fas @else far @endif fa-heart"></i>
                                    </button>
                                    <span class="action-count">{{ $fmtCount($video->likes_count) }}</span>
                                </div>
                                
                                <div class="action-item">
                                    <button class="action-btn comment-btn" data-video-id="{{ $video->id }}">
                                        <i class="far fa-comment"></i>
                                    </button>
                                    <span class="action-count">{{ $fmtCount($video->comments_count) }}</span>
                                </div>

                                <div class="action-item">
                                    <button class="action-btn repost-btn @if($video->is_reposted) reposted @endif" data-video-id="{{ $video->id }}">
                                        <i class="fas fa-retweet"></i>
                                    </button>
                                    <span class="action-count">{{ $fmtCount($video->reposts_count) }}</span>
                                </div>
                                
                                <div class="action-item">
                                    <button class="action-btn share-btn" data-video-id="{{ $video->id }}">
                                        <i class="far fa-paper-plane"></i>
                                    </button>
                                    <span class="action-count">{{ $fmtCount($video->shares_count) }}</span>
                                </div>

                                <div class="action-item">
                                    <button type="button" class="action-btn save-btn" onclick="toggleSave(this)" data-video-id="{{ $video->id }}">
                                        <i class="far fa-bookmark"></i>
                                    </button>
                                    <span class="action-count">{{ $fmtCount($savesCount) }}</span>
                                </div>

                                <div class="action-item">
                                    <button type="button" class="action-btn more-btn" onclick="handleUploadClick()">
                                        <i class="fas fa-ellipsis"></i>
                                    </button>
                                </div>

                                <div class="action-profile clickable" data-user-id="{{ $video->user->id }}" data-user-username="{{ $video->user->username ?? strtolower(str_replace(' ', '', $video->user->name)) }}" style="background: {{ ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'][abs(crc32($video->user->name)) % 5] }};">
                                    <span>{{ strtoupper(substr($video->user->name, 0, 1)) }}</span>
                                </div>
                            </div>
                            
                            <!-- Right Navigation Controls -->
                            <div class="video-navigation-right">
                                <button class="nav-btn nav-up-btn" onclick="scrollToPreviousVideo()">
                                    <i class="fas fa-chevron-up"></i>
                                </button>
                                <button class="nav-btn nav-down-btn" onclick="scrollToNextVideo()">
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                            </div>
                            
                            <!-- Bottom Video Info -->
                            <div class="video-overlay">
                                <div class="video-info">
                                    <div class="user-row">
                                        <div class="user-info clickable" data-user-id="{{ $video->user->id }}" data-user-username="{{ $video->user->username ?? strtolower(str_replace(' ', '', $video->user->name)) }}">
                                            <div class="user-avatar-initials" style="background: {{ ['#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7'][abs(crc32($video->user->name)) % 5] }};">
                                                {{ strtoupper(substr($video->user->name, 0, 1)) }}{{ strtoupper(substr(explode(' ', $video->user->name)[1] ?? '', 0, 1)) }}
                                            </div>
                                            <div class="user-details">
                                                <h4>{{ '@' . ($video->user->username ?? strtolower(str_replace(' ', '', $video->user->name))) }}</h4>
                                            </div>
                                        </div>
                                        <button type="button" class="follow-btn" onclick="this.classList.toggle('following'); this.textContent = this.classList.contains('following') ? 'Following' : 'Follow';">Follow</button>
                                    </div>

                                    <div class="video-caption">
                                        @if($video->description)
                                            <p class="video-description">{{ $video->description }}</p>
                                        @else
                                            <p class="video-description">{{ $video->title }}</p>
                                        @endif
                                    </div>

                                    <div class="video-audio">
                                        <i class="fas fa-music"></i>
                                        <span class="video-audio__marquee">{{ ($video->user->name) . ' · ' . ($video->audio_title ?? 'Original audio') }}</span>
                                    </div>

                                    @if(!empty($video->location))
                                        <div class="video-location">
                                            <i class="fas fa-location-dot"></i>
                                            <span>{{ $video->location }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            
                            <div class="progress-bar" role="slider" tabindex="0"
                                 aria-label="{{ __('Seek') }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                                <div class="progress-bar__track">
                                    <div class="progress"></div>
                                    <div class="progress-bar__handle"></div>
                                </div>
                                <span class="progress-bar__time">0:00</span>
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

        <!-- Comments Overlay -->
        <div class="comments-overlay" id="comments-overlay"></div>

        <!-- Comments bottom sheet -->
        <div class="comments-sidebar" id="comments-sidebar" role="dialog" aria-modal="true" aria-labelledby="comments-title">
            {{-- The grabber is the drag target, and the header drags too so the whole
                 top edge dismisses -- a 4px line alone is not a real touch target. --}}
            <div class="comments-grabber" data-comments-drag aria-hidden="true"></div>
            <div class="comments-header" data-comments-drag>
                <h3 id="comments-title">Comments <span class="comments-count" id="comments-count" hidden></span></h3>
                <button type="button" class="close-comments" aria-label="Close comments">
                    {{-- stroke has to be set here: these are <line> elements, and with no
                         stroke they paint nothing, which left the button looking empty. --}}
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                </button>
            </div>

            <div class="comments-list" id="comments-list">
                <div class="no-comments">
                    <p>No comments yet</p>
                    <p class="no-comments__hint">Start the conversation.</p>
                </div>
            </div>

            <div class="comment-reactions" role="group" aria-label="Quick reactions">
                <button type="button" class="reaction-emoji" data-emoji="❤️" aria-label="React ❤️">❤️</button>
                <button type="button" class="reaction-emoji" data-emoji="🙌" aria-label="React 🙌">🙌</button>
                <button type="button" class="reaction-emoji" data-emoji="🔥" aria-label="React 🔥">🔥</button>
                <button type="button" class="reaction-emoji" data-emoji="👏" aria-label="React 👏">👏</button>
                <button type="button" class="reaction-emoji" data-emoji="😢" aria-label="React 😢">😢</button>
                <button type="button" class="reaction-emoji" data-emoji="😍" aria-label="React 😍">😍</button>
                <button type="button" class="reaction-emoji" data-emoji="😮" aria-label="React 😮">😮</button>
                <button type="button" class="reaction-emoji" data-emoji="😂" aria-label="React 😂">😂</button>
            </div>

            <div class="comment-form">
                @auth
                    <div class="comment-form__avatar" aria-hidden="true">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}</div>
                @else
                    <div class="comment-form__avatar" aria-hidden="true">?</div>
                @endauth
                <input type="text" id="comment-input" placeholder="Add a comment..." maxlength="500" autocomplete="off">
                <button type="button" id="post-comment" aria-label="Post comment" disabled>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="12" y1="20" x2="12" y2="5"/><polyline points="6 11 12 5 18 11"/></svg>
                </button>
            </div>
        </div>
        </div>
    </div>

    <!-- Bottom Navigation Bar -->
    @include('elements.reels-bottom-nav')
@stop

@section('scripts')
    <!-- Removed feed.js and Post.js as they conflict with home page video functionality -->
    
    <!-- Reels JavaScript -->
    <script>
        
        // Global variables
        let currentVideoIndex = 0;
        
        // Global functions that need to be accessible immediately for onclick handlers
        window.handleUploadClick = function() {
            @auth
                window.location.href = '{{ route("videos.create") }}';
            @else
                window.location.href = '{{ route("register") }}';
            @endauth
        };

        window.toggleSave = function(btn) {
            const icon = btn.querySelector('i');
            const isSaved = btn.classList.toggle('saved');
            if (icon) {
                icon.classList.toggle('fas', isSaved);
                icon.classList.toggle('far', !isSaved);
            }
            const countSpan = btn.closest('.action-item')?.querySelector('.action-count');
            if (countSpan) {
                const parse = (t) => {
                    t = (t || '0').trim().toUpperCase();
                    if (t.endsWith('K')) return Math.round(parseFloat(t) * 1000);
                    if (t.endsWith('M')) return Math.round(parseFloat(t) * 1000000);
                    return parseInt(t.replace(/,/g, ''), 10) || 0;
                };
                const fmt = (n) => {
                    if (n >= 1000000) return (n / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
                    if (n >= 1000) return (n / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
                    return n.toLocaleString();
                };
                let n = parse(countSpan.textContent);
                n = isSaved ? n + 1 : Math.max(0, n - 1);
                countSpan.textContent = fmt(n);
            }
        };
        
        window.scrollToNextVideo = function() {
            const videoItems = document.querySelectorAll('.video-item');
            if (currentVideoIndex < videoItems.length - 1) {
                currentVideoIndex++;
                videoItems[currentVideoIndex].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        };
        
        window.scrollToPreviousVideo = function() {
            const videoItems = document.querySelectorAll('.video-item');
            if (currentVideoIndex > 0) {
                currentVideoIndex--;
                videoItems[currentVideoIndex].scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        };
        
        document.addEventListener('DOMContentLoaded', function() {
            const videoItems = document.querySelectorAll('.video-item');
            const videoPlayers = document.querySelectorAll('.video-player');
            const videoFeed = document.querySelector('.video-feed');
            let isScrolling = false;
            
            
            // Initialize all videos
            videoPlayers.forEach((video, index) => {
                video.setAttribute('playsinline', '');
                video.setAttribute('webkit-playsinline', '');
                // Mute state is owned by elements/reels-sound, which persists the
                // viewer's choice. Forcing it true here made the home feed permanently
                // silent with no control to change it.
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
                        
                        // Increment views when video starts playing
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
                rootMargin: '-10% 0px',
                root: videoFeed || null
            });
            
            // Observe all videos
            videoPlayers.forEach(video => observer.observe(video));

            // Force first visible video to play on load
            const playFirstVisible = () => {
                const firstVideo = videoPlayers[0];
                if (!firstVideo) return;
                pauseAllVideosExcept(firstVideo);
                firstVideo.currentTime = 0;
                firstVideo.play().catch(() => {
                    // Autoplay can be blocked until a user gesture
                });
            };
            setTimeout(playFirstVisible, 300);

            // Resume autoplay after any user interaction
            const resumeOnGesture = () => {
                const currentVideo = videoPlayers[currentVideoIndex] || videoPlayers[0];
                if (currentVideo) {
                    currentVideo.play().catch(() => {});
                }
            };
            document.addEventListener('click', resumeOnGesture, { once: true });
            document.addEventListener('touchstart', resumeOnGesture, { once: true });
            
            function pauseAllVideosExcept(currentVideo) {
                videoPlayers.forEach(video => {
                    if (video !== currentVideo) {
                        video.pause();
                        video.currentTime = 0;
                    }
                });
            }
            
            function startProgressBar(video, progressBar) {
                const updateProgress = () => {
                    if (!video.paused && video.duration > 0) {
                        const progress = (video.currentTime / video.duration) * 100;
                        progressBar.style.width = progress + '%';

                        if (progress < 100 && !video.paused) {
                            requestAnimationFrame(updateProgress);
                        }
                    }
                };
                requestAnimationFrame(updateProgress);
            }

            // Seek bar behaviour now lives in elements/reels-scrubber.
            
            // Like button functionality - using event delegation for dynamic content
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.like-btn');
                if (!btn) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                @guest
                    // If user is not logged in, redirect to login
                    if (confirm('Please login to like videos')) {
                        window.location.href = '{{ route("login") }}';
                    }
                    return;
                @endguest
                
                const videoId = btn.getAttribute('data-video-id');
                const countSpan = btn.closest('.action-item').querySelector('.action-count');
                const icon = btn.querySelector('i');
                let count = parseInt(countSpan.textContent) || 0;
                
                const wasLiked = btn.classList.contains('liked');
                const button = btn;
                    
                // Optimistic UI update
                if (!wasLiked) {
                    button.classList.add('liked');
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    countSpan.textContent = count + 1;
                } else {
                    button.classList.remove('liked');
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    countSpan.textContent = Math.max(0, count - 1);
                }
                
                // Make API call
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    console.error('CSRF token not found');
                    return;
                }
                
                fetch(`/videos/${videoId}/like`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken.content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (response.status === 401) {
                        // Redirect to login if not authenticated
                        if (confirm('Please login to like videos')) {
                            window.location.href = '{{ route("login") }}';
                        }
                        throw new Error('Authentication required');
                    }
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Update with real data from server
                        countSpan.textContent = data.likes_count || countSpan.textContent;
                        if (data.is_liked) {
                            button.classList.add('liked');
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                        } else {
                            button.classList.remove('liked');
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                        }
                        
                        // Show notification if using fallback
                        if (data.note) {
                            console.warn('Using fallback data:', data.note);
                        }
                    } else {
                        // Revert UI on failure
                        if (wasLiked) {
                            button.classList.add('liked');
                            icon.classList.remove('far');
                            icon.classList.add('fas');
                            countSpan.textContent = count;
                        } else {
                            button.classList.remove('liked');
                            icon.classList.remove('fas');
                            icon.classList.add('far');
                            countSpan.textContent = count;
                        }
                        console.error('Like failed:', data.message || data.error || 'Unknown error');
                    }
                })
                .catch(error => {
                    console.error('Error liking video:', error);
                    // Don't revert UI for authentication errors
                    if (error.message === 'Authentication required') {
                        return;
                    }
                    // Revert UI on other errors
                    if (wasLiked) {
                        button.classList.add('liked');
                        icon.classList.remove('far');
                        icon.classList.add('fas');
                        countSpan.textContent = count;
                    } else {
                        button.classList.remove('liked');
                        icon.classList.remove('fas');
                        icon.classList.add('far');
                        countSpan.textContent = count;
                    }
                });
            });
            
            // Comments sidebar functionality
            const commentsSidebar = document.getElementById('comments-sidebar');
            const commentsOverlay = document.getElementById('comments-overlay');
            const closeCommentsBtn = document.querySelector('.close-comments');
            
            function openComments() {
                commentsSidebar.classList.add('active');
                if (commentsOverlay) {
                    commentsOverlay.classList.add('active');
                }
                document.body.classList.add('comments-open');
                if (videoFeed) {
                    videoFeed.dataset.lockedScrollTop = String(videoFeed.scrollTop);
                    videoFeed.style.overflow = 'hidden';
                }
            }
            
            function closeComments() {
                commentsSidebar.classList.remove('active');
                if (commentsOverlay) {
                    commentsOverlay.classList.remove('active');
                }
                document.body.classList.remove('comments-open');
                if (videoFeed) {
                    videoFeed.style.overflow = '';
                    if (videoFeed.dataset.lockedScrollTop != null) {
                        videoFeed.scrollTop = parseFloat(videoFeed.dataset.lockedScrollTop) || 0;
                    }
                }
            }

            // Keep reel feed from scrolling while the comments sheet is open
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
            
            // Comment button functionality - using event delegation for dynamic content
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.comment-btn');
                if (!btn) return;
                
                e.preventDefault();
                e.stopPropagation();
                openComments();
                const videoId = btn.getAttribute('data-video-id');
                loadComments(videoId);
                
                // Store current video ID for posting comments
                currentVideoIdForComments = videoId;
            });
            
            closeCommentsBtn.addEventListener('click', function() {
                closeComments();
            });
            
            // Close comments when clicking on overlay
            if (commentsOverlay) {
                commentsOverlay.addEventListener('click', function() {
                    closeComments();
                });
            }

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && commentsSidebar.classList.contains('active')) {
                    closeComments();
                }
            });
            
            // Share button functionality - using event delegation for dynamic content
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.share-btn');
                if (!btn) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const videoId = btn.getAttribute('data-video-id');
                const countSpan = btn.closest('.action-item').querySelector('.action-count');
                let count = parseInt(countSpan.textContent);
                countSpan.textContent = count + 1;
                
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
                .catch(error => console.error('Error:', error));
                
                const videoUrl = `${window.location.origin}/videos/${videoId}`;
                if (navigator.share) {
                    navigator.share({
                        title: 'Check out this video!',
                        url: videoUrl
                    });
                } else {
                    navigator.clipboard.writeText(videoUrl);
                }
            });
            
            // Repost button functionality - using event delegation for dynamic content
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.repost-btn');
                if (!btn) return;
                
                e.preventDefault();
                e.stopPropagation();
                
                const videoId = btn.getAttribute('data-video-id');
                const countSpan = btn.closest('.action-item').querySelector('.action-count');
                const wasReposted = btn.classList.contains('reposted');
                let count = parseInt(countSpan.textContent);
                
                // Toggle UI immediately
                if (!wasReposted) {
                    btn.classList.add('reposted');
                    btn.style.color = '#17bf63';
                    countSpan.textContent = count + 1;
                } else {
                    btn.classList.remove('reposted');
                    btn.style.color = '#fff';
                    countSpan.textContent = Math.max(0, count - 1);
                }
                
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
                        if (data.is_reposted) {
                            btn.classList.add('reposted');
                            btn.style.color = '#17bf63';
                        } else {
                            btn.classList.remove('reposted');
                            btn.style.color = '#fff';
                        }
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Revert UI on error
                    if (wasReposted) {
                        btn.classList.add('reposted');
                        btn.style.color = '#17bf63';
                        countSpan.textContent = count;
                    } else {
                        btn.classList.remove('reposted');
                        btn.style.color = '#fff';
                        countSpan.textContent = count;
                    }
                });
            });
            
            // Comments functionality
            function renderCommentsList(comments) {
                const commentsList = document.getElementById('comments-list');
                if (!commentsList) return;

                if (!comments || !comments.length) {
                    commentsList.innerHTML = '<div class="no-comments"><p>No comments yet</p><p class="no-comments__hint">Start the conversation.</p></div>';
                    updateCommentCount(0);
                    return;
                }

                commentsList.innerHTML = comments.map(comment => {
                    const user = comment.user || {};
                    const userName = user.name || comment.user_name || 'User';
                    const userUsername = user.username || comment.user_username || '';
                    const commentContent = comment.content || '';
                    const createdAt = comment.created_at || 'Just now';
                    const label = userUsername ? '@' + userUsername : userName;
                    const likes = parseInt(comment.likes_count, 10) || 0;

                    // Comment bodies and display names are user input. They used to be
                    // interpolated into innerHTML as-is, so any markup in a comment was
                    // parsed as HTML -- escape everything that comes back from the API.
                    return `
                        <div class="comment-item" data-comment-id="${escapeHtml(String(comment.id || ''))}">
                            <div class="comment-avatar-initials" style="background: ${getRandomColor()};">${escapeHtml(getUserInitials(userName))}</div>
                            <div class="comment-content">
                                <div class="comment-head">
                                    <strong>${escapeHtml(label)}</strong>
                                    <span class="comment-time">${escapeHtml(formatCommentTime(createdAt))}</span>
                                </div>
                                <p>${escapeHtml(commentContent)}</p>
                                <div class="comment-actions">
                                    <button type="button" class="comment-reply">Reply</button>
                                </div>
                            </div>
                            <button type="button" class="comment-like" aria-pressed="false" aria-label="Like comment">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8z"/></svg>
                                <span>${likes}</span>
                            </button>
                        </div>
                    `;
                }).join('');

                updateCommentCount(comments.length);
            }

            /* The header count is the only thing that tells you how big the thread is
               before you scroll it. Hidden rather than showing "0", which reads as a
               failed load next to the empty state that already says the same thing. */
            /* The API returns an ISO timestamp, which was being printed straight into
               the row -- so comments were stamped '2024-01-01T10:00:00Z'. Anything that
               does not parse as a date is passed through untouched, because some
               callers already hand us a relative string like '2h'. */
            function formatCommentTime(value) {
                if (!value) return 'Just now';

                const then = new Date(value);
                if (isNaN(then.getTime())) return String(value);

                const secs = Math.floor((Date.now() - then.getTime()) / 1000);
                if (secs < 45) return 'Just now';
                if (secs < 3600) return Math.floor(secs / 60) + 'm';
                if (secs < 86400) return Math.floor(secs / 3600) + 'h';
                if (secs < 604800) return Math.floor(secs / 86400) + 'd';
                if (secs < 2592000) return Math.floor(secs / 604800) + 'w';

                return then.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            }

            function updateCommentCount(n) {
                const el = document.getElementById('comments-count');
                if (!el) return;
                el.textContent = n > 0 ? n : '';
                el.hidden = !(n > 0);
            }

            function getDemoComments(videoId) {
                const samples = [
                    { user: { name: 'Priya Shah', username: 'priyashah' }, content: 'This is fire 🔥', created_at: '2h' },
                    { user: { name: 'Dev Patel', username: 'devp' }, content: 'Need the audio name!!', created_at: '5h' },
                    { user: { name: 'Maya Chen', username: 'mayachen' }, content: 'Tag your friend who needs to see this', created_at: '1d' },
                    { user: { name: 'Leo Martins', username: 'leom' }, content: 'Quality content as always 👏', created_at: '1d' },
                    { user: { name: 'Aisha Khan', username: 'aishak' }, content: 'Where was this filmed?', created_at: '2d' },
                ];
                // Slightly vary by video id so each reel feels different
                const offset = Math.abs(parseInt(videoId, 10) || 0) % samples.length;
                return samples.slice(offset).concat(samples.slice(0, offset)).slice(0, 4);
            }

            function loadComments(videoId) {
                const commentsList = document.getElementById('comments-list');
                if (!commentsList) return;
                
                commentsList.innerHTML = '<div class="loading"><p>Loading comments...</p></div>';

                // Demo videos use negative IDs — show sample comments
                if (parseInt(videoId, 10) < 0) {
                    renderCommentsList(getDemoComments(videoId));
                    return;
                }
                
                fetch(`/videos/${videoId}/comments`, {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    
                    if (data.success !== false) {
                        let comments = data.comments || data.data?.comments || [];
                        
                        if (!Array.isArray(comments)) {
                            if (typeof comments === 'object' && comments !== null) {
                                comments = Object.values(comments);
                            } else {
                                comments = [];
                            }
                        }
                        
                        renderCommentsList(comments);
                    } else {
                        renderCommentsList([]);
                    }
                })
                .catch(error => {
                    console.error('Error loading comments:', error);
                    renderCommentsList([]);
                });
            }
            
            // Post comment functionality
            const commentInput = document.getElementById('comment-input');
            const postCommentBtn = document.getElementById('post-comment');
            let currentVideoIdForComments = null;
            
            if (commentInput && postCommentBtn) {
                postCommentBtn.addEventListener('click', function() {
                    const comment = commentInput.value.trim();
                    if (comment && currentVideoIdForComments) {
                        // The button holds an SVG now, so the old textContent swap
                        // would have deleted the icon and never put it back.
                        postCommentBtn.disabled = true;
                        postCommentBtn.classList.add('is-busy');
                        
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
                                loadComments(currentVideoIdForComments);
                                commentInput.value = '';
                                
                                const currentVideo = document.querySelector(`.video-item[data-video-id="${currentVideoIdForComments}"]`);
                                const commentCountSpan = currentVideo.querySelector('.comment-btn').closest('.action-item').querySelector('.action-count');
                                if (commentCountSpan) {
                                    const count = parseInt(commentCountSpan.textContent);
                                    commentCountSpan.textContent = count + 1;
                                }
                            }
                        })
                        .catch(error => console.error('Error posting comment:', error))
                        .finally(() => {
                            postCommentBtn.classList.remove('is-busy');
                            postCommentBtn.disabled = !commentInput.value.trim();
                        });
                    }
                });
                
                // Send stays disabled until there is something to send, so the
                // control says whether it will do anything before you press it.
                const syncPostBtn = () => { postCommentBtn.disabled = !commentInput.value.trim(); };
                commentInput.addEventListener('input', syncPostBtn);
                syncPostBtn();

                commentInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        postCommentBtn.click();
                    }
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
            
            // Increment video views
            function incrementVideoViews(videoId) {
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
                    }
                })
                .catch(error => console.error('Error incrementing views:', error));
            }
            
            // Update current video index on scroll
            if (videoFeed) {
                videoFeed.addEventListener('scroll', function() {
                    const videoItems = document.querySelectorAll('.video-item');
                    videoItems.forEach((item, index) => {
                        const rect = item.getBoundingClientRect();
                        if (rect.top >= 0 && rect.top < window.innerHeight / 2) {
                            currentVideoIndex = index;
                        }
                    });
                });
            }
            
            // Header menu + search: handled by mobile-sidebar.js (#header-menu-btn, #search-btn)
            
            // TikTok-Style Search Modal (legacy fallback if mobile-search unavailable)
            const searchBtn = document.getElementById('search-btn');
            const searchModal = document.getElementById('search-modal');
            const searchBackBtn = document.getElementById('search-back-btn');
            const searchInput = document.getElementById('search-input');
            const searchClearBtn = document.getElementById('search-clear-btn');
            const searchSendBtn = document.getElementById('search-send-btn');
            const searchResults = document.getElementById('search-results');
            
            function openSearchModal() {
                if (searchModal) {
                    searchModal.classList.add('active');
                    setTimeout(() => {
                        if (searchInput) {
                            searchInput.focus();
                        }
                    }, 300);
                }
            }
            
            function closeSearchModal() {
                if (searchModal) {
                    searchModal.classList.remove('active');
                }
                if (searchInput) {
                    searchInput.value = '';
                    updateClearButton();
                }
                if (searchResults) {
                    searchResults.innerHTML = '<div class="search-placeholder">Start typing to search...</div>';
                }
            }
            
            if (searchBtn) {
                searchBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    openSearchModal();
                });
            }
            
            if (searchBackBtn) {
                searchBackBtn.addEventListener('click', function() {
                    closeSearchModal();
                });
            }
            
            function updateClearButton() {
                if (searchClearBtn && searchInput) {
                    if (searchInput.value.trim().length > 0) {
                        searchClearBtn.classList.add('visible');
                    } else {
                        searchClearBtn.classList.remove('visible');
                    }
                }
            }
            
            if (searchClearBtn) {
                searchClearBtn.addEventListener('click', function() {
                    if (searchInput) {
                        searchInput.value = '';
                        searchInput.focus();
                        updateClearButton();
                        if (searchResults) {
                            searchResults.innerHTML = '<div class="search-placeholder">Start typing to search...</div>';
                        }
                    }
                });
            }
            
            // Search functionality with suggestions
            if (searchInput && searchResults) {
                let searchTimeout;
                
                searchInput.addEventListener('input', function() {
                    updateClearButton();
                    clearTimeout(searchTimeout);
                    const query = this.value.trim();
                    
                    if (query.length > 0) {
                        searchTimeout = setTimeout(() => {
                            performSearch(query);
                        }, 300);
                    } else {
                        searchResults.innerHTML = '<div class="search-placeholder">Start typing to search...</div>';
                    }
                });
                
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        const query = this.value.trim();
                        if (query.length > 0) {
                            performSearch(query);
                        }
                    }
                });
            }
            
            if (searchSendBtn) {
                searchSendBtn.addEventListener('click', function() {
                    if (searchInput) {
                        const query = searchInput.value.trim();
                        if (query.length > 0) {
                            performSearch(query);
                        }
                    }
                });
            }
            
            function performSearch(query) {
                if (!searchResults) return;
                
                searchResults.innerHTML = '<div class="search-loading">Searching...</div>';
                
                // Fetch both videos and users
                Promise.all([
                    fetch(`/api/videos/search?query=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    }).then(r => {
                        if (!r.ok) throw new Error('Video search failed');
                        return r.json();
                    }).catch(e => ({ success: false, videos: [] })),
                    fetch(`/search/users?query=${encodeURIComponent(query)}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    }).then(r => {
                        if (!r.ok) throw new Error('User search failed');
                        return r.json();
                    }).catch(e => ({ success: false, data: [] }))
                ])
                .then(([videosData, usersData]) => {
                    let html = '';
                    
                    // Show search term suggestions
                    if (query.length > 0 && query.length < 15) {
                        html += '<div class="search-suggestions">';
                        // Generate suggestions based on query
                        const suggestions = [
                            query + ' edit',
                            query + ' therapy',
                            query + ' video'
                        ].slice(0, 3);
                        
                        suggestions.forEach(suggestion => {
                            html += `
                                <div class="search-suggestion-item" onclick="searchFor('${suggestion.replace(/'/g, "\\'")}')">
                                    <div class="suggestion-icon">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="suggestion-text">${escapeHtml(suggestion)}</div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    
                    // Show user results - handle both paginated and non-paginated responses
                    let users = [];
                    if (usersData.success) {
                        if (Array.isArray(usersData.data)) {
                            users = usersData.data;
                        } else if (usersData.data && Array.isArray(usersData.data.data)) {
                            users = usersData.data.data;
                        }
                    }
                    
                    if (users.length > 0) {
                        html += '<div class="search-suggestions">';
                        users.slice(0, 5).forEach(user => {
                            // Handle different user object structures
                            const userObj = user.user || user;
                            const username = userObj.username || userObj.name || 'user';
                            const displayName = userObj.name || username;
                            const avatar = userObj.avatar || '';
                            const initials = getUserInitials(displayName);
                            const isVerified = userObj.is_verified || userObj.verified || false;
                            const bio = userObj.bio || '';
                            
                            html += `
                                <div class="user-profile-suggestion" onclick="goToProfile('${escapeHtml(username)}')">
                                    <div class="user-avatar-search">
                                        ${avatar ? `<img src="${escapeHtml(avatar)}" alt="${escapeHtml(displayName)}">` : initials}
                                    </div>
                                    <div class="user-info-search">
                                        <div class="username">
                                            ${escapeHtml(displayName)}
                                            ${isVerified ? '<i class="fas fa-check-circle verified-badge"></i>' : ''}
                                        </div>
                                        <div class="user-desc">@${escapeHtml(username)}${bio ? ' • ' + escapeHtml(bio.substring(0, 50)) : ''}</div>
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    
                    // Show video results with thumbnails
                    if (videosData.success && videosData.videos && videosData.videos.length > 0) {
                        html += '<div class="search-suggestions video-grid">';
                        videosData.videos.slice(0, 12).forEach(video => {
                            const videoTitle = video.title || 'Untitled Video';
                            const userName = video.user_name || video.user?.name || 'Unknown';
                            const videoId = video.id;
                            const thumbnail = video.thumbnail_url || video.video_url || '';
                            const likesCount = video.likes_count || 0;
                            const viewsCount = video.views_count || 0;
                            
                            // Format numbers (1000 -> 1K, 1000000 -> 1M)
                            const formatCount = (count) => {
                                if (count >= 1000000) return (count / 1000000).toFixed(1) + 'M';
                                if (count >= 1000) return (count / 1000).toFixed(1) + 'K';
                                return count;
                            };
                            
                            html += `
                                <div class="search-result-video" onclick="goToVideo('${videoId}')">
                                    <div class="video-thumbnail">
                                        ${thumbnail ? `
                                            <video 
                                                src="${escapeHtml(thumbnail)}" 
                                                poster="${escapeHtml(thumbnail)}"
                                                muted 
                                                loop 
                                                playsinline
                                                onmouseover="this.play()" 
                                                onmouseout="this.pause()"
                                                style="width: 100%; height: 100%; object-fit: cover;"
                                            ></video>
                                        ` : `
                                            <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-video" style="font-size: 32px; color: rgba(255,255,255,0.5);"></i>
                                            </div>
                                        `}
                                        <div class="video-overlay-stats">
                                            <span><i class="fas fa-heart"></i> ${formatCount(likesCount)}</span>
                                        </div>
                                    </div>
                                    <div class="video-info-search">
                                        <h4>${escapeHtml(videoTitle)}</h4>
                                        <p><i class="fas fa-user-circle"></i> ${escapeHtml(userName)}</p>
                                        ${viewsCount > 0 ? `<p class="video-views"><i class="fas fa-eye"></i> ${formatCount(viewsCount)} views</p>` : ''}
                                    </div>
                                </div>
                            `;
                        });
                        html += '</div>';
                    }
                    
                    if (html === '') {
                        html = `<div class="no-search-results">No results found for "${escapeHtml(query)}"</div>`;
                    }
                    
                    searchResults.innerHTML = html;
                })
                .catch(error => {
                    console.error('Search error:', error);
                    searchResults.innerHTML = '<div class="no-search-results">Search failed. Please try again.</div>';
                });
            }
            
            function searchFor(query) {
                if (searchInput) {
                    searchInput.value = query;
                    updateClearButton();
                    performSearch(query);
                }
            }
            
            function goToProfile(username) {
                window.location.href = '/' + username;
            }
            
            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
            
            window.searchFor = searchFor;
            window.goToProfile = goToProfile;
            
            window.goToVideo = function(videoId) {
                const videoElement = document.querySelector(`.video-item[data-video-id="${videoId}"]`);
                if (videoElement) {
                    closeSearchModal();
                    setTimeout(() => {
                        videoElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 300);
                } else {
                    // If video not found in current feed, redirect to video page
                    window.location.href = `/videos/${videoId}`;
                }
            };
            
            // Profile click functionality - using event delegation for dynamic content
            document.addEventListener('click', function(e) {
                const userInfo = e.target.closest('.user-info.clickable, .action-profile.clickable');
                if (!userInfo) return;
                
                e.stopPropagation();
                e.preventDefault();
                
                const username = userInfo.getAttribute('data-user-username');
                
                @auth
                    // If logged in, go to user's profile
                    if (username) {
                        window.location.href = '/' + username;
                    }
                @else
                    // If not logged in, show login prompt
                    if (confirm('Please login to view profiles')) {
                        window.location.href = '{{ route("login") }}';
                    }
                @endauth
            });
        });
    </script>
@include('elements.reels-sheets-behaviour')
@include('elements.reels-scrubber')
@include('elements.reels-hud')
@include('elements.reels-sound')
@include('elements.reels-fit')
@include('elements.reels-prefetch')
@stop