@extends('voyager::master')

@section('page_title', __('voyager::generic.media'))

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-images"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">{{ __('voyager::generic.media') }}</h1>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content container-fluid jf-dash-page jf-media-page">
        @include('voyager::alerts')
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--media">
                    <div class="panel-body jf-dash-card__body jf-media-card__body">
                        <div id="filemanager">
                            <media-manager
                                base-path="{{ config('voyager.media.path', '/') }}"
                                :show-folders="{{ config('voyager.media.show_folders', true) ? 'true' : 'false' }}"
                                :allow-upload="{{ config('voyager.media.allow_upload', true) ? 'true' : 'false' }}"
                                :allow-move="{{ config('voyager.media.allow_move', true) ? 'true' : 'false' }}"
                                :allow-delete="{{ config('voyager.media.allow_delete', true) ? 'true' : 'false' }}"
                                :allow-create-folder="{{ config('voyager.media.allow_create_folder', true) ? 'true' : 'false' }}"
                                :allow-rename="{{ config('voyager.media.allow_rename', true) ? 'true' : 'false' }}"
                                :allow-crop="{{ config('voyager.media.allow_crop', true) ? 'true' : 'false' }}"
                                :details="{{ json_encode(['thumbnails' => config('voyager.media.thumbnails', []), 'watermark' => config('voyager.media.watermark', (object)[])]) }}"
                            ></media-manager>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
new Vue({
    el: '#filemanager'
});
</script>
@endsection
