@extends('layouts.user-no-nav')

@section('page_title', __('Bookmarks'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/libs/swiper/swiper-bundle.min.css',
            '/libs/photoswipe/dist/photoswipe.css',
            '/libs/photoswipe/dist/default-skin/default-skin.css',
            '/css/posts/post.css',
            '/css/pages/checkout.css'
         ])->withFullUrl()
    !!}
    <link rel="stylesheet" href="{{ asset('css/pages/bookmarks.css') }}?v=20260712c">
    @if(getSetting('feed.post_box_max_height'))
        @include('elements.feed.fixed-height-feed-posts', ['height' => getSetting('feed.post_box_max_height')])
    @endif
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/pages/checkout.js',
            '/js/PostsPaginator.js',
            '/js/CommentsPaginator.js',
            '/js/Post.js',
             '/js/pages/lists.js',
            '/js/pages/bookmarks.js',
            '/libs/swiper/swiper-bundle.min.js',
            '/js/plugins/media/photoswipe.js',
            '/libs/photoswipe/dist/photoswipe-ui-default.min.js',
            '/js/plugins/media/mediaswipe.js',
            '/js/plugins/media/mediaswipe-loader.js',
         ])->withFullUrl()
    !!}
@stop

@section('content')
@php
    $bmDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
    $bmEmpty = $posts->count() === 0;
    $bmActiveTab = $activeTab ?? 'all';
@endphp
<div class="bookmarks-page bookmarks-page--{{ $bmDark ? 'dark' : 'light' }}{{ $bmEmpty ? ' bookmarks-page--empty' : '' }}">
    <div class="bookmarks-page__scroll">
        <header class="bookmarks-page__header d-none d-md-flex">
            <div class="bookmarks-page__inner">
                <h1 class="bookmarks-page__title">{{ __('Bookmarks') }}</h1>
            </div>
        </header>

        <div class="bookmarks-page__tabs">
            @include('elements.bookmarks.bookmarks-menu', ['activeTab' => $bmActiveTab])
        </div>

        <div class="bookmarks-page__inner">
            <div class="bookmarks-list{{ $bmEmpty ? ' bookmarks-list--empty' : '' }}">
                @include('elements.feed.posts-load-more')
                <div class="feed-box posts-wrapper bookmarks-feed">
                    @include('elements.feed.posts-wrapper', ['posts' => $posts, 'emptyVariant' => 'bookmarks'])
                </div>
                @include('elements.feed.posts-loading-spinner')
            </div>
        </div>
    </div>
</div>

<div class="d-none">
    <ion-icon name="heart"></ion-icon>
    <ion-icon name="heart-outline"></ion-icon>
</div>

@include('elements.checkout.checkout-box')

@include('elements.standard-dialog',[
    'dialogName' => 'comment-delete-dialog',
    'title' => __('Delete comment'),
    'content' => __('Are you sure you want to delete this comment?'),
    'actionLabel' => __('Delete'),
    'actionFunction' => 'Post.deleteComment();',
])
@stop
