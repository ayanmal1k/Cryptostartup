<button type="button" class="footer-action-btn dark-mode-switcher" aria-label="{{ Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') ? __('Switch to light mode') : __('Switch to dark mode') }}">
    <span class="footer-action-btn__icon" aria-hidden="true">
        @if(Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark'))
            @include('elements.icon',['icon'=>'contrast-outline','variant'=>'small','centered'=>true])
        @else
            @include('elements.icon',['icon'=>'contrast','variant'=>'small','centered'=>true])
        @endif
    </span>
    <span class="footer-action-btn__label">
        @if(Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark'))
            {{ __('Light') }}
        @else
            {{ __('Dark') }}
        @endif
    </span>
</button>
