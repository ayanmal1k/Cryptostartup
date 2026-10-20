@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('page_title', 'Analytics - Creator Dashboard')

@section('styles')
    @include('creator.partials.styles')
@endsection

@section('content')
<div class="creator-page {{ $isDarkTheme ? 'creator-page--dark' : 'creator-page--light' }}">
    <div class="creator-page__inner">
        <header class="creator-header">
            <div>
                <h1 class="creator-header__title">
                    <i class="fas fa-chart-bar"></i>
                    Analytics
                </h1>
                <p class="creator-header__subtitle">Deep insights into your content performance</p>
            </div>
        </header>

        @include('creator.partials.nav', ['active' => 'analytics'])

        <div class="creator-stats">
            <div class="creator-stat creator-stat--views">
                <div class="creator-stat__icon"><i class="fas fa-eye"></i></div>
                <div class="creator-stat__label">Views</div>
                <div class="creator-stat__value">{{ number_format($stats['total_views']) }}</div>
            </div>
            <div class="creator-stat creator-stat--likes">
                <div class="creator-stat__icon"><i class="fas fa-heart"></i></div>
                <div class="creator-stat__label">Likes</div>
                <div class="creator-stat__value">{{ number_format($stats['total_likes']) }}</div>
            </div>
            <div class="creator-stat creator-stat--comments">
                <div class="creator-stat__icon"><i class="fas fa-comment"></i></div>
                <div class="creator-stat__label">Comments</div>
                <div class="creator-stat__value">{{ number_format($stats['total_comments']) }}</div>
            </div>
            <div class="creator-stat creator-stat--shares">
                <div class="creator-stat__icon"><i class="fas fa-share"></i></div>
                <div class="creator-stat__label">Shares</div>
                <div class="creator-stat__value">{{ number_format($stats['total_shares']) }}</div>
            </div>
            <div class="creator-stat creator-stat--reposts">
                <div class="creator-stat__icon"><i class="fas fa-retweet"></i></div>
                <div class="creator-stat__label">Reposts</div>
                <div class="creator-stat__value">{{ number_format($stats['total_reposts']) }}</div>
            </div>
            <div class="creator-stat creator-stat--videos">
                <div class="creator-stat__icon"><i class="fas fa-video"></i></div>
                <div class="creator-stat__label">Videos</div>
                <div class="creator-stat__value">{{ number_format($stats['total_videos']) }}</div>
            </div>
        </div>

        <div class="creator-stats creator-stats--wide">
            <div class="creator-stat creator-stat--views">
                <div class="creator-stat__icon"><i class="fas fa-chart-line"></i></div>
                <div class="creator-stat__label">Avg. views / video</div>
                <div class="creator-stat__value">{{ number_format($stats['avg_views']) }}</div>
            </div>
            <div class="creator-stat creator-stat--likes">
                <div class="creator-stat__icon"><i class="fas fa-chart-line"></i></div>
                <div class="creator-stat__label">Avg. likes / video</div>
                <div class="creator-stat__value">{{ number_format($stats['avg_likes']) }}</div>
            </div>
        </div>

        <div class="creator-section">
            <h2><i class="fas fa-trophy"></i> Top Performing Videos</h2>
        </div>

        @if($topVideos->count() > 0)
            <div class="creator-table-wrap">
                <table class="creator-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-film"></i> Video</th>
                            <th><i class="fas fa-eye"></i> Views</th>
                            <th><i class="fas fa-heart"></i> Likes</th>
                            <th><i class="fas fa-comment"></i> Comments</th>
                            <th><i class="far fa-calendar-alt"></i> Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topVideos as $video)
                            <tr>
                                <td>
                                    <div class="creator-table__video">
                                        <div class="creator-table__thumb">
                                            @if($video->thumbnail_url)
                                                <img src="{{ $video->thumbnail_url }}" alt="">
                                            @else
                                                <video src="{{ $video->video_url }}" muted></video>
                                            @endif
                                        </div>
                                        <span class="creator-table__title">{{ $video->title }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="creator-metric creator-metric--views">
                                        <i class="fas fa-eye"></i>
                                        {{ number_format($video->views_count ?? 0) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="creator-metric creator-metric--likes">
                                        <i class="fas fa-heart"></i>
                                        {{ number_format($video->likes_count ?? 0) }}
                                    </span>
                                </td>
                                <td>
                                    <span class="creator-metric creator-metric--comments">
                                        <i class="fas fa-comment"></i>
                                        {{ number_format($video->comments_count ?? 0) }}
                                    </span>
                                </td>
                                <td>
                                    <small style="color: var(--cr-text-muted);">
                                        <i class="far fa-calendar-alt"></i> {{ $video->created_at->format('M d, Y') }}
                                    </small>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="creator-empty">
                <div class="creator-empty__icon"><i class="fas fa-chart-bar"></i></div>
                <h3>No analytics data yet</h3>
                <p>Upload videos to start tracking your performance.</p>
            </div>
        @endif
    </div>
</div>
@endsection
