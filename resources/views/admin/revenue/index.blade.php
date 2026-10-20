@extends('voyager::master')

@section('page_title', 'Revenue Management')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-dollar"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Revenue Management</h1>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <a href="{{ route('voyager.revenue.export', request()->query()) }}" class="jf-dash-btn jf-dash-btn--green">
                    <i class="voyager-download"></i>
                    <span class="jf-pill-label">Export CSV</span>
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid jf-dash-page jf-revenue-page">
        @include('voyager::alerts')

        <!-- Statistics Cards -->
        <div class="row jf-revenue-stat-row">
            <div class="col-md-2 col-sm-4 col-xs-6">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-receipt',
                    'accent' => '#4f8cff',
                    'label' => 'Total Shares',
                    'value' => number_format($stats['total_revenue_shares']),
                ])
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-check',
                    'accent' => '#22c55e',
                    'label' => 'Distributed',
                    'value' => number_format($stats['distributed_shares']),
                ])
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-alarm-clock',
                    'accent' => '#f59e0b',
                    'label' => 'Pending',
                    'value' => number_format($stats['pending_shares']),
                ])
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-exclamation',
                    'accent' => '#ef4444',
                    'label' => 'Overdue',
                    'value' => number_format($stats['overdue_shares']),
                ])
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-credit-cards',
                    'accent' => '#7928ca',
                    'label' => 'Distributed Amount',
                    'value' => '$' . number_format($stats['total_distributed_amount'], 2),
                ])
            </div>
            <div class="col-md-2 col-sm-4 col-xs-6">
                @include('admin.dashboard.partials.stat-card', [
                    'icon' => 'voyager-dollar',
                    'accent' => '#f472b6',
                    'label' => 'Pending Amount',
                    'value' => '$' . number_format($stats['pending_amount'], 2),
                ])
            </div>
        </div>

        <!-- Filters -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--filters">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--purple"><i class="voyager-search"></i></span>
                            <span>Search &amp; Filters</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <form method="GET" action="{{ route('voyager.revenue.index') }}" class="jf-revenue-filter">
                            <div class="jf-revenue-filter__field">
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Search users, cryptocurrencies, transactions..."
                                       value="{{ request('search') }}">
                            </div>

                            <div class="jf-revenue-filter__field">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="distributed" {{ request('status') == 'distributed' ? 'selected' : '' }}>Distributed</option>
                                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="overdue" {{ request('status') == 'overdue' ? 'selected' : '' }}>Overdue</option>
                                </select>
                            </div>

                            <div class="jf-revenue-filter__field">
                                <select name="cryptocurrency_id" class="form-control">
                                    <option value="">All Cryptocurrencies</option>
                                    @foreach($cryptocurrencies as $crypto)
                                        <option value="{{ $crypto->id }}" {{ request('cryptocurrency_id') == $crypto->id ? 'selected' : '' }}>
                                            {{ $crypto->name }} ({{ $crypto->symbol }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="jf-revenue-filter__field">
                                <select name="amount_range" class="form-control">
                                    <option value="">All Amounts</option>
                                    <option value="small" {{ request('amount_range') == 'small' ? 'selected' : '' }}>Small (&lt; $100)</option>
                                    <option value="medium" {{ request('amount_range') == 'medium' ? 'selected' : '' }}>Medium ($100 – $1,000)</option>
                                    <option value="large" {{ request('amount_range') == 'large' ? 'selected' : '' }}>Large (&gt; $1,000)</option>
                                </select>
                            </div>

                            <div class="jf-revenue-filter__field">
                                <select name="sort_by" class="form-control">
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Sort by Created</option>
                                    <option value="revenue_amount" {{ request('sort_by') == 'revenue_amount' ? 'selected' : '' }}>Sort by Revenue</option>
                                    <option value="distribution_amount" {{ request('sort_by') == 'distribution_amount' ? 'selected' : '' }}>Sort by Distribution</option>
                                    <option value="percentage" {{ request('sort_by') == 'percentage' ? 'selected' : '' }}>Sort by Percentage</option>
                                </select>
                            </div>

                            <div class="jf-revenue-filter__field">
                                <select name="sort_dir" class="form-control">
                                    <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Descending</option>
                                    <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                </select>
                            </div>

                            <div class="jf-revenue-filter__actions">
                                <button type="submit" class="jf-dash-btn jf-dash-btn--blue">
                                    <i class="voyager-search"></i>
                                    <span class="jf-pill-label">Filter</span>
                                </button>
                                <a href="{{ route('voyager.revenue.index') }}" class="jf-dash-btn jf-dash-btn--purple">
                                    <i class="voyager-refresh"></i>
                                    <span class="jf-pill-label">Clear</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Shares Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--revenue-table revenue-table-panel">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--rose"><i class="voyager-dollar"></i></span>
                            <span>All Revenue Shares</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body revenue-table-panel-body">
                        <div class="table-responsive revenue-table-responsive">
                            <table class="table table-hover jf-tokens-table jf-revenue-table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Cryptocurrency</th>
                                        <th>Transaction</th>
                                        <th>Share</th>
                                        <th>Revenue</th>
                                        <th>Distribution</th>
                                        <th>Status</th>
                                        <th>Priority</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($revenues as $revenue)
                                        <tr class="{{ $revenue->is_overdue ? 'jf-revenue-row--overdue' : '' }}">
                                            <td><strong>#{{ $revenue->id }}</strong></td>
                                            <td>
                                                @if($revenue->user)
                                                    <div class="jf-wallet-user">
                                                        <strong class="jf-wallet-user__name">{{ $revenue->user->name }}</strong>
                                                        <span class="jf-wallet-user__email">{{ $revenue->user->email }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Unknown User</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($revenue->cryptocurrency)
                                                    <div class="jf-token-cell">
                                                        @if($revenue->cryptocurrency->logo)
                                                            <div class="jf-token-cell__avatar jf-token-cell__avatar--sm">
                                                                <img src="{{ Storage::url($revenue->cryptocurrency->logo) }}" alt="{{ $revenue->cryptocurrency->name }}">
                                                            </div>
                                                        @else
                                                            <div class="jf-token-cell__avatar jf-token-cell__avatar--sm jf-token-cell__avatar--placeholder">
                                                                <i class="voyager-trophy"></i>
                                                            </div>
                                                        @endif
                                                        <div class="jf-token-cell__body">
                                                            <span class="jf-token-cell__name">{{ $revenue->cryptocurrency->name }}</span>
                                                            <div class="jf-token-cell__meta">
                                                                <span>{{ $revenue->cryptocurrency->symbol }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Unknown Crypto</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($revenue->transaction_id)
                                                    <code class="jf-wallet-address" title="{{ $revenue->transaction_id }}">{{ substr($revenue->transaction_id, 0, 10) }}…</code>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td><strong>{{ $revenue->formatted_percentage }}</strong></td>
                                            <td>
                                                <strong>{{ $revenue->formatted_revenue_amount }}</strong>
                                                @if($revenue->cryptocurrency)
                                                    <span class="jf-token-cell__meta">{{ $revenue->cryptocurrency->symbol }}</span>
                                                @endif
                                                <div class="jf-revenue-usd">{{ $revenue->formatted_revenue_amount_usd }}</div>
                                            </td>
                                            <td>
                                                <strong>{{ $revenue->formatted_distribution_amount }}</strong>
                                                @if($revenue->cryptocurrency)
                                                    <span class="jf-token-cell__meta">{{ $revenue->cryptocurrency->symbol }}</span>
                                                @endif
                                                <div class="jf-revenue-usd">{{ $revenue->formatted_distribution_amount_usd }}</div>
                                            </td>
                                            <td>
                                                @php
                                                    $statusClass = $revenue->is_distributed ? 'jf-token-badge--success' : ($revenue->is_overdue ? 'jf-token-badge--danger' : 'jf-token-badge--warning');
                                                @endphp
                                                <span class="jf-token-badge {{ $statusClass }}">{{ $revenue->status_text }}</span>
                                                @if($revenue->is_overdue)
                                                    <div class="jf-revenue-overdue"><i class="voyager-exclamation"></i> Overdue</div>
                                                @endif
                                            </td>
                                            <td>
                                                @php
                                                    $priorityClass = match($revenue->priority_level) {
                                                        'high' => 'jf-token-badge--danger',
                                                        'medium' => 'jf-token-badge--warning',
                                                        'low' => 'jf-token-badge--network',
                                                        'completed' => 'jf-token-badge--success',
                                                        default => 'jf-token-badge--network',
                                                    };
                                                @endphp
                                                <span class="jf-token-badge {{ $priorityClass }}">{{ ucfirst($revenue->priority_level) }}</span>
                                            </td>
                                            <td>
                                                {{ $revenue->created_at ? $revenue->created_at->format('M d, Y') : 'N/A' }}
                                                <div class="jf-token-cell__meta">{{ $revenue->time_since_created }}</div>
                                            </td>
                                            <td>
                                                <div class="jf-revenue-actions">
                                                    <button type="button"
                                                            onclick="showRevenueDetails({{ $revenue->id }}); return false;"
                                                            data-toggle="modal"
                                                            data-target="#revenueModal"
                                                            class="jf-dash-btn jf-dash-btn--blue jf-revenue-actions-btn"
                                                            title="View Details">
                                                        <i class="voyager-eye"></i>
                                                        <span class="jf-pill-label">Details</span>
                                                    </button>
                                                    @if($revenue->is_distributed)
                                                        <a href="{{ route('voyager.revenue.mark-pending', $revenue->id) }}"
                                                           onclick="return confirm('Are you sure you want to mark this as pending?')"
                                                           class="jf-dash-btn jf-dash-btn--amber jf-revenue-actions-btn"
                                                           title="Mark as Pending">
                                                            <i class="voyager-refresh"></i>
                                                            <span class="jf-pill-label">Pending</span>
                                                        </a>
                                                    @else
                                                        <a href="{{ route('voyager.revenue.mark-distributed', $revenue->id) }}"
                                                           onclick="return confirm('Are you sure you want to mark this as distributed?')"
                                                           class="jf-dash-btn jf-dash-btn--green jf-revenue-actions-btn"
                                                           title="Mark as Distributed">
                                                            <i class="voyager-check"></i>
                                                            <span class="jf-pill-label">Distribute</span>
                                                        </a>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="11">
                                                <div class="jf-dash-card__empty">No revenue shares found.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($revenues->hasPages())
                            <div class="jf-tokens-pagination">
                                {{ $revenues->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revenue Details Modal -->
    <div class="modal fade jf-wallets-modal jf-revenue-modal" id="revenueModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                    <h4 class="modal-title">Revenue Share Details</h4>
                </div>
                <div class="modal-body" id="revenueModalBody">
                    <div class="text-center">
                        <i class="voyager-refresh"></i>
                        <p>Loading revenue details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
function showRevenueDetails(revenueId) {
    $('#revenueModalBody').html(`
        <div class="text-center">
            <i class="voyager-refresh"></i>
            <p>Loading revenue details...</p>
        </div>
    `);

    $.get('{{ route("voyager.revenue.details", ":id") }}'.replace(':id', revenueId))
        .done(function(data) {
            const detailTable = (rows) => `
                <table class="jf-wallet-details-table">
                    ${rows.map(([label, value]) => `<tr><td>${label}</td><td>${value}</td></tr>`).join('')}
                </table>
            `;

            let priorityBadge = '';
            switch (data.priority_level) {
                case 'high':
                    priorityBadge = '<span class="jf-token-badge jf-token-badge--danger">High Priority</span>';
                    break;
                case 'medium':
                    priorityBadge = '<span class="jf-token-badge jf-token-badge--warning">Medium Priority</span>';
                    break;
                case 'low':
                    priorityBadge = '<span class="jf-token-badge jf-token-badge--network">Low Priority</span>';
                    break;
                case 'completed':
                    priorityBadge = '<span class="jf-token-badge jf-token-badge--success">Completed</span>';
                    break;
                default:
                    priorityBadge = '<span class="jf-token-badge jf-token-badge--network">' + (data.priority_level || 'N/A') + '</span>';
            }

            const statusBadge = data.is_distributed
                ? '<span class="jf-token-badge jf-token-badge--success">' + data.status_text + '</span>'
                : '<span class="jf-token-badge jf-token-badge--warning">' + data.status_text + '</span>';

            const html = `
                <div class="jf-wallet-details">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">User Information</h5>
                                ${detailTable([
                                    ['Name', data.user_name],
                                    ['Email', data.user_email]
                                ])}
                            </div>

                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">Cryptocurrency</h5>
                                ${detailTable([
                                    ['Name', data.cryptocurrency_name],
                                    ['Symbol', data.cryptocurrency_symbol]
                                ])}
                            </div>

                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">Transaction</h5>
                                ${detailTable([
                                    ['Transaction ID', data.transaction_id || 'N/A'],
                                    ['Percentage', data.percentage]
                                ])}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">Revenue Information</h5>
                                ${detailTable([
                                    ['Revenue Amount', data.revenue_amount + ' ' + data.cryptocurrency_symbol + '<div class="jf-revenue-usd">' + data.revenue_amount_usd + '</div>'],
                                    ['Distribution Amount', data.distribution_amount + ' ' + data.cryptocurrency_symbol + '<div class="jf-revenue-usd">' + data.distribution_amount_usd + '</div>'],
                                    ['Status', statusBadge],
                                    ['Priority', priorityBadge]
                                ])}
                            </div>

                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">Timestamps</h5>
                                ${detailTable([
                                    ['Created', data.created_at + '<div class="jf-token-cell__meta">' + data.time_since_created + '</div>'],
                                    ['Distributed', data.distributed_at + '<div class="jf-token-cell__meta">' + data.time_since_distributed + '</div>']
                                ])}
                            </div>

                            ${data.is_overdue ? `
                            <div class="jf-revenue-modal-alert">
                                <i class="voyager-exclamation"></i>
                                <span>This revenue share is overdue for distribution.</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;

            $('#revenueModalBody').html(html);
        })
        .fail(function() {
            $('#revenueModalBody').html(`
                <div class="alert alert-danger">
                    <strong>Error!</strong> Failed to load revenue details.
                </div>
            `);
        });
}
</script>
@stop
