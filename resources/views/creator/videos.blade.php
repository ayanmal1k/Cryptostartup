@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('page_title', 'My Videos - Creator Dashboard')

@section('styles')
    @include('creator.partials.styles')
@endsection

@section('content')
<div class="creator-page {{ $isDarkTheme ? 'creator-page--dark' : 'creator-page--light' }}">
    <div class="creator-page__inner">
        <header class="creator-header">
            <div>
                <h1 class="creator-header__title">
                    <i class="fas fa-video"></i>
                    My Videos
                </h1>
                <p class="creator-header__subtitle">Manage and track all your uploaded content</p>
            </div>
            <div class="creator-actions">
                <a href="/create" class="creator-btn creator-btn--primary">
                    <i class="fas fa-plus"></i> Upload New Video
                </a>
            </div>
        </header>

        @include('creator.partials.nav', ['active' => 'videos'])

        @if($videos->count() > 0)
            <div class="creator-grid">
                @foreach($videos as $video)
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
                                <span class="creator-badge">{{ $video->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="creator-card__body">
                            <h3 class="creator-card__title">{{ $video->title }}</h3>
                            <div class="creator-card__meta">
                                <span><i class="fas fa-heart"></i> {{ number_format($video->likes_count ?? 0) }}</span>
                                <span><i class="fas fa-comment"></i> {{ number_format($video->comments_count ?? 0) }}</span>
                                <span><i class="fas fa-share"></i> {{ number_format($video->shares_count ?? 0) }}</span>
                            </div>
                            <div class="creator-card__actions">
                                <a href="{{ route('videos.edit', $video) }}" class="creator-card__action">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="{{ route('videos.show', $video) }}" class="creator-card__action">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $videos->links() }}
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
    </div>
</div>
@endsection
