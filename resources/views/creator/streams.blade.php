@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('page_title', 'My Livestreams - Creator Dashboard')

@section('styles')
    @include('creator.partials.styles')
@endsection

@section('content')
<div class="creator-page {{ $isDarkTheme ? 'creator-page--dark' : 'creator-page--light' }}">
    <div class="creator-page__inner">
        <header class="creator-header">
            <div>
                <h1 class="creator-header__title">
                    <i class="fas fa-broadcast-tower"></i>
                    My Livestreams
                </h1>
                <p class="creator-header__subtitle">Go live and connect with your audience in real-time</p>
            </div>
            <div class="creator-actions">
                @if($canStream)
                    <a href="{{ route('streams.create') }}" class="creator-btn creator-btn--primary">
                        <i class="fas fa-plus"></i> Go Live
                    </a>
                @else
                    <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="creator-btn creator-btn--primary">
                        <i class="fas fa-check-circle"></i> Verify to Stream
                    </a>
                @endif
            </div>
        </header>

        @include('creator.partials.nav', ['active' => 'streams'])

        @if(!$canStream)
            <div class="creator-alert creator-alert--warning">
                <div class="creator-alert__icon"><i class="fas fa-exclamation-triangle"></i></div>
                <div class="creator-alert__body">
                    <h4>ID Verification Required</h4>
                    <p>You need to verify your identity before you can start livestreaming. This helps keep our community safe and builds trust with your viewers.</p>
                </div>
                <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="creator-btn creator-btn--primary">
                    <i class="fas fa-id-card"></i> Verify Now
                </a>
            </div>
        @endif

        @if($streams->count() > 0)
            <div class="creator-grid">
                @foreach($streams as $stream)
                    <article class="creator-card">
                        <div class="creator-card__thumb">
                            @if($stream->thumbnail)
                                <img src="{{ $stream->thumbnail_url }}" alt="{{ $stream->title }}">
                            @else
                                <div class="creator-card__thumb-placeholder">
                                    <i class="fas fa-broadcast-tower"></i>
                                </div>
                            @endif
                            @if($stream->is_live)
                                <div class="creator-card__badges">
                                    <span class="creator-badge creator-badge--live">
                                        <i class="fas fa-circle"></i> LIVE
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="creator-card__body">
                            <h3 class="creator-card__title">{{ $stream->title }}</h3>
                            <div class="creator-card__meta">
                                <span><i class="fas fa-users"></i> {{ number_format($stream->viewer_count ?? 0) }} viewers</span>
                                <span><i class="fas fa-clock"></i> {{ $stream->formatted_duration }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <span class="creator-status creator-status--{{ $stream->status }}">
                                    {{ ucfirst($stream->status) }}
                                </span>
                                <small style="color: var(--cr-text-muted);">
                                    <i class="far fa-calendar-alt"></i> {{ $stream->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $streams->links() }}
            </div>
        @else
            <div class="creator-empty">
                <div class="creator-empty__icon"><i class="fas fa-broadcast-tower"></i></div>
                <h3>No livestreams yet</h3>
                @if($canStream)
                    <p>Go live and connect with your audience in real-time!</p>
                    <a href="{{ route('streams.create') }}" class="creator-btn creator-btn--primary">
                        <i class="fas fa-plus"></i> Start Your First Stream
                    </a>
                @else
                    <p>Verify your identity to unlock livestreaming.</p>
                    <a href="{{ route('my.settings', ['type' => 'verify']) }}" class="creator-btn creator-btn--primary">
                        <i class="fas fa-check-circle"></i> Verify Identity
                    </a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
