@extends('voyager::master')

@section('page_title', 'Platform Dashboard')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-dashboard"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Platform Dashboard</h1>
                    <p class="jf-dash-page-header__desc">Overview of your cryptocurrency platform</p>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <button type="button" class="jf-dash-btn jf-dash-btn--blue" onclick="refreshDashboard()">
                    <i class="voyager-refresh"></i>
                    <span class="jf-pill-label">Refresh</span>
                </button>
                <button type="button" class="jf-dash-btn jf-dash-btn--green" onclick="exportSummary()">
                    <i class="voyager-download"></i>
                    <span class="jf-pill-label">Export</span>
                </button>
            </div>
        </div>
    </div>
@stop

@section('content')
@php
    $totalUsers = $overviewStats['total_users'] ?? 0;
    $totalTokens = $overviewStats['total_tokens'] ?? 0;
    $verifiedTokens = $overviewStats['verified_tokens'] ?? 0;
    $activeWallets = $overviewStats['active_wallets'] ?? 0;
    $totalWallets = $overviewStats['total_wallets'] ?? 0;
    $activeUsersPct = $quickStats['active_users_percentage'] ?? 0;
    $tokenVerifiedPct = $totalTokens > 0 ? ($verifiedTokens / $totalTokens) * 100 : 0;
    $walletActivePct = $totalWallets > 0 ? ($activeWallets / $totalWallets) * 100 : 0;
    $distEfficiency = $quickStats['distribution_efficiency'] ?? 0;
@endphp
    <div class="page-content browse container-fluid jf-dash-page">
        @include('voyager::alerts')
        @include('admin.dashboard.partials.section-nav')
        
        <!-- Platform Health Banner -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel jf-hero-panel">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h3 style="margin-top: 0;">
                                    <i class="voyager-activity"></i> Platform Health Score:
                                    <span id="health-score">{{ $overviewStats['platform_health_score'] ?? 85 }}</span>%
                                </h3>
                                <p>
                                    Real-time monitoring of your cryptocurrency platform performance
                                    <span id="last-updated" style="font-size: 12px;">• Last updated: {{ now()->format('H:i:s') }}</span>
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <div style="margin-top: 10px;">
                                    <i class="voyager-trophy" style="margin-right: 10px;"></i>
                                    <span class="jf-hero-panel__label">Total Platform Value</span><br>
                                    <span class="jf-hero-panel__value">${{ number_format(($quickStats['total_platform_value'] ?? 0), 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Key Metrics Cards -->
        <div class="row">
            <div class="col-md-3">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-people',
                    'accent' => '#4f8cff',
                    'label' => 'Total Users',
                    'value' => number_format($totalUsers),
                    'footer' => '+' . number_format($overviewStats['new_users_today'] ?? 0) . ' today',
                    'valueId' => 'total-users',
                ])
            </div>

            <div class="col-md-3">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-trophy',
                    'accent' => '#22c55e',
                    'label' => 'Total Tokens',
                    'value' => number_format($totalTokens),
                    'footer' => number_format($verifiedTokens) . ' verified',
                ])
            </div>

            <div class="col-md-3">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-wallet',
                    'accent' => '#f59e0b',
                    'label' => 'Active Wallets',
                    'value' => number_format($activeWallets),
                    'footer' => '$' . number_format($overviewStats['total_wallet_balance_usd'] ?? 0, 0) . ' total value',
                    'valueId' => 'active-wallets',
                ])
            </div>

            <div class="col-md-3">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-dollar',
                    'accent' => '#f472b6',
                    'label' => 'Pending Distributions',
                    'value' => number_format($overviewStats['pending_distributions'] ?? 0),
                    'footer' => '<span id="overdue-count">' . number_format($overviewStats['overdue_distributions'] ?? 0) . ' overdue</span>',
                    'valueId' => 'pending-distributions',
                ])
            </div>
        </div>

        <!-- Alerts Section -->
        @if(!empty($alerts))
        <div class="row" id="platform-alerts-row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-platform-alerts" id="platform-alerts-panel">
                    <div class="panel-heading jf-platform-alerts__head">
                        <h3 class="panel-title platform-alerts-title">
                            <span class="platform-alerts-title__icon" aria-hidden="true">
                                <i class="voyager-warning"></i>
                            </span>
                            <span class="platform-alerts-title__text">Platform Alerts</span>
                        </h3>
                    </div>
                    <div class="panel-body platform-alerts-body">
                        @foreach($alerts as $alert)
                            @include('admin.dashboard.partials.platform-alert-item', ['alert' => $alert])
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Quick Actions & Recent Activity -->
        <div class="row jf-dash-cards-row">
            <!-- Quick Actions -->
            <div class="col-md-4">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--actions">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--purple"><i class="voyager-rocket"></i></span>
                            <span>Quick Actions</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="jf-dash-card__content">
                            <div class="jf-quick-actions">
                                <a href="{{ route('voyager.tokens.index') }}" class="jf-quick-action jf-quick-action--blue">
                                    <span class="jf-quick-action__icon"><i class="voyager-trophy"></i></span>
                                    <span class="jf-quick-action__label">Manage Tokens</span>
                                    <span class="jf-quick-action__count">{{ $overviewStats['total_tokens'] ?? 0 }}</span>
                                </a>
                                <a href="{{ route('voyager.wallets.index') }}" class="jf-quick-action jf-quick-action--green">
                                    <span class="jf-quick-action__icon"><i class="voyager-wallet"></i></span>
                                    <span class="jf-quick-action__label">View Wallets</span>
                                    <span class="jf-quick-action__count">{{ $overviewStats['total_wallets'] ?? 0 }}</span>
                                </a>
                                <a href="{{ route('voyager.revenue.index', ['status' => 'pending']) }}" class="jf-quick-action jf-quick-action--amber">
                                    <span class="jf-quick-action__icon"><i class="voyager-dollar"></i></span>
                                    <span class="jf-quick-action__label">Pending Distributions</span>
                                    <span class="jf-quick-action__count">{{ $overviewStats['pending_distributions'] ?? 0 }}</span>
                                </a>
                                <a href="{{ route('voyager.revenue.index', ['status' => 'overdue']) }}" class="jf-quick-action jf-quick-action--rose">
                                    <span class="jf-quick-action__icon"><i class="voyager-exclamation"></i></span>
                                    <span class="jf-quick-action__label">Overdue Distributions</span>
                                    <span class="jf-quick-action__count">{{ $overviewStats['overdue_distributions'] ?? 0 }}</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Tokens -->
            <div class="col-md-4">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--tokens">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-trophy"></i></span>
                            <span>Recent Tokens</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="jf-dash-card__content">
                            @if(!empty($recentActivity['recent_tokens']) && $recentActivity['recent_tokens']->count() > 0)
                                <div class="jf-feed-list">
                                    @foreach($recentActivity['recent_tokens'] as $token)
                                        <div class="jf-feed-item">
                                            @if($token->logo)
                                                <div class="jf-feed-item__avatar">
                                                    <img src="{{ Storage::url($token->logo) }}" alt="{{ $token->name }}">
                                                </div>
                                            @else
                                                <div class="jf-feed-item__avatar jf-feed-item__avatar--placeholder">
                                                    <i class="voyager-trophy"></i>
                                                </div>
                                            @endif
                                            <div class="jf-feed-item__body">
                                                <div class="jf-feed-item__title">
                                                    {{ $token->name }}
                                                    <span class="jf-feed-item__symbol">({{ $token->symbol }})</span>
                                                    @if($token->is_verified)
                                                        <i class="voyager-check jf-feed-item__verified"></i>
                                                    @endif
                                                </div>
                                                <div class="jf-feed-item__meta">
                                                    {{ $token->created_at ? $token->created_at->diffForHumans() : 'Unknown' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="jf-dash-card__empty">No recent tokens found.</div>
                            @endif
                        </div>
                        <div class="jf-dash-card__footer">
                            <a href="{{ route('voyager.tokens.index') }}" class="jf-dash-card__btn jf-dash-card__btn--blue">
                                View All Tokens
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Distributions -->
            <div class="col-md-4">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--distributions">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--green"><i class="voyager-dollar"></i></span>
                            <span>Recent Distributions</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="jf-dash-card__content">
                            @if(!empty($recentActivity['recent_distributions']) && $recentActivity['recent_distributions']->count() > 0)
                                <div class="jf-feed-list">
                                    @foreach($recentActivity['recent_distributions'] as $distribution)
                                        <div class="jf-feed-item jf-feed-item--distribution">
                                            <div class="jf-feed-item__avatar jf-feed-item__avatar--green">
                                                <i class="voyager-check"></i>
                                            </div>
                                            <div class="jf-feed-item__body">
                                                <div class="jf-feed-item__title">
                                                    ${{ number_format($distribution->distribution_amount, 2) }}
                                                    @if($distribution->cryptocurrency)
                                                        <span class="jf-feed-item__symbol">({{ $distribution->cryptocurrency->symbol }})</span>
                                                    @endif
                                                </div>
                                                <div class="jf-feed-item__meta">
                                                    @if($distribution->user)
                                                        {{ $distribution->user->name }} ·
                                                    @endif
                                                    {{ $distribution->distributed_at ? $distribution->distributed_at->diffForHumans() : 'Recently' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="jf-dash-card__empty">No recent distributions found.</div>
                            @endif
                        </div>
                        <div class="jf-dash-card__footer">
                            <a href="{{ route('voyager.revenue.index', ['status' => 'distributed']) }}" class="jf-dash-card__btn jf-dash-card__btn--green">
                                View All Distributions
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Performance Metrics -->
        <div class="row jf-dash-cards-row">
            <!-- Market Overview -->
            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--market">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-bar-chart"></i></span>
                            <span>Market Overview</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="jf-dash-card__content">
                            <div class="jf-metric-grid">
                                <div class="jf-metric-tile jf-metric-tile--blue">
                                    <div class="jf-metric-tile__value">${{ number_format($overviewStats['total_market_cap'] ?? 0, 0) }}</div>
                                    <div class="jf-metric-tile__label">Total Market Cap</div>
                                </div>
                                <div class="jf-metric-tile jf-metric-tile--green">
                                    <div class="jf-metric-tile__value">${{ number_format($overviewStats['total_distributed_amount'] ?? 0, 0) }}</div>
                                    <div class="jf-metric-tile__label">Total Distributed</div>
                                </div>
                                <div class="jf-metric-tile jf-metric-tile--teal">
                                    <div class="jf-metric-tile__value">{{ number_format($quickStats['active_users_percentage'] ?? 0, 1) }}%</div>
                                    <div class="jf-metric-tile__label">Active Users</div>
                                </div>
                                <div class="jf-metric-tile jf-metric-tile--amber">
                                    <div class="jf-metric-tile__value">{{ number_format($quickStats['distribution_efficiency'] ?? 0, 1) }}%</div>
                                    <div class="jf-metric-tile__label">Distribution Efficiency</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- High Priority Items -->
            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--priority">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--rose"><i class="voyager-exclamation"></i></span>
                            <span>High Priority Items</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="jf-dash-card__content">
                            @if(!empty($recentActivity['pending_high_priority']) && $recentActivity['pending_high_priority']->count() > 0)
                                <div class="jf-feed-list">
                                    @foreach($recentActivity['pending_high_priority'] as $priority)
                                        <div class="jf-feed-item jf-priority-item">
                                            <div class="jf-feed-item__avatar jf-feed-item__avatar--rose">
                                                <i class="voyager-clock"></i>
                                            </div>
                                            <div class="jf-feed-item__body">
                                                <div class="jf-feed-item__title">
                                                    ${{ number_format($priority->distribution_amount, 2) }}
                                                    @if($priority->cryptocurrency)
                                                        <span class="jf-feed-item__symbol">({{ $priority->cryptocurrency->symbol }})</span>
                                                    @endif
                                                    @if($priority->created_at && $priority->created_at->diffInDays(now()) > 30)
                                                        <span class="jf-priority-badge jf-priority-badge--danger">Overdue</span>
                                                    @elseif($priority->distribution_amount > 1000)
                                                        <span class="jf-priority-badge jf-priority-badge--warning">High Value</span>
                                                    @endif
                                                </div>
                                                <div class="jf-feed-item__meta">
                                                    @if($priority->user)
                                                        {{ $priority->user->name }} ·
                                                    @endif
                                                    {{ $priority->created_at ? $priority->created_at->diffForHumans() : 'Unknown date' }}
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="jf-priority-clear">
                                    <div class="jf-priority-clear__icon"><i class="voyager-check"></i></div>
                                    <div class="jf-priority-clear__title">All Clear!</div>
                                    <div class="jf-priority-clear__text">No high priority items requiring attention.</div>
                                </div>
                            @endif
                        </div>
                        @if(!empty($recentActivity['pending_high_priority']) && $recentActivity['pending_high_priority']->count() > 0)
                            <div class="jf-dash-card__footer">
                                <a href="{{ route('voyager.revenue.index', ['status' => 'overdue']) }}" class="jf-dash-card__btn jf-dash-card__btn--rose">
                                    View All High Priority
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <div class="panel-body text-center" style="background-color: #f8f9fa;">
                        <small class="text-muted">
                            Dashboard last refreshed: <span id="refresh-time">{{ now()->format('M d, Y H:i:s') }}</span> | 
                            Auto-refresh: <span id="auto-refresh-status">Enabled</span> |
                            <a href="#" onclick="toggleAutoRefresh()">Toggle Auto-refresh</a>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
let autoRefreshEnabled = true;
let refreshInterval;

$(document).ready(function() {
    // Start auto-refresh
    startAutoRefresh();
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();

    // Collapse Platform Alerts section when every alert is dismissed (Bootstrap 3)
    $(document).on('closed.bs.alert', '.platform-alert-item', function() {
        setTimeout(function() {
            var $panel = $('#platform-alerts-panel');
            if ($panel.length && $panel.find('.platform-alert-item').length === 0) {
                $('#platform-alerts-row').remove();
            }
        }, 0);
    });
});

function startAutoRefresh() {
    if (autoRefreshEnabled) {
        refreshInterval = setInterval(function() {
            updateRealtimeStats();
        }, 30000); // Refresh every 30 seconds
    }
}

function stopAutoRefresh() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
}

function toggleAutoRefresh() {
    autoRefreshEnabled = !autoRefreshEnabled;
    $('#auto-refresh-status').text(autoRefreshEnabled ? 'Enabled' : 'Disabled');
    
    if (autoRefreshEnabled) {
        startAutoRefresh();
    } else {
        stopAutoRefresh();
    }
}

function updateRealtimeStats() {
    $.get('{{ route("voyager.dashboard.realtime-stats") }}')
        .done(function(data) {
            $('#pending-distributions').text(Number(data.pending_distributions).toLocaleString());
            $('#overdue-count').text(Number(data.overdue_distributions).toLocaleString() + ' overdue');
            $('#active-wallets').text(Number(data.active_wallets).toLocaleString());
            $('#health-score').text(data.platform_health_score);
            $('#last-updated').text('• Last updated: ' + data.last_updated);
            $('#refresh-time').text(new Date().toLocaleString());
        })
        .fail(function() {
            console.log('Failed to fetch real-time stats');
        });
}

function refreshDashboard() {
    updateRealtimeStats();
    toastr.success('Dashboard refreshed successfully!');
}

function exportSummary() {
    window.location.href = '{{ route("voyager.dashboard.export") }}';
    toastr.info('Exporting dashboard summary...');
}
</script>
@stop
