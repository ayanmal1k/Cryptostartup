<!doctype html>
<html class="h-100" dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}" data-theme="{{ GenericHelper::getSiteTheme() == 'dark' ? 'dark' : 'light' }}">
<head>
    @include('template.head')
</head>
<body class="d-flex flex-column">
@include('elements.impersonation-header')
@include('elements.global-announcement')
@include('template.header')
@include('template.mobile-sidebar')
@include('template.partials.mobile-app-bar')
<div class="flex-fill">
    @yield('content')
</div>
@if(getSetting('compliance.enable_age_verification_dialog'))
    @include('elements.site-entry-approval-box')
@endif
@include('template.footer')
@include('template.jsVars')
@include('template.jsAssets')
@include('elements.language-selector-box')
@auth
    @include('elements.custom-request.create-modal')
    <script src="{{ asset('js/CustomRequest.js') }}"></script>
@endauth
@guest
    @if(getSetting('security.recaptcha_enabled'))
        {!! NoCaptcha::renderJs() !!}
    @endif
    @include('elements.modal-login')
    <script src="{{ asset('js/LoginModal.js') }}"></script>
@endguest
@include('elements.gamification-celebrations')
</body>
</html>
