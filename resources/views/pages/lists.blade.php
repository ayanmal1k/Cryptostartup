@extends('layouts.user-no-nav')
@section('page_title', __('Your lists'))

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
    $listsEmpty = count($lists) === 0;
@endphp
<div class="lists-page lists-page--{{ $listsDark ? 'dark' : 'light' }}{{ $listsEmpty ? ' lists-page--empty' : '' }}">
    <div class="lists-page__scroll">
        <div class="lists-page__inner">
            <header class="lists-page__header">
                <h1 class="lists-page__title d-none d-md-flex">
                    <span class="lists-page__title-icon" aria-hidden="true">
                        @include('elements.icon',['icon'=>'list-outline','centered'=>true,'variant'=>'small','classes'=>'ls-icon ls-icon--title'])
                    </span>
                    <span class="lists-page__title-text">{{ __('Lists') }}</span>
                </h1>
                <button type="button" class="lists-page__add-btn" onclick="Lists.showListEditDialog()" data-toggle="tooltip" data-placement="top" title="{{ __('Add list') }}" aria-label="{{ __('Add list') }}">
                    @include('elements.icon',['icon'=>'add-circle-outline','centered'=>true,'variant'=>'small','classes'=>'ls-icon ls-icon--add'])
                </button>
            </header>

            <div class="lists-page__content">
                <div class="lists-wrapper">
                    @if(count($lists))
                        @foreach($lists as $key => $list)
                            @include('elements.lists.list-box', ['list' => $list, 'isLastItem' => (count($lists) == $key + 1)])
                        @endforeach
                    @else
                        <div class="lists-empty">
                            <div class="lists-empty__icon" aria-hidden="true">
                                @include('elements.icon',['icon'=>'list-outline','centered'=>true,'variant'=>'medium','classes'=>'ls-icon ls-icon--empty'])
                            </div>
                            <h2 class="lists-empty__title">{{ __('No lists available') }}</h2>
                            <p class="lists-empty__text">{{ __('Create lists to organize the creators you follow.') }}</p>
                            <button type="button" class="lists-empty__cta" onclick="Lists.showListEditDialog()">
                                @include('elements.icon',['icon'=>'add-circle-outline','centered'=>true,'variant'=>'small','classes'=>'ls-icon'])
                                <span>{{ __('Add list') }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@include('elements.lists.list-update-dialog',['mode'=>'create'])
@stop
