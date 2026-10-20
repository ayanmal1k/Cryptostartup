@extends('layouts.user-no-nav')

@section('page_title', __('Notifications'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/notifications.css'
         ])->withFullUrl()
    !!}
    <link rel="stylesheet" href="{{ asset('css/pages/notifications.css') }}?v=20260817a">
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/pages/notifications.js'
         ])->withFullUrl()
    !!}
@stop

@section('content')
@php
    $notifDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
    $notifEmpty = $notifications->count() === 0;
@endphp
<div class="notifications-page notifications-page--{{ $notifDark ? 'dark' : 'light' }}{{ $notifEmpty ? ' notifications-page--empty' : '' }}">
    <div class="notifications-page__scroll">
        <header class="notifications-page__header d-none d-md-flex">
            <div class="notifications-page__inner">
                <h1 class="notifications-page__title">{{ __('Notifications') }}</h1>
            </div>
        </header>

        <div class="notifications-page__tabs">
            @include('elements.notifications.notifications-menu')
        </div>

        <div class="notifications-page__inner">
            <div class="notifications-page__toolbar">
                @include('elements.notifications.notifications-toolbar')
            </div>

            @include('elements.notifications.notifications-wrapper', ['notifications' => $notifications])
        </div>
    </div>
</div>
@stop
