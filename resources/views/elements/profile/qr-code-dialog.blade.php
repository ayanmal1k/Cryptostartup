@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp
<div class="modal fade pf-modal-root" tabindex="-1" role="dialog" id="qr-code-dialog">
    <div class="modal-dialog modal-dialog-centered pf-modal-dialog" role="document">
        <div class="modal-content pf-modal {{ $isDarkTheme ? 'pf-modal--dark' : 'pf-modal--light' }}">
            <div class="pf-modal__header">
                <div class="pf-modal__header-text">
                    <h5 class="pf-modal__title">{{__('Username QR Code',['username'=>$user->username])}}</h5>
                    <p class="pf-modal__sub">{{__('Scan to open this profile')}}</p>
                </div>
                <button type="button" class="pf-modal__close" data-dismiss="modal" aria-label="{{__('Close')}}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                </button>
            </div>
            <div class="pf-modal__body pf-modal__body--center">
                <div class="pf-qr-frame">
                    <div id="qrcode"></div>
                </div>
            </div>
            <div class="pf-modal__footer">
                <button type="button" class="pf-btn pf-btn--ghost" data-dismiss="modal">{{__('Close')}}</button>
                <button type="button" class="pf-btn pf-btn--primary" onclick="Profile.downloadQRCode()">{{__('Download')}}</button>
            </div>
        </div>
    </div>
</div>
