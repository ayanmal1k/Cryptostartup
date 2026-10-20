@extends('layouts.user-no-nav')

@section('page_title', __('Messenger'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/libs/@selectize/selectize/dist/css/selectize.css',
            '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css',
            '/libs/dropzone/dist/dropzone.css',
            '/libs/photoswipe/dist/photoswipe.css',
            '/libs/photoswipe/dist/default-skin/default-skin.css',
            '/css/pages/checkout.css'
         ])->withFullUrl()
    !!}
    <link rel="stylesheet" href="{{ asset('css/pages/messenger.css') }}?v=20260713a">
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/messenger/messenger.js',
            '/js/messenger/elements.js',
            '/libs/@selectize/selectize/dist/js/standalone/selectize.min.js',
            '/libs/dropzone/dist/dropzone.js',
            '/js/FileUpload.js',
            '/js/plugins/media/photoswipe.js',
            '/libs/photoswipe/dist/photoswipe-ui-default.min.js',
            '/js/plugins/media/mediaswipe.js',
            '/js/plugins/media/mediaswipe-loader.js',
            '/js/pages/lists.js',
            '/js/pages/checkout.js',
            '/libs/pusher-js-auth/lib/pusher-auth.js'
         ])->withFullUrl()
    !!}
@stop

@section('content')
    @php
        $messengerDark = Cookie::get('app_theme') == null
            ? getSetting('site.default_user_theme') == 'dark'
            : Cookie::get('app_theme') == 'dark';
    @endphp
    @include('elements.uploaded-file-preview-template')
    @include('elements.photoswipe-container')
    @include('elements.report-user-or-post',['reportStatuses' => ListsHelper::getReportTypes()])
    @include('elements.feed.post-delete-dialog')
    @include('elements.feed.post-list-management')
    @include('elements.messenger.message-price-dialog')
    @include('elements.checkout.checkout-box')
    @include('elements.attachments-uploading-dialog')
    @include('elements.messenger.locked-message-no-attachments-dialog')
    <div class="messenger-page messenger-page--{{ $messengerDark ? 'dark' : 'light' }}">
        <div class="messenger-page__glow messenger-page__glow--tl" aria-hidden="true"></div>
        <div class="messenger-page__glow messenger-page__glow--br" aria-hidden="true"></div>
    <div class="row no-gutters messenger-page__inner">
        <div class="col-12">
            <div class="container messenger {{ $lastContactID ? 'messenger--has-chat' : 'messenger--list-only' }}">
                <div class="row no-gutters h-100">
                    <div class="col-12 col-md-4 col-lg-3 conversations-wrapper">
                        <div class="conversations-header d-none d-md-flex justify-content-between align-items-center">
                            <h5 class="conversations-title mb-0 text-truncate">{{ __('Messages') }}</h5>
                            <a href="javascript:void(0)" class="pointer-cursor new-conversation-toggle" aria-label="{{ trans_choice('Send a new message', ['user' => 0]) }}">
                                <span class="new-conversation-toggle__icon" aria-hidden="true">
                                    @include('elements.icon',['icon'=>'create-outline','variant'=>'medium'])
                                </span>
                            </a>
                        </div>

                        <div class="messenger-list-toolbar">
                            <div class="messenger-list-filter-wrap">
                                <button type="button" class="messenger-list-filter" id="messenger-list-filter-btn" aria-haspopup="listbox" aria-expanded="false" aria-controls="messenger-list-filter-menu">
                                    <span class="messenger-list-filter__label">{{ __('All messages') }}</span>
                                    @include('elements.icon', ['icon' => 'chevron-down-outline', 'variant' => 'small', 'centered' => true, 'classes' => 'messenger-list-filter__chevron'])
                                </button>
                                <div class="messenger-list-filter-menu" id="messenger-list-filter-menu" role="listbox" hidden>
                                    <button type="button" class="messenger-list-filter-option is-active" data-filter="all" role="option" aria-selected="true">{{ __('All messages') }}</button>
                                    <button type="button" class="messenger-list-filter-option" data-filter="unread" role="option" aria-selected="false">{{ __('Unread') }}</button>
                                </div>
                            </div>
                            <div class="messenger-list-search">
                                <span class="messenger-list-search__icon" aria-hidden="true">
                                    @include('elements.icon', ['icon' => 'search-outline', 'variant' => 'small', 'centered' => true])
                                </span>
                                <input type="search" class="messenger-list-search__input" id="messenger-contacts-search" placeholder="{{ __('Search messages...') }}" autocomplete="off" enterkeyhint="search">
                            </div>
                        </div>

                        <div class="conversations-list">
                            @if($lastContactID == false)
                                @include('elements.messenger.messenger-empty-hero')
                            @else
                                @include('elements.preloading.messenger-contact-box', ['limit'=>3])
                            @endif
                        </div>
                    </div>
                    <div class="col-12 col-md-8 col-lg-9 conversation-wrapper">
                        @include('elements.message-alert')
                        @include('elements.messenger.messenger-conversation-header')
                        @include('elements.messenger.messenger-new-conversation-header')
                        @include('elements.preloading.messenger-conversation-header-box')
                        @include('elements.preloading.messenger-conversation-box')
                        <div class="conversation-content">
                        </div>
                        <div id="messenger-dropzone-hook" class="messenger-dropzone-hook" aria-hidden="true"></div>
                        <div class="messenger-dropzone-previews dropzone-previews w-100"></div>
                        <div class="conversation-writeup {{!$lastContactID ? 'hidden' : ''}}">
                            <div class="messenger-buttons-wrapper">
                                <div class="dropup">
                                    <button class="btn btn-outline-primary btn-rounded-icon messenger-button dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{__('More')}}">
                                        <div class="d-flex justify-content-center align-items-center">
                                            @include('elements.icon',['icon'=>'ellipsis-horizontal-outline','variant'=>''])
                                        </div>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#" onclick="messenger.openPrivateRequestModal(); return false;">
                                            @include('elements.icon',['icon'=>'gift-outline','variant'=>''])
                                            <span class="ml-2">{{__('Private request')}}</span>
                                        </a>
                                        <a class="dropdown-item" href="#" onclick="$('.file-upload-button').trigger('click'); return false;">
                                            @include('elements.icon',['icon'=>'document','variant'=>''])
                                            <span class="ml-2">{{__('Attach file')}}</span>
                                        </a>
                                        @if((GenericHelper::creatorCanEarnMoney(Auth::user()) && !(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))) /*|| Auth::user()->role_id === 1*/)
                                            <a class="dropdown-item" href="#" onclick="messenger.showSetPriceDialog(); return false;">
                                                @include('elements.icon',['icon'=>'lock-open','variant'=>''])
                                                <span class="ml-2">{{__('Message price')}}</span>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                                <button class="btn btn-outline-primary btn-rounded-icon messenger-button attach-file file-upload-button d-none" aria-hidden="true" tabindex="-1" type="button">
                                    <div class="d-flex justify-content-center align-items-center">
                                        @include('elements.icon',['icon'=>'document','variant'=>''])
                                    </div>
                                </button>
                            </div>
                            <form class="message-form">
                                <div class="input-group messageBoxInput-wrapper">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="receiverID" id="receiverID" value="">
                                    <textarea name="message" class="form-control messageBoxInput" placeholder="{{__('Write a message..')}}" onkeyup="messenger.textAreaAdjust(this)" rows="1"></textarea>
                                </div>
                            </form>
                            <div class="messenger-buttons-wrapper">
                                @if((GenericHelper::creatorCanEarnMoney(Auth::user()) && !(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))) /*|| Auth::user()->role_id === 1*/)
                                    <span class="d-none">
                                        <button class="btn btn-outline-primary btn-rounded-icon messenger-button to-tooltip" data-placement="top" title="{{__('Message price')}}" onClick="messenger.showSetPriceDialog()">
                                            <div class="d-flex justify-content-center align-items-center">
                                                <span class="message-price-lock">@include('elements.icon',['icon'=>'lock-open','variant'=>''])</span>
                                                <span class="message-price-close d-none">@include('elements.icon',['icon'=>'lock-closed','variant'=>''])</span>
                                            </div>
                                        </button>
                                    </span>
                                @endif
                                <button class="btn btn-outline-primary btn-rounded-icon messenger-button send-message to-tooltip" onClick="messenger.sendMessage()" data-placement="top" title="{{__('Send message')}}">
                                    <div class="d-flex justify-content-center align-items-center">
                                        @include('elements.icon',['icon'=>'paper-plane','variant'=>''])
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    @include('elements.standard-dialog',[
    'dialogName' => 'message-delete-dialog',
    'title' => __('Delete message'),
    'content' => __('Are you sure you want to delete this message?'),
    'actionLabel' => __('Delete'),
    'actionFunction' => 'messenger.deleteMessage();',
])
@stop
