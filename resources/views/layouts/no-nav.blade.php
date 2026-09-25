<!doctype html>
<html dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}" data-theme="{{ GenericHelper::getSiteTheme() == 'dark' ? 'dark' : 'light' }}">
<head>
    @include('template.head')
</head>
<body class="auth-layout" style="display: flex; flex-direction: column; min-height: 100dvh; min-height: 100vh;">
@include('elements.impersonation-header')
@include('elements.global-announcement')
<div class="auth-layout__fill" style="flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0;">
    @yield('content')
</div>
@if(getSetting('compliance.enable_age_verification_dialog'))
    @include('elements.site-entry-approval-box')
@endif
@include('template.jsVars')
@include('template.jsAssets')
@include('elements.language-selector-box')
</body>
</html>
