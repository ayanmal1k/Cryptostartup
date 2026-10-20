@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('page_title', 'Creator Dashboard')

@section('styles')
    @include('creator.partials.styles')
@endsection

@section('content')
<div class="creator-page {{ $isDarkTheme ? 'creator-page--dark' : 'creator-page--light' }}">
    <div class="creator-page__inner">
        <header class="creator-header">
            <div>
                <h1 class="creator-header__title">
                    <i class="fas fa-chart-line"></i>
                    Creator Dashboard
                </h1>
                <p class="creator-header__subtitle">Track performance, manage content, and grow your audience</p>
            </div>
            <div class="creator-actions">
                <a href="/create" class="creator-btn creator-btn--primary">
                    <i class="fas fa-cloud-upload-alt"></i> Upload Video
            </a>
            @if($isVerified)
                    <a href="{{ route('streams.create') }}" class="creator-btn creator-btn--secondary">
                    <i class="fas fa-broadcast-tower"></i> Go Live
                </a>
            @endif
        </div>
        </header>

        @include('creator.partials.nav', ['active' => 'dashboard'])

    @if($verificationStatus === 'not_submitted')
            <div class="creator-alert creator-alert--danger">
                <div class="creator-alert__icon"><i class="fas fa-id-card"></i></div>
                <div class="creator-alert__body">
                <h4>Verify Your Identity</h4>
                <p>Complete ID verification to unlock livestreaming and earn more trust from your audience.</p>
            </div>
                <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="creator-btn creator-btn--primary">
                    <i class="fas fa-id-card"></i> Verify Now
            </a>
        </div>
    @elseif($verificationStatus === 'pending')
            <div class="creator-alert creator-alert--warning">
                <div class="creator-alert__icon"><i class="fas fa-clock"></i></div>
                <div class="creator-alert__body">
                <h4>Verification Pending</h4>
                    <p>Your ID verification is being reviewed. This usually takes 1–2 business days.</p>
                </div>
        </div>
    @elseif($verificationStatus === 'rejected')
            <div class="creator-alert creator-alert--danger">
                <div class="creator-alert__icon"><i class="fas fa-times-circle"></i></div>
                <div class="creator-alert__body">
                <h4>Verification Rejected</h4>
                <p>Your verification was not approved. Please try again with clear, valid documents.</p>
            </div>
                <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="creator-btn creator-btn--primary">
                    <i class="fas fa-redo"></i> Try Again
            </a>
        </div>
    @elseif($isVerified)
            <div class="creator-alert creator-alert--success">
                <div class="creator-alert__icon"><i class="fas fa-shield-halved"></i></div>
                <div class="creator-alert__body">
                <h4>Verified Creator</h4>
                <p>Your identity is verified. You have access to all creator features including livestreaming.</p>
            </div>
        </div>
    @endif
    
        <div class="creator-stats">
            <div class="creator-stat creator-stat--views">
                <div class="creator-stat__icon"><i class="fas fa-eye"></i></div>
                <div class="creator-stat__label">Views</div>
                <div class="creator-stat__value">{{ number_format($totalViews) }}</div>
            </div>
            <div class="creator-stat creator-stat--likes">
                <div class="creator-stat__icon"><i class="fas fa-heart"></i></div>
                <div class="creator-stat__label">Likes</div>
                <div class="creator-stat__value">{{ number_format($totalLikes) }}</div>
        </div>
            <div class="creator-stat creator-stat--comments">
                <div class="creator-stat__icon"><i class="fas fa-comment"></i></div>
                <div class="creator-stat__label">Comments</div>
                <div class="creator-stat__value">{{ number_format($totalComments) }}</div>
            </div>
            <div class="creator-stat creator-stat--videos">
                <div class="creator-stat__icon"><i class="fas fa-video"></i></div>
                <div class="creator-stat__label">Videos</div>
                <div class="creator-stat__value">{{ number_format($totalVideos) }}</div>
            </div>
            <div class="creator-stat creator-stat--streams">
                <div class="creator-stat__icon"><i class="fas fa-broadcast-tower"></i></div>
                <div class="creator-stat__label">Livestreams</div>
                <div class="creator-stat__value">{{ number_format($totalStreams) }}</div>
            </div>
        </div>
        
        <div class="creator-section">
            <h2><i class="fas fa-video"></i> Recent Videos</h2>
            <a href="{{ route('creator.videos') }}" class="creator-section__link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
    </div>
    
    @if($videos->count() > 0)
            <div class="creator-grid">
            @foreach($videos->take(4) as $video)
                    <article class="creator-card">
                        <div class="creator-card__thumb">
                        @if($video->thumbnail_url)
                            <img src="{{ $video->thumbnail_url }}" alt="{{ $video->title }}">
                        @else
                            <video src="{{ $video->video_url }}" muted></video>
                        @endif
                            <div class="creator-card__badges">
                                <span class="creator-badge">
                                <i class="fas fa-eye"></i> {{ number_format($video->views_count ?? 0) }}
                            </span>
                        </div>
                    </div>
                        <div class="creator-card__body">
                            <h3 class="creator-card__title">{{ $video->title }}</h3>
                            <div class="creator-card__meta">
                            <span><i class="fas fa-heart"></i> {{ number_format($video->likes_count ?? 0) }}</span>
                            <span><i class="fas fa-comment"></i> {{ number_format($video->comments_count ?? 0) }}</span>
                        </div>
                            <div class="creator-card__date">
                                <i class="far fa-clock"></i> {{ $video->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </article>
            @endforeach
        </div>
    @else
            <div class="creator-empty">
                <div class="creator-empty__icon"><i class="fas fa-video"></i></div>
            <h3>No videos yet</h3>
            <p>Start creating content to grow your audience!</p>
                <a href="/create" class="creator-btn creator-btn--primary">
                    <i class="fas fa-plus"></i> Upload Your First Video
            </a>
        </div>
    @endif
    
        <div class="creator-section">
            <h2><i class="fas fa-broadcast-tower"></i> Recent Livestreams</h2>
            <a href="{{ route('creator.streams') }}" class="creator-section__link">
                View All <i class="fas fa-arrow-right"></i>
            </a>
    </div>
    
    @if($streams->count() > 0)
            <div class="creator-grid">
            @foreach($streams->take(4) as $stream)
                    <article class="creator-card">
                        <div class="creator-card__thumb">
                        @if($stream->thumbnail)
                            <img src="{{ $stream->thumbnail_url }}" alt="{{ $stream->title }}">
                        @else
                                <div class="creator-card__thumb-placeholder">
                                    <i class="fas fa-broadcast-tower"></i>
                            </div>
                        @endif
                            <div class="creator-card__badges">
                            @if($stream->is_live)
                                    <span class="creator-badge creator-badge--live">
                                    <i class="fas fa-circle"></i> LIVE
                                </span>
                            @endif
                                <span class="creator-badge">
                                <i class="fas fa-users"></i> {{ number_format($stream->viewer_count ?? 0) }}
                            </span>
                        </div>
                    </div>
                        <div class="creator-card__body">
                            <h3 class="creator-card__title">{{ $stream->title }}</h3>
                            <div class="creator-card__meta">
                            <span><i class="fas fa-clock"></i> {{ $stream->formatted_duration }}</span>
                            <span><i class="fas fa-users"></i> {{ number_format($stream->peak_viewer_count ?? 0) }} peak</span>
                        </div>
                            <div class="creator-card__date">
                                <i class="far fa-clock"></i> {{ $stream->created_at->diffForHumans() }}
                            </div>
                        </div>
                    </article>
            @endforeach
        </div>
    @else
            <div class="creator-empty">
                <div class="creator-empty__icon"><i class="fas fa-broadcast-tower"></i></div>
            <h3>No livestreams yet</h3>
            @if($isVerified)
                <p>Go live and connect with your audience in real-time!</p>
                    <a href="{{ route('streams.create') }}" class="creator-btn creator-btn--primary">
                        <i class="fas fa-broadcast-tower"></i> Start Your First Stream
                </a>
            @else
                <p>Verify your identity to start livestreaming.</p>
                    <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="creator-btn creator-btn--primary">
                        <i class="fas fa-check-circle"></i> Verify Identity
                </a>
            @endif
        </div>
    @endif
    </div>
</div>
@endsection
