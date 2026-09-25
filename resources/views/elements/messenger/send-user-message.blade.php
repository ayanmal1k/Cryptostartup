@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
@endphp
<div class="modal fade pf-modal-root" tabindex="-1" role="dialog" id="messageModal">
    <div class="modal-dialog modal-dialog-centered pf-modal-dialog" role="document">
        <div class="modal-content pf-modal {{ $isDarkTheme ? 'pf-modal--dark' : 'pf-modal--light' }}">
            <div class="pf-modal__header">
                <div class="pf-modal__header-text">
                    <h5 class="pf-modal__title" id="modal-title-default">{{ isset($user) ? __('Send a new message to',['user' => $user->name]) : __('Send a new message') }}</h5>
                    <p class="pf-modal__sub">{{__('Write a private message')}}</p>
                </div>
                <button type="button" class="pf-modal__close" data-dismiss="modal" aria-label="{{__('Close')}}">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="18" y1="6" x2="6" y2="18"/></svg>
                </button>
            </div>
            <div class="pf-modal__body">
                <div class="new-message-has-contacts">
                    <form id="userMessageForm" role="form" autocomplete="off">
                        <div class="mfv-errorBox"></div>
                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                        @if(!isset($user))
                            <div class="pf-field">
                                <label class="pf-label" for="select-repo">{{__('To')}}</label>
                                <select id="select-repo" name="receiverID" class="repositories form-control input-sm pf-input" placeholder="{{__('To...')}}"></select>
                            </div>
                        @else
                            <input type="hidden" name="receiverID" value="{{$user->id}}" id="receiverID">
                        @endif
                        <div class="pf-field">
                            <label class="pf-label" for="messageText">{{__('Message')}}</label>
                            <textarea class="form-control pf-input pf-textarea" name="message" placeholder="{{__('Your message')}}" id="messageText" rows="4"></textarea>
                        </div>
                    </form>
                </div>
                <div class="new-message-no-contacts pf-modal__empty">
                    {{__("Before sending a new message, please subscribe to a creator a follow a free profile.")}}
                </div>
            </div>
            <div class="pf-modal__footer">
                <div class="new-message-no-contacts">
                    <button type="button" class="pf-btn pf-btn--ghost" data-dismiss="modal">{{__('Close')}}</button>
                </div>
                <div class="new-message-has-contacts">
                    <button type="button" class="pf-btn pf-btn--ghost" data-dismiss="modal">{{__('Close')}}</button>
                    <button type="submit" onclick="messenger.{{isset($user) ? 'sendDMFromProfilePage' : 'createConversation'}}()" class="pf-btn pf-btn--primary new-conversation-label">{{__('Send')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
