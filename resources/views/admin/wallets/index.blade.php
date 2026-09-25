@extends('voyager::master')

@section('page_title', 'Wallet Management')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-wallet"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Wallet Management</h1>
                    <p class="jf-dash-page-header__desc">Monitor user wallets, balances, and activity across all cryptocurrencies</p>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <a href="{{ route('voyager.wallets.export', request()->query()) }}" class="jf-dash-btn jf-dash-btn--green">
                    <i class="voyager-download"></i>
                    <span class="jf-pill-label">Export CSV</span>
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid jf-dash-page jf-wallets-page">
        @include('voyager::alerts')

        @php
            $activePct = $stats['total_wallets'] > 0
                ? round(($stats['active_wallets'] / $stats['total_wallets']) * 100)
                : 0;
            $withBalancePct = $stats['total_wallets'] > 0
                ? round(($stats['wallets_with_balance'] / $stats['total_wallets']) * 100)
                : 0;
        @endphp

        <!-- Summary banner -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel jf-hero-panel">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h3 style="margin-top: 0;">
                                    <i class="voyager-wallet"></i> Platform Wallet Overview
                                </h3>
                                <p>
                                    {{ number_format($stats['total_wallets']) }} wallets across {{ number_format($stats['unique_users']) }} users
                                    · {{ number_format($stats['active_wallets']) }} active
                                    · {{ number_format($stats['wallets_with_balance']) }} with balance
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <div style="margin-top: 10px;">
                                    <span class="jf-hero-panel__label">Total Balance (USD)</span><br>
                                    <span class="jf-hero-panel__value">${{ number_format($stats['total_balance_usd'], 2) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="jf-stat-cards-row jf-stat-cards-row--five">
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-wallet',
                'accent' => '#4f8cff',
                'label' => 'Total Wallets',
                'value' => number_format($stats['total_wallets']),
                'footer' => 'All user wallets',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-check',
                'accent' => '#22c55e',
                'label' => 'Active',
                'value' => number_format($stats['active_wallets']),
                'footer' => $activePct . '% of total',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-dollar',
                'accent' => '#f59e0b',
                'label' => 'With Balance',
                'value' => number_format($stats['wallets_with_balance']),
                'footer' => $withBalancePct . '% funded',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-credit-cards',
                'accent' => '#7928ca',
                'label' => 'Total Balance USD',
                'value' => '$' . number_format($stats['total_balance_usd'], 0),
                'footer' => 'Combined holdings',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-people',
                'accent' => '#f472b6',
                'label' => 'Unique Users',
                'value' => number_format($stats['unique_users']),
                'footer' => 'Wallet holders',
            ])
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
                        <form method="GET" action="{{ route('voyager.wallets.index') }}" class="jf-wallets-filter">
                            <div class="jf-wallets-filter__field">
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Search wallets, users, cryptocurrencies..."
                                       value="{{ request('search') }}">
                            </div>

                            <div class="jf-wallets-filter__field">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                    <option value="with_balance" {{ request('status') == 'with_balance' ? 'selected' : '' }}>With Balance</option>
                                    <option value="empty" {{ request('status') == 'empty' ? 'selected' : '' }}>Empty</option>
                                </select>
                            </div>

                            <div class="jf-wallets-filter__field">
                                <select name="cryptocurrency_id" class="form-control">
                                    <option value="">All Cryptocurrencies</option>
                                    @foreach($cryptocurrencies as $crypto)
                                        <option value="{{ $crypto->id }}" {{ request('cryptocurrency_id') == $crypto->id ? 'selected' : '' }}>
                                            {{ $crypto->name }} ({{ $crypto->symbol }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="jf-wallets-filter__field">
                                <select name="sort_by" class="form-control">
                                    <option value="created_at" {{ request('sort_by') == 'created_at' ? 'selected' : '' }}>Sort by Created</option>
                                    <option value="updated_at" {{ request('sort_by') == 'updated_at' ? 'selected' : '' }}>Sort by Updated</option>
                                    <option value="balance" {{ request('sort_by') == 'balance' ? 'selected' : '' }}>Sort by Balance</option>
                                </select>
                            </div>

                            <div class="jf-wallets-filter__field">
                                <select name="sort_dir" class="form-control">
                                    <option value="desc" {{ request('sort_dir') == 'desc' ? 'selected' : '' }}>Descending</option>
                                    <option value="asc" {{ request('sort_dir') == 'asc' ? 'selected' : '' }}>Ascending</option>
                                </select>
                            </div>

                            <div class="jf-wallets-filter__actions">
                                <button type="submit" class="jf-dash-btn jf-dash-btn--blue">
                                    <i class="voyager-search"></i>
                                    <span class="jf-pill-label">Filter</span>
                                </button>
                                <a href="{{ route('voyager.wallets.index') }}" class="jf-dash-btn jf-dash-btn--purple">
                                    <i class="voyager-refresh"></i>
                                    <span class="jf-pill-label">Clear</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wallets Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--wallets-table">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--green"><i class="voyager-wallet"></i></span>
                            <span>All Wallets</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <div class="table-responsive">
                            <table class="table table-hover jf-tokens-table" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>User</th>
                                        <th>Cryptocurrency</th>
                                        <th>Balance</th>
                                        <th>Balance USD</th>
                                        <th>Wallet Address</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($wallets as $wallet)
                                        <tr>
                                            <td><strong>#{{ $wallet->id }}</strong></td>
                                            <td>
                                                @if($wallet->user)
                                                    <div class="jf-wallet-user">
                                                        <strong class="jf-wallet-user__name">{{ $wallet->user->name }}</strong>
                                                        <span class="jf-wallet-user__email">{{ $wallet->user->email }}</span>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Unknown User</span>
                                                @endif
                                            </td>
                                            <td>
                                                @if($wallet->cryptocurrency)
                                                    <div class="jf-token-cell">
                                                        @if($wallet->cryptocurrency->logo)
                                                            <div class="jf-token-cell__avatar jf-token-cell__avatar--sm">
                                                                <img src="{{ Storage::url($wallet->cryptocurrency->logo) }}" alt="{{ $wallet->cryptocurrency->name }}">
                                                            </div>
                                                        @else
                                                            <div class="jf-token-cell__avatar jf-token-cell__avatar--sm jf-token-cell__avatar--placeholder">
                                                                <i class="voyager-trophy"></i>
                                                            </div>
                                                        @endif
                                                        <div class="jf-token-cell__body">
                                                            <span class="jf-token-cell__name">{{ $wallet->cryptocurrency->name }}</span>
                                                            <div class="jf-token-cell__meta">
                                                                <span>{{ $wallet->cryptocurrency->symbol }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @else
                                                    <span class="text-muted">Unknown Crypto</span>
                                                @endif
                                            </td>
                                            <td>
                                                <strong>{{ $wallet->formatted_balance }}</strong>
                                                @if($wallet->cryptocurrency)
                                                    <br><small class="text-muted">{{ $wallet->cryptocurrency->symbol }}</small>
                                                @endif
                                            </td>
                                            <td><strong>{{ $wallet->formatted_balance_usd }}</strong></td>
                                            <td>
                                                @if($wallet->wallet_address)
                                                    <code class="jf-wallet-address" title="{{ $wallet->wallet_address }}">{{ $wallet->masked_address }}</code>
                                                    @if($wallet->has_private_key)
                                                        <i class="voyager-key jf-wallet-key" title="Has Private Key"></i>
                                                    @endif
                                                @else
                                                    <span class="text-muted">No Address</span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="jf-token-badge {{ $wallet->is_active ? 'jf-token-badge--success' : 'jf-token-badge--danger' }}">
                                                    {{ $wallet->status_text }}
                                                </span>
                                            </td>
                                            <td>{{ $wallet->created_at ? $wallet->created_at->format('M d, Y') : 'N/A' }}</td>
                                            <td>
                                                <div class="jf-wallet-actions">
                                                    <button type="button"
                                                            onclick="showWalletDetails({{ $wallet->id }}); return false;"
                                                            data-toggle="modal"
                                                            data-target="#walletModal"
                                                            class="jf-dash-btn jf-dash-btn--blue jf-wallet-actions-btn"
                                                            title="View Details">
                                                        <i class="voyager-eye"></i>
                                                        <span class="jf-pill-label">Details</span>
                                                    </button>
                                                    <a href="{{ route('voyager.wallets.toggle-status', $wallet->id) }}"
                                                       onclick="return confirm('Are you sure you want to {{ $wallet->is_active ? 'deactivate' : 'activate' }} this wallet?')"
                                                       class="jf-dash-btn {{ $wallet->is_active ? 'jf-dash-btn--amber' : 'jf-dash-btn--green' }} jf-wallet-actions-btn"
                                                       title="{{ $wallet->is_active ? 'Deactivate' : 'Activate' }} Wallet">
                                                        <i class="voyager-power"></i>
                                                        <span class="jf-pill-label">{{ $wallet->is_active ? 'Off' : 'On' }}</span>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9">
                                                <div class="jf-dash-card__empty">No wallets found.</div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($wallets->hasPages())
                            <div class="jf-tokens-pagination">
                                {{ $wallets->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wallet Details Modal -->
    <div class="modal fade jf-wallets-modal" id="walletModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                    <h4 class="modal-title">Wallet Details</h4>
                </div>
                <div class="modal-body" id="walletModalBody">
                    <div class="text-center">
                        <i class="voyager-refresh"></i>
                        <p>Loading wallet details...</p>
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
function showWalletDetails(walletId) {
    $('#walletModalBody').html(`
        <div class="text-center">
            <i class="voyager-refresh"></i>
            <p>Loading wallet details...</p>
        </div>
    `);

    $.get('{{ route("voyager.wallets.details", ":id") }}'.replace(':id', walletId))
        .done(function(data) {
            const detailTable = (rows) => `
                <table class="jf-wallet-details-table">
                    ${rows.map(([label, value]) => `<tr><td>${label}</td><td>${value}</td></tr>`).join('')}
                </table>
            `;

            const hasAddress = data.wallet_address && data.wallet_address !== 'No Address';

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
                        </div>
                        <div class="col-md-6">
                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">Wallet Information</h5>
                                ${detailTable([
                                    ['Balance', `${data.balance} ${data.cryptocurrency_symbol}`],
                                    ['Balance USD', data.balance_usd],
                                    ['Status', `<span class="jf-token-badge ${data.is_active ? 'jf-token-badge--success' : 'jf-token-badge--danger'}">${data.status_text}</span>`],
                                    ['Has Private Key', data.has_private_key ? '<i class="voyager-check text-success"></i> Yes' : '<i class="voyager-x text-danger"></i> No']
                                ])}
                            </div>

                            <div class="jf-wallet-details__section">
                                <h5 class="jf-wallet-details__heading">Timestamps</h5>
                                ${detailTable([
                                    ['Created', data.created_at],
                                    ['Updated', data.updated_at]
                                ])}
                            </div>
                        </div>
                    </div>

                    <div class="jf-wallet-details__section">
                        <h5 class="jf-wallet-details__heading">Wallet Address</h5>
                        <div class="jf-wallet-address-block">
                            ${hasAddress ? `<code>${data.wallet_address}</code>` : '<span class="jf-wallet-details__empty">No address assigned</span>'}
                        </div>
                    </div>
                </div>
            `;

            $('#walletModalBody').html(html);
        })
        .fail(function() {
            $('#walletModalBody').html(`
                <div class="alert alert-danger">
                    <strong>Error!</strong> Failed to load wallet details.
                </div>
            `);
        });
}
</script>
@stop
