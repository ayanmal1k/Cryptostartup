@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

@section('page_title',  ucfirst(__($activeSettingsTab)))

@section('scripts')
    {!!
        Minify::javascript(
            array_merge($additionalAssets['js'],[
                '/js/pages/settings/settings.js',
                '/js/suggestions.js',
         ])
        )->withFullUrl()
    !!}
    @if(getSetting('profiles.allow_profile_bio_markdown'))
        <script src="{{asset('/libs/easymde/dist/easymde.min.js')}}"></script>
    @endif
@stop

@section('styles')
    {!!
        Minify::stylesheet(
            array_merge($additionalAssets['css'],[
                '/css/pages/settings.css',
                ])
         )->withFullUrl()
    !!}
    <style>
        .selectize-control.multi .selectize-input>div.active {
            background:#{{getSetting('colors.theme_color_code')}};
        }
    </style>
    @if(getSetting('profiles.allow_profile_bio_markdown'))
        <link href="{{asset('/libs/easymde/dist/easymde.min.css')}}" rel="stylesheet">
    @endif
    <link rel="stylesheet" href="{{ asset('css/pages/settings-glass.css') }}?v=20260817b">
@stop

@section('content')
@php
    $settingsSubsEmpty = $activeSettingsTab === 'subscriptions'
        && isset($subscriptions)
        && $subscriptions->count() === 0;
    $settingsProfile = $activeSettingsTab === 'profile';
    $settingsAccount = $activeSettingsTab === 'account';
    $settingsWallet = $activeSettingsTab === 'wallet';
    $settingsPayments = $activeSettingsTab === 'payments';
    $settingsRates = $activeSettingsTab === 'rates';
    $settingsNotifications = $activeSettingsTab === 'notifications';
    $settingsPrivacy = $activeSettingsTab === 'privacy';
    $settingsSubscriptions = $activeSettingsTab === 'subscriptions';
    $settingsVerify = $activeSettingsTab === 'verify';
@endphp
<div class="settings-page {{ $isDarkTheme ? 'settings-page--dark' : 'settings-page--light' }}{{ $settingsSubsEmpty ? ' settings-page--subs-empty' : '' }}{{ $settingsSubscriptions ? ' settings-page--subscriptions' : '' }}{{ $settingsProfile ? ' settings-page--profile' : '' }}{{ $settingsAccount ? ' settings-page--account' : '' }}{{ $settingsWallet ? ' settings-page--wallet' : '' }}{{ $settingsPayments ? ' settings-page--payments' : '' }}{{ $settingsRates ? ' settings-page--rates' : '' }}{{ $settingsNotifications ? ' settings-page--notifications' : '' }}{{ $settingsPrivacy ? ' settings-page--privacy' : '' }}{{ $settingsVerify ? ' settings-page--verify' : '' }}">
    <div class="container-fluid settings-layout">
        <div class="row">
            <div class="col-12 col-md-4 col-lg-3 mb-3 pr-0 settings-menu">

                <div class="settings-menu-wrapper">

                    <div class="d-none d-md-block">
                        @include('elements.settings.settings-header',['type'=>'generic'])
                    </div>
                    <div class="d-block d-md-none">
                        @include('elements.settings.settings-header',['type'=>'settingTab'])
                    </div>
                    <hr class="mb-0">
                    <div class="">
                        @include('elements.settings.settings-menu',['availableSettings' => $availableSettings])
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-8 col-lg-9 mb-lg-0 settings-content mt-1 mt-md-0 pl-md-0 pr-md-0">
                <div class="ml-3 d-none d-md-flex justify-content-between">
                    <div>
                        <h5 class="text-bold mt-0 mt-md-3 mb-0 {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{ ucfirst(__($activeSettingsTab))}}</h5>
                        <h6 class="mt-2 text-muted">{{__($currentSettingTab['heading'])}}</h6>
                    </div>
                </div>
                <hr class="{{in_array($activeSettingsTab, ['subscriptions','payments']) ? 'mb-0' : ''}} d-none d-md-block mt-2">
                <div class="settings-tab-panel {{ in_array($activeSettingsTab, ['subscriptions', 'payments', 'referrals', 'profile', 'account', 'wallet', 'rates', 'notifications', 'privacy', 'verify']) ? 'settings-tab-panel--flush' : 'px-4 px-md-3' }}">
                    @include('elements.settings.settings-mobile-toolbar')
                    @include('elements.settings.settings-'.$activeSettingsTab)
                </div>
            </div>
        </div>
    </div>
</div>
@stop
