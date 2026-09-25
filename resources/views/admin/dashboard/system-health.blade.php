@extends('voyager::master')

@section('page_title', 'Dashboard System Health')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-tools"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Dashboard System Health</h1>
                    <p class="jf-dash-page-header__desc">Database, activity, data integrity, and performance checks.</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        $sectionMeta = [
            'database_connection' => ['icon' => 'voyager-data', 'accent' => 'blue'],
            'recent_activity' => ['icon' => 'voyager-activity', 'accent' => 'green'],
            'data_integrity' => ['icon' => 'voyager-check', 'accent' => 'purple'],
            'performance_metrics' => ['icon' => 'voyager-bar-chart', 'accent' => 'amber'],
        ];
    @endphp

    <div class="page-content browse container-fluid jf-dash-page jf-system-health-page">
        @include('voyager::alerts')
        @include('admin.dashboard.partials.section-nav')

        @if($error)
            <div class="jf-system-health-page__alert alert alert-danger">{{ $error }}</div>
        @endif

        <div class="row jf-dash-cards-row">
            @foreach($health as $section => $details)
                @php
                    $status = $details['status'] ?? 'healthy';
                    $badgeClass = $status === 'error' ? 'danger' : ($status === 'warning' ? 'warning' : 'success');
                    $meta = $sectionMeta[$section] ?? ['icon' => 'voyager-tools', 'accent' => 'blue'];
                @endphp
                <div class="col-md-6">
                    <div class="panel panel-bordered jf-dash-card jf-dash-card--system-health">
                        <div class="panel-heading jf-dash-card__head">
                            <h3 class="panel-title jf-dash-card__title">
                                <span class="jf-dash-card__title-icon jf-dash-card__title-icon--{{ $meta['accent'] }}">
                                    <i class="{{ $meta['icon'] }}"></i>
                                </span>
                                <span>{{ ucwords(str_replace('_', ' ', $section)) }}</span>
                                <span class="jf-system-health-page__status jf-system-health-page__status--{{ $badgeClass }} pull-right">{{ ucfirst($status) }}</span>
                            </h3>
                        </div>
                        <div class="panel-body jf-dash-card__body jf-system-health-page__table-wrap">
                            <table class="table table-condensed jf-system-health-table">
                                <tbody>
                                    @foreach($details as $key => $value)
                                        <tr>
                                            <th style="width: 45%;">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                                            <td>
                                                @if(is_array($value))
                                                    {{ json_encode($value) }}
                                                @else
                                                    {{ $value }}
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@stop
