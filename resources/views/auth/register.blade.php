@extends('layouts.no-nav')
@section('page_title', __('Register'))

@section('page_description', getSetting('site.description'))
@section('share_url', route('home'))
@section('share_title', getSetting('site.name') . ' - ' .  __('Register'))
@section('share_description', getSetting('site.description'))
@section('share_type', 'article')
@section('share_img', GenericHelper::getOGMetaImage())

@if(getSetting('security.recaptcha_enabled') && !Auth::check())
    @section('meta')
        {!! NoCaptcha::renderJs() !!}
    @stop
@endif

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/auth.css') }}?v=20260715a">
@stop

@section('content')
<div class="auth-page">
    <div class="auth-aurora" aria-hidden="true">
        <span class="auth-orb auth-orb--1"></span>
        <span class="auth-orb auth-orb--2"></span>
        <span class="auth-orb auth-orb--3"></span>
        <span class="auth-grid"></span>
    </div>
    <div class="auth-page__inner">
        <div class="auth-brand">
            <a href="{{ route('home') }}" class="auth-brand__logo">
                <img src="{{ asset( (Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')) : (Cookie::get('app_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo'))) ) }}" alt="{{ getSetting('site.name') }}">
            </a>
            <h1 class="auth-brand__title">{{ __('Create Account') }}</h1>
            <p class="auth-brand__subtitle">{{ __('Join us and start creating amazing content') }}</p>
        </div>

        <div class="auth-card">
            @include('auth.register-form')
            @include('auth.social-login-box')
        </div>

        <div class="auth-footer">
            <p>
                {{ __('Already have an account?') }}
                <a href="{{ route('login') }}" class="auth-footer__link">{{ __('Sign in') }}</a>
            </p>
        </div>
    </div>
</div>
@endsection
