@extends('layouts.user-no-nav')
@section('page_title', $list->name)

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/lists.css') }}?v=20260712c">
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/pages/lists.js'
         ])->withFullUrl()
    !!}
@stop

@section('content')
@php
    $listsDark = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';
    $listsEmpty = count($list->members) === 0;
    $isMemberManageable = $list->type !== \App\Model\UserList::FOLLOWERS_TYPE;
@endphp
<div class="lists-page lists-page--show lists-page--{{ $listsDark ? 'dark' : 'light' }}{{ $listsEmpty ? ' lists-page--empty' : '' }}">
    <div class="lists-page__scroll">
        <div class="lists-page__inner">
            <header class="lists-page__header lists-page__header--show d-none d-md-flex">
                <h1 class="lists-page__title lists-page__title--show">{{ __($list->name) }}</h1>
                @if($list->isManageable)
                    <div class="lists-page__menu dropdown {{ GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft' }}">
                        <button type="button" class="lists-page__menu-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('List options') }}">
                            @include('elements.icon',['icon'=>'ellipsis-horizontal-outline','centered'=>true,'variant'=>'small','classes'=>'ls-icon ls-icon--menu'])
                        </button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <a class="dropdown-item" href="javascript:void(0);" onclick="Lists.showListEditDialog('edit')">{{ __('Rename list') }}</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" onclick="Lists.showListClearConfirmation()">{{ __('Clear list') }}</a>
                            <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="Lists.showListDeleteConfirmation()">{{ __('Delete list') }}</a>
                        </div>
                    </div>
                @endif
            </header>

            @if(!$listsEmpty)
                <p class="lists-page__meta d-none d-md-block">{{ trans_choice('people', count($list->members), ['number' => count($list->members)]) }}</p>
            @endif

            <div class="lists-page__content lists-page__content--show">
                @if(count($list->members))
                    <div class="lists-members list-wrapper">
                        @foreach($list->members as $member)
                            @include('elements.lists.list-member-card', [
                                'profile' => $member,
                                'isListMode' => true,
                                'isListManageable' => $isMemberManageable,
                            ])
                        @endforeach
                    </div>
                @else
                    <div class="lists-empty lists-empty--show">
                        <div class="lists-empty__icon" aria-hidden="true">
                            @include('elements.icon',['icon'=>'people-outline','centered'=>true,'variant'=>'medium','classes'=>'ls-icon ls-icon--empty'])
                        </div>
                        <h2 class="lists-empty__title">{{ __('No profiles available') }}</h2>
                        <p class="lists-empty__text">{{ __('Members you add to this list will appear here.') }}</p>
                        <a href="{{ route('my.lists.all') }}" class="lists-empty__cta lists-empty__cta--link">{{ __('Back to lists') }}</a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@include('elements.lists.list-update-dialog',['mode'=>'edit'])
@include('elements.lists.list-delete-dialog')
@include('elements.lists.list-member-delete-dialog')
@include('elements.lists.list-clear-dialog')
@stop
