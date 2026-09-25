@extends('layouts.generic')

@section('page_title', __('Cryptocurrency'))

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/cryptocurrency-landing.css') }}?v=20260709b">
@stop

@section('content')
@php
    $clDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp
<div class="crypto-landing-page crypto-landing-page--{{ $clDark ? 'dark' : 'light' }}">
    <div class="crypto-landing-page__glow crypto-landing-page__glow--tl" aria-hidden="true"></div>
    <div class="crypto-landing-page__glow crypto-landing-page__glow--br" aria-hidden="true"></div>

    <div class="crypto-landing-page__scroll">
        <div class="crypto-landing-page__inner">
            <article class="crypto-landing-page__card">
                <div class="crypto-landing-page__icon-wrap" aria-hidden="true">
                    @include('elements.icon', ['icon' => 'logo-bitcoin', 'centered' => true, 'variant' => 'medium', 'classes' => 'cl-icon'])
                </div>

                <h1 class="crypto-landing-page__title">{{ __('Cryptocurrency') }}</h1>
                <span class="crypto-landing-page__title-line" aria-hidden="true"></span>

                <p class="crypto-landing-page__description">
                    {{ __('Connect with creators and request custom content through our innovative platform') }}
                </p>

                <ul class="crypto-landing-page__features">
                    <li class="crypto-landing-page__feature">
                        <span class="crypto-landing-page__feature-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'centered' => true, 'variant' => 'xsmall', 'classes' => 'cl-icon'])
                        </span>
                        <span class="crypto-landing-page__feature-text">{{ __('Secure Transactions') }}</span>
                    </li>
                    <li class="crypto-landing-page__feature">
                        <span class="crypto-landing-page__feature-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'centered' => true, 'variant' => 'xsmall', 'classes' => 'cl-icon'])
                        </span>
                        <span class="crypto-landing-page__feature-text">{{ __('Direct Creator Access') }}</span>
                    </li>
                    <li class="crypto-landing-page__feature">
                        <span class="crypto-landing-page__feature-icon" aria-hidden="true">
                            @include('elements.icon', ['icon' => 'checkmark-circle-outline', 'centered' => true, 'variant' => 'xsmall', 'classes' => 'cl-icon'])
                        </span>
                        <span class="crypto-landing-page__feature-text">{{ __('Custom Content Requests') }}</span>
                    </li>
                </ul>

                <div class="crypto-landing-page__actions">
                    <a href="{{ route('custom-requests.marketplace') }}" class="crypto-landing-page__cta">
                        @include('elements.icon', ['icon' => 'add-circle-outline', 'centered' => true, 'variant' => 'small', 'classes' => 'cl-icon'])
                        <span>{{ __('Create Custom Request') }}</span>
                    </a>

                    <div class="crypto-landing-page__links">
                        <a href="{{ route('cryptocurrency.marketplace') }}" class="crypto-landing-page__link">
                            @include('elements.icon', ['icon' => 'trending-up-outline', 'centered' => true, 'variant' => 'xsmall', 'classes' => 'cl-icon'])
                            <span>{{ __('Token Market') }}</span>
                        </a>
                        <a href="{{ route('cryptocurrency.wallet') }}" class="crypto-landing-page__link">
                            @include('elements.icon', ['icon' => 'wallet-outline', 'centered' => true, 'variant' => 'xsmall', 'classes' => 'cl-icon'])
                            <span>{{ __('My Wallet') }}</span>
                        </a>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>
@endsection
