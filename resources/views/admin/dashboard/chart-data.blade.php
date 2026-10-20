@extends('voyager::master')

@section('page_title', 'Dashboard Chart Data')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-bar-chart"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Dashboard Chart Data</h1>
                    <p class="jf-dash-page-header__desc">Revenue, wallet, token, and distribution trends for the last {{ $days }} days.</p>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <span class="jf-dash-btn jf-dash-btn--blue jf-chart-data-page__period">
                    <i class="voyager-calendar"></i>
                    <span class="jf-pill-label">Last {{ $days }} days</span>
                </span>
            </div>
        </div>
    </div>
@stop

@section('content')
    @php
        $chartMeta = [
            'revenue' => [
                'title' => 'Revenue',
                'icon' => 'voyager-dollar',
                'accent' => 'green',
            ],
            'wallets' => [
                'title' => 'Wallets',
                'icon' => 'voyager-wallet',
                'accent' => 'blue',
            ],
            'tokens' => [
                'title' => 'Tokens',
                'icon' => 'voyager-trophy',
                'accent' => 'purple',
            ],
            'distributions' => [
                'title' => 'Distributions',
                'icon' => 'voyager-receipt',
                'accent' => 'amber',
            ],
        ];
    @endphp

    <div class="page-content browse container-fluid jf-dash-page jf-chart-data-page">
        @include('voyager::alerts')
        @include('admin.dashboard.partials.section-nav')

        @if($error)
            <div class="jf-chart-data-page__alert alert alert-danger">{{ $error }}</div>
        @endif

        <div class="jf-chart-data-page__charts">
            @foreach($charts as $chartName => $chartRows)
                @php
                    $meta = $chartMeta[$chartName] ?? [
                        'title' => ucwords(str_replace('_', ' ', $chartName)),
                        'icon' => 'voyager-bar-chart',
                        'accent' => 'blue',
                    ];
                    $rowCount = is_countable($chartRows) ? count($chartRows) : 0;
                @endphp
                <div class="panel panel-bordered jf-dash-card jf-dash-card--chart-data">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--{{ $meta['accent'] }}">
                                <i class="{{ $meta['icon'] }}"></i>
                            </span>
                            <span>{{ $meta['title'] }}</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        @if($rowCount > 0)
                            <div class="jf-chart-data-page__table-wrap">
                                <table class="table table-hover jf-tokens-table jf-chart-data-table">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            @if($chartName === 'revenue')
                                                <th>Total Revenue</th>
                                            @elseif($chartName === 'distributions')
                                                <th>Distribution Count</th>
                                                <th>Total Distributed</th>
                                            @else
                                                <th>Count</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($chartRows as $row)
                                            <tr>
                                                <td class="jf-chart-data-table__date">{{ $row->date ?? 'Unknown' }}</td>
                                                @if($chartName === 'revenue')
                                                    <td><span class="jf-chart-data-table__money">${{ number_format($row->total ?? 0, 2) }}</span></td>
                                                @elseif($chartName === 'distributions')
                                                    <td>{{ number_format($row->count ?? 0) }}</td>
                                                    <td><span class="jf-chart-data-table__money">${{ number_format($row->total_amount ?? 0, 2) }}</span></td>
                                                @else
                                                    <td>{{ number_format($row->count ?? 0) }}</td>
                                                @endif
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="jf-dash-card__empty">No chart data found for this period.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@stop
