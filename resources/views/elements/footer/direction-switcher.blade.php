<button type="button" class="footer-action-btn rtl-mode-switcher" aria-label="{{ GenericHelper::getSiteDirection() == 'rtl' ? __('Switch to LTR') : __('Switch to RTL') }}">
    <span class="footer-action-btn__icon" aria-hidden="true">
        @include('elements.icon',['icon'=>'return-up-back','variant'=>'small', 'centered'=>true])
    </span>
    <span class="footer-action-btn__label">
        @if(GenericHelper::getSiteDirection() == 'rtl')
            {{ __('LTR') }}
        @else
            {{ __('RTL') }}
        @endif
    </span>
</button>
