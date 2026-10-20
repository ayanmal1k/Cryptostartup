@extends('layouts.generic')

@section('page_title', __($page->title))
@section('share_url', route('home'))
@section('share_title', getSetting('site.name') . ' - ' . getSetting('site.slogan'))
@section('share_description', getSetting('site.description'))
@section('share_type', 'article')
@section('share_img', GenericHelper::getOGMetaImage())

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/public-page-glass.css') }}?v=20260712s">
@stop

@section('content')
@php
    $ppDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
    $ppShowUpdated = in_array($page->slug, ['help', 'privacy', 'terms-and-conditions']);
@endphp
<div class="public-page public-page--{{ $ppDark ? 'dark' : 'light' }}">
    <div class="public-page__scroll">
        <div class="public-page__inner">
            <header class="public-page__header d-none d-md-block">
                <h1 class="public-page__title">{{ $page->title }}</h1>
                @if($ppShowUpdated)
                    <p class="public-page__meta">{{ __('Last updated') }}: {{ $page->updated_at->format('Y-m-d') }}</p>
                @endif
            </header>

            <article class="public-page__card">
                @if($ppShowUpdated)
                    <div class="public-page__card-top d-md-none">
                        <p class="public-page__meta">{{ __('Last updated') }}: {{ $page->updated_at->format('Y-m-d') }}</p>
                    </div>
                @endif

                <div class="public-page__body">
                    {!! $page->content !!}
                </div>
            </article>
        </div>
    </div>
</div>
@stop
