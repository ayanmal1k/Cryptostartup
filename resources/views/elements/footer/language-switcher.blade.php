<button type="button" class="footer-action-btn footer-language-switcher" onclick="openLanguageSelectorDialog()" aria-label="{{ __('Change language') }}">
    <span class="footer-action-btn__icon" aria-hidden="true">
        @include('elements.icon',['icon'=>'language','variant'=>'small', 'centered'=>true])
    </span>
    <span class="footer-action-btn__label">{{ __('Language') }}</span>
</button>
