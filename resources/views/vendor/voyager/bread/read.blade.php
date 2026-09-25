@extends('voyager::master')

@section('page_title', __('voyager::generic.view').' '.$dataType->getTranslatedAttribute('display_name_singular'))

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="{{ $dataType->icon }}"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">
                        {{ __('voyager::generic.viewing') }} {{ $dataType->getTranslatedAttribute('display_name_singular') }}
                    </h1>
                    @if($dataType->slug === 'users' && !empty($dataTypeContent->name))
                        <p class="jf-dash-page-header__desc">{{ $dataTypeContent->name }}</p>
                    @endif
                </div>
            </div>
            <div class="jf-dash-page-header__actions jf-bread-read-header-actions">
                @can('edit', $dataTypeContent)
                    <a href="{{ route('voyager.'.$dataType->slug.'.edit', $dataTypeContent->getKey()) }}" class="jf-dash-btn jf-dash-btn--blue">
                        <i class="voyager-edit"></i>
                        <span class="jf-pill-label">{{ __('voyager::generic.edit') }}</span>
                    </a>
                @endcan
                @can('delete', $dataTypeContent)
                    @if($isSoftDeleted)
                        <a href="{{ route('voyager.'.$dataType->slug.'.restore', $dataTypeContent->getKey()) }}" title="{{ __('voyager::generic.restore') }}" class="jf-dash-btn jf-dash-btn--green restore" data-id="{{ $dataTypeContent->getKey() }}" id="restore-{{ $dataTypeContent->getKey() }}">
                            <i class="voyager-trash"></i>
                            <span class="jf-pill-label">{{ __('voyager::generic.restore') }}</span>
                        </a>
                    @else
                        <a href="javascript:;" title="{{ __('voyager::generic.delete') }}" class="jf-dash-btn jf-dash-btn--rose delete" data-id="{{ $dataTypeContent->getKey() }}" id="delete-{{ $dataTypeContent->getKey() }}">
                            <i class="voyager-trash"></i>
                            <span class="jf-pill-label">{{ __('voyager::generic.delete') }}</span>
                        </a>
                    @endif
                @endcan
                @can('browse', $dataTypeContent)
                    <a href="{{ route('voyager.'.$dataType->slug.'.index') }}" class="jf-dash-btn jf-dash-btn--amber">
                        <i class="voyager-list"></i>
                        <span class="jf-pill-label">{{ __('voyager::generic.return_to_list') }}</span>
                    </a>
                @endcan
            </div>
        </div>
    </div>
    @include('voyager::multilingual.language-selector')
@stop

@section('content')
    <div class="page-content read container-fluid jf-dash-page jf-bread-read-page{{ $dataType->slug === 'users' ? ' jf-users-read-page' : '' }}">
        <div class="row">
            <div class="col-md-12">

                <div class="panel panel-bordered jf-dash-card jf-bread-read-card">
                    <div class="jf-bread-read-card__body">
                    {{--  Attachments preview --}}
                    @if($dataType->slug == 'attachments')
                        <div class="jf-bread-read-attachments">
                            @foreach($dataType->editRows as $row)
                                @if($row->field == 'filename' && $dataTypeContent->{$row->field})
                                    <h3 class="jf-bread-read-attachments__title">{{__("Attachment preview")}}</h3>
                                    <div class="identity-files-preview">
                                        @include('vendor.voyager.partials.voyager-file-preview-box', ['asset' => $dataTypeContent->{$row->field}, 'attachment' => $dataTypeContent])
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                <!-- form start -->
                    @foreach($dataType->readRows as $row)
                        @php
                            if ($dataTypeContent->{$row->field.'_read'}) {
                                $dataTypeContent->{$row->field} = $dataTypeContent->{$row->field.'_read'};
                            }
                        @endphp
                        <div class="panel-heading jf-bread-read-row__head" style="border-bottom:0;">
                            <h3 class="panel-title jf-bread-read-row__label">{{ $row->getTranslatedAttribute('display_name') }}</h3>
                        </div>

                        <div class="panel-body jf-bread-read-row__body" style="padding-top:0;">
                            @if (isset($row->details->view))
                                @include($row->details->view, ['row' => $row, 'dataType' => $dataType, 'dataTypeContent' => $dataTypeContent, 'content' => $dataTypeContent->{$row->field}, 'action' => 'read', 'view' => 'read', 'options' => $row->details])
                            @elseif($row->type == "image")
                                <img class="img-responsive jf-bread-read-image"
                                     src="{{ filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL) ? $dataTypeContent->{$row->field} : Voyager::image($dataTypeContent->{$row->field}) }}">
                            @elseif($row->type == 'multiple_images')
                                @if(json_decode($dataTypeContent->{$row->field}))
                                    @foreach(json_decode($dataTypeContent->{$row->field}) as $file)
                                        <img class="img-responsive jf-bread-read-image"
                                             src="{{ filter_var($file, FILTER_VALIDATE_URL) ? $file : Voyager::image($file) }}">
                                    @endforeach
                                @else
                                    <img class="img-responsive jf-bread-read-image"
                                         src="{{ filter_var($dataTypeContent->{$row->field}, FILTER_VALIDATE_URL) ? $dataTypeContent->{$row->field} : Voyager::image($dataTypeContent->{$row->field}) }}">
                                @endif
                            @elseif($row->type == 'relationship')
                                @include('vendor.voyager.bread.relationship', ['view' => 'read', 'options' => $row->details])
                            @elseif($row->type == 'select_dropdown' && property_exists($row->details, 'options') &&
                                    !empty($row->details->options->{$dataTypeContent->{$row->field}})
                            )
                                <span class="jf-bread-read-value"><?php echo $row->details->options->{$dataTypeContent->{$row->field}};?></span>
                            @elseif($row->type == 'select_multiple')
                                @if(property_exists($row->details, 'relationship'))

                                    @foreach(json_decode($dataTypeContent->{$row->field}) as $item)
                                        <span class="jf-bread-read-value">{{ $item->{$row->field}  }}</span>
                                    @endforeach

                                @elseif(property_exists($row->details, 'options'))
                                    @if (!empty(json_decode($dataTypeContent->{$row->field})))
                                        @foreach(json_decode($dataTypeContent->{$row->field}) as $item)
                                            @if (@$row->details->options->{$item})
                                                <span class="jf-bread-read-value">{{ $row->details->options->{$item} . (!$loop->last ? ', ' : '') }}</span>
                                            @endif
                                        @endforeach
                                    @else
                                        <span class="jf-bread-read-value jf-bread-read-value--muted">{{ __('voyager::generic.none') }}</span>
                                    @endif
                                @endif
                            @elseif($row->type == 'date' || $row->type == 'timestamp')
                                <span class="jf-bread-read-value">
                                @if ( property_exists($row->details, 'format') && !is_null($dataTypeContent->{$row->field}) )
                                    {{ \Carbon\Carbon::parse($dataTypeContent->{$row->field})->formatLocalized($row->details->format) }}
                                @else
                                    {{ $dataTypeContent->{$row->field} }}
                                @endif
                                </span>
                            @elseif($row->type == 'checkbox')
                                @if(property_exists($row->details, 'on') && property_exists($row->details, 'off'))
                                    @if($dataTypeContent->{$row->field})
                                        <span class="jf-bread-read-badge jf-bread-read-badge--success">{{ $row->details->on }}</span>
                                    @else
                                        <span class="jf-bread-read-badge jf-bread-read-badge--muted">{{ $row->details->off }}</span>
                                    @endif
                                @else
                                    <span class="jf-bread-read-value">{{ $dataTypeContent->{$row->field} }}</span>
                                @endif
                            @elseif($row->type == 'color')
                                <span class="jf-bread-read-color" style="background-color: {{ $dataTypeContent->{$row->field} }}">{{ $dataTypeContent->{$row->field} }}</span>
                            @elseif($row->type == 'coordinates')
                                @include('voyager::partials.coordinates')
                            @elseif($row->type == 'rich_text_box')
                                @include('voyager::multilingual.input-hidden-bread-read')
                                <div class="jf-bread-read-rich">{!! $dataTypeContent->{$row->field} !!}</div>
                            @elseif($row->type == 'file')
                                @if(json_decode($dataTypeContent->{$row->field}))
                                    @foreach(json_decode($dataTypeContent->{$row->field}) as $file)
                                        <a class="jf-bread-read-link" href="{{ Storage::disk(config('voyager.storage.disk'))->url($file->download_link) ?: '' }}">
                                            {{ $file->original_name ?: '' }}
                                        </a>
                                        <br/>
                                    @endforeach
                                @else
                                    <a class="jf-bread-read-link" href="{{ Storage::disk(config('voyager.storage.disk'))->url($row->field) ?: '' }}">
                                        {{ __('voyager::generic.download') }}
                                    </a>
                                @endif
                            @else
                                @include('voyager::multilingual.input-hidden-bread-read')
                                <p class="jf-bread-read-value">{{ $dataTypeContent->{$row->field} }}</p>
                            @endif
                        </div><!-- panel-body -->
                        @if(!$loop->last)
                            <hr class="jf-bread-read-row__sep" style="margin:0;">
                        @endif
                    @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Single delete modal --}}
    <div class="modal modal-danger fade jf-wallets-modal jf-delete-confirm-modal jf-bread-read-delete-modal" tabindex="-1" id="delete_modal" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="{{ __('voyager::generic.close') }}"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title jf-delete-confirm-modal__title">
                        <span class="jf-delete-confirm-modal__icon" aria-hidden="true"><i class="voyager-trash"></i></span>
                        <span>{{ __('voyager::generic.delete_question') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</span>
                    </h4>
                </div>
                <div class="modal-body">
                    <p class="jf-delete-confirm-modal__message">{{ __('voyager::generic.are_you_sure_delete') }} {{ strtolower($dataType->getTranslatedAttribute('display_name_singular')) }}?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('voyager::generic.cancel') }}</button>
                    <form action="{{ route('voyager.'.$dataType->slug.'.index') }}" id="delete_form" method="POST" class="jf-delete-confirm-modal__form">
                        {{ method_field('DELETE') }}
                        {{ csrf_field() }}
                        <button type="submit" class="btn btn-danger delete-confirm">
                            {{ __('voyager::generic.delete_confirm') }}
                        </button>
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->
@stop

@section('javascript')
    @if ($isModelTranslatable)
        <script>
            $(document).ready(function () {
                $('.side-body').multilingual();
            });
        </script>
    @endif
    <script>
        var deleteFormAction;
        $('.delete').on('click', function (e) {
            var form = $('#delete_form')[0];

            if (!deleteFormAction) {
                // Save form action initial value
                deleteFormAction = form.action;
            }

            form.action = deleteFormAction.match(/\/[0-9]+$/)
                ? deleteFormAction.replace(/([0-9]+$)/, $(this).data('id'))
                : deleteFormAction + '/' + $(this).data('id');

            $('#delete_modal').modal('show');
        });

    </script>
@stop
