@extends('voyager::master')

@section('page_title', 'Realtime Dashboard Stats')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-activity"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Realtime Dashboard Stats</h1>
                    <p class="jf-dash-page-header__desc">Live operational counters used by the admin dashboard widgets.</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid jf-dash-page jf-realtime-stats-page">
        @include('voyager::alerts')
        @include('admin.dashboard.partials.section-nav')

        @if($error)
            <div class="jf-realtime-stats-page__alert alert alert-danger">{{ $error }}</div>
        @endif

        <div class="jf-realtime-stats-page__grid">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-alarm-clock',
                    'variant' => 'amber',
                    'label' => 'Pending Distributions',
                    'value' => number_format($stats['pending_distributions'] ?? 0),
                    'footer' => 'Awaiting processing',
                ])
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-warning',
                    'variant' => 'rose',
                    'label' => 'Overdue Distributions',
                    'value' => number_format($stats['overdue_distributions'] ?? 0),
                    'footer' => 'Needs attention',
                ])
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-wallet',
                    'variant' => 'blue',
                    'label' => 'Active Wallets',
                    'value' => number_format($stats['active_wallets'] ?? 0),
                    'footer' => 'Currently active',
                ])
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-dollar',
                    'variant' => 'green',
                    'label' => 'Total Wallet Balance USD',
                    'value' => '$' . number_format($stats['total_wallet_balance_usd'] ?? 0, 2),
                    'footer' => 'USD equivalent',
                ])
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-activity',
                    'variant' => 'purple',
                    'label' => 'Platform Health Score',
                    'value' => number_format($stats['platform_health_score'] ?? 0) . '%',
                    'footer' => 'Live platform status',
                ])
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-people',
                    'variant' => 'blue',
                    'label' => 'New Users Today',
                    'value' => number_format($stats['new_users_today'] ?? 0),
                    'footer' => 'Registered today',
                ])
        </div>

        <div class="row jf-dash-cards-row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--refresh">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-refresh"></i></span>
                            <span>Refresh Details</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="jf-realtime-stats-page__meta">
                            <p class="jf-realtime-stats-page__meta-item">
                                <span class="jf-realtime-stats-page__meta-label">Last updated</span>
                                <span class="jf-realtime-stats-page__meta-value">{{ $stats['last_updated'] ?? now()->format('H:i:s') }}</span>
                            </p>
                            <p class="jf-realtime-stats-page__meta-note">
                                This page is the browser-facing version of the realtime stats endpoint. JavaScript requests still receive JSON.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
