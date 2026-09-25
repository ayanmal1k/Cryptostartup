@php
    $isDarkFooter = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp

<link rel="stylesheet" href="{{ asset('css/footer.css') }}?v=20260709l">

<footer class="footer-modern py-5 {{ $isDarkFooter ? 'footer-modern--dark' : 'footer-modern--light' }}">
    <div class="container">
        <div class="footer-content-wrapper">
            <div class="footer-top-section">
                <div class="d-flex flex-column flex-md-row justify-content-md-between align-items-center">
                    <div class="footer-brand-section">
                        <div class="headline d-flex">
                            <a href="{{ route('home') }}" class="footer-logo-link">
                                <img class="brand-logo footer-logo" src="{{ asset((Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')) : (Cookie::get('app_theme') == 'dark' ? getSetting('site.dark_logo') : getSetting('site.light_logo')))) }}" alt="{{ __('Site logo') }}">
                            </a>
                        </div>
                        <p class="footer-description-text">{{ getSetting('site.description') ?? 'Premium creators platform for content monetization' }}</p>
                    </div>
                    <div class="d-flex justify-content-md-center align-items-center mt-4 mt-md-0 footer-social-links">
                        @if(getSetting('social-links.facebook_url'))
                            <a href="{{ getSetting('social-links.facebook_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Facebook') }}">
                                @include('elements.icon',['icon'=>'logo-facebook','variant'=>'medium','classes' => 'opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.twitter_url'))
                            <a href="{{ getSetting('social-links.twitter_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Twitter') }}">
                                @include('elements.icon',['icon'=>'logo-twitter','variant'=>'medium','classes' => 'opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.instagram_url'))
                            <a href="{{ getSetting('social-links.instagram_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Instagram') }}">
                                @include('elements.icon',['icon'=>'logo-instagram','variant'=>'medium','classes' => 'opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.whatsapp_url'))
                            <a href="{{ getSetting('social-links.whatsapp_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Whatsapp') }}">
                                @include('elements.icon',['icon'=>'logo-whatsapp','variant'=>'medium','classes' => 'opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.tiktok_url'))
                            <a href="{{ getSetting('social-links.tiktok_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Tiktok') }}">
                                @include('elements.icon',['icon'=>'logo-tiktok','variant'=>'medium','classes' => 'opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.youtube_url'))
                            <a href="{{ getSetting('social-links.youtube_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Youtube') }}">
                                @include('elements.icon',['icon'=>'logo-youtube','variant'=>'medium','classes' => 'opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.telegram_link'))
                            <a href="{{ getSetting('social-links.telegram_link') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Telegram') }}">
                                @include('elements.icon',['icon'=>'paper-plane','variant'=>'medium','classes' => 'text-lg opacity-8'])
                            </a>
                        @endif
                        @if(getSetting('social-links.reddit_url'))
                            <a href="{{ getSetting('social-links.reddit_url') }}" target="_blank" rel="noopener noreferrer" title="{{ __('Reddit') }}">
                                @include('elements.icon',['icon'=>'logo-reddit','variant'=>'medium','classes' => 'text-lg opacity-8'])
                            </a>
                        @endif
                    </div>
                </div>

                <div class="footer-links-section">
                    <div class="d-flex flex-column flex-md-row flex-wrap mt-3 mt-md-4 footer-links">
                        <a href="{{ route('contact') }}" class="footer-link-item">{{ __('Contact') }}</a>
                        @foreach(GenericHelper::getFooterPublicPages() as $page)
                            <a href="{{ route('pages.get', ['slug' => $page->slug]) }}" class="footer-link-item">{{ __($page->title) }}</a>
                        @endforeach
                        <a href="{{ route('community.guidelines') }}" class="footer-link-item" target="_blank" rel="noopener noreferrer">{{ __('Community Guidelines') }}</a>
                        <a href="{{ route('dmca.index') }}" class="footer-link-item">{{ __('DMCA') }}</a>
                        @if(Auth::check())
                            <a href="{{ route('my.settings') }}" class="footer-link-item">{{ __('Settings') }}</a>
                        @endif
                    </div>
                </div>
                <hr class="footer-divider">
            </div>
        </div>

        <div class="footer-bottom-section">
            <div class="copyRightInfo d-flex flex-column-reverse flex-md-row justify-content-md-between align-items-center">
                <div class="d-flex align-items-center justify-content-center mt-3 mt-md-0">
                    <p class="footer-copyright">&copy; {{ date('Y') }} <strong>{{ getSetting('site.name') }}</strong>. {{ __('All rights reserved.') }}</p>
                </div>
                <div class="footer-actions-wrapper">
                    @include('elements.footer.dark-mode-switcher')
                    @include('elements.footer.direction-switcher')
                    @include('elements.footer.language-switcher')
                </div>
            </div>
        </div>
    </div>
</footer>
