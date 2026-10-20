@extends('voyager::master')

@section('page_title', 'Token Management')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-trophy"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Token Management</h1>
                    <p class="jf-dash-page-header__desc">Manage cryptocurrencies, verification, supply, and market activity</p>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <a href="{{ route('voyager.tokens.create') }}" class="jf-dash-btn jf-dash-btn--green">
                    <i class="voyager-plus"></i>
                    <span class="jf-pill-label">Add New Token</span>
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid jf-dash-page jf-tokens-page">
        @include('voyager::alerts')

        @php
            $verifiedPct = $stats['total_tokens'] > 0
                ? round(($stats['verified_tokens'] / $stats['total_tokens']) * 100)
                : 0;
            $activePct = $stats['total_tokens'] > 0
                ? round(($stats['active_tokens'] / $stats['total_tokens']) * 100)
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
                                    <i class="voyager-trophy"></i> Platform Token Registry
                                </h3>
                                <p>
                                    {{ number_format($stats['total_tokens']) }} tokens tracked across all networks
                                    · {{ number_format($stats['verified_tokens']) }} verified
                                    · {{ number_format($stats['active_tokens']) }} active
                                </p>
                            </div>
                            <div class="col-md-4 text-right">
                                <div style="margin-top: 10px;">
                                    <span class="jf-hero-panel__label">Combined Market Cap</span><br>
                                    <span class="jf-hero-panel__value">${{ number_format($stats['total_market_cap'], 2) }}</span>
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
                'icon' => 'voyager-trophy',
                'accent' => '#4f8cff',
                'label' => 'Total Tokens',
                'value' => number_format($stats['total_tokens']),
                'footer' => 'All registered tokens',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-check',
                'accent' => '#22c55e',
                'label' => 'Verified',
                'value' => number_format($stats['verified_tokens']),
                'footer' => $verifiedPct . '% of total',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-activity',
                'accent' => '#f59e0b',
                'label' => 'Active',
                'value' => number_format($stats['active_tokens']),
                'footer' => $activePct . '% currently live',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-dollar',
                'accent' => '#7928ca',
                'label' => 'Total Market Cap',
                'value' => '$' . number_format($stats['total_market_cap'], 0),
                'footer' => 'Combined valuation',
            ])
            @include('admin.dashboard.partials.stat-card', [
                'icon' => 'voyager-bar-chart',
                'accent' => '#f472b6',
                'label' => '24h Volume',
                'value' => '$' . number_format($stats['total_volume_24h'], 0),
                'footer' => 'Trading volume today',
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
                        <form method="GET" action="{{ route('voyager.tokens.index') }}" class="jf-tokens-filter">
                            <div class="jf-tokens-filter__field">
                                <input type="text"
                                       name="search"
                                       class="form-control"
                                       placeholder="Search tokens..."
                                       value="{{ request('search') }}">
                            </div>

                            <div class="jf-tokens-filter__field">
                                <select name="status" class="form-control">
                                    <option value="">All Status</option>
                                    <option value="verified" {{ request('status') == 'verified' ? 'selected' : '' }}>Verified</option>
                                    <option value="unverified" {{ request('status') == 'unverified' ? 'selected' : '' }}>Unverified</option>
                                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>

                            <div class="jf-tokens-filter__field">
                                <select name="token_type" class="form-control">
                                    <option value="">All Types</option>
                                    <option value="utility" {{ request('token_type') == 'utility' ? 'selected' : '' }}>Utility Token</option>
                                    <option value="security" {{ request('token_type') == 'security' ? 'selected' : '' }}>Security Token</option>
                                    <option value="governance" {{ request('token_type') == 'governance' ? 'selected' : '' }}>Governance Token</option>
                                    <option value="nft" {{ request('token_type') == 'nft' ? 'selected' : '' }}>NFT Token</option>
                                </select>
                            </div>

                            <div class="jf-tokens-filter__field">
                                <select name="network" class="form-control">
                                    <option value="">All Networks</option>
                                    <option value="ETH" {{ request('network') == 'ETH' ? 'selected' : '' }}>Ethereum (ETH)</option>
                                    <option value="BSC" {{ request('network') == 'BSC' ? 'selected' : '' }}>Binance Smart Chain (BSC)</option>
                                    <option value="MATIC" {{ request('network') == 'MATIC' ? 'selected' : '' }}>Polygon (MATIC)</option>
                                    <option value="ARB" {{ request('network') == 'ARB' ? 'selected' : '' }}>Arbitrum (ARB)</option>
                                </select>
                            </div>

                            <div class="jf-tokens-filter__actions">
                                <button type="submit" class="jf-dash-btn jf-dash-btn--blue">
                                    <i class="voyager-search"></i>
                                    <span class="jf-pill-label">Filter</span>
                                </button>
                                <a href="{{ route('voyager.tokens.index') }}" class="jf-dash-btn jf-dash-btn--purple">
                                    <i class="voyager-refresh"></i>
                                    <span class="jf-pill-label">Clear</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tokens Table -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--tokens-table tokens-table-panel">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-trophy"></i></span>
                            <span>All Tokens</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body tokens-table-panel-body">
                        <div class="table-responsive tokens-table-responsive">
                            <table class="table table-hover jf-tokens-table" id="dataTable">
                                <thead>
                                    <tr>
                                        <th>Token</th>
                                        <th>Price</th>
                                        <th>24h Change</th>
                                        <th>Market Cap</th>
                                        <th>Volume (24h)</th>
                                        <th>Network</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($tokens as $token)
                                        <tr>
                                            <td>
                                                <div class="jf-token-cell">
                                                    @if($token->logo)
                                                        <div class="jf-token-cell__avatar">
                                                            <img src="{{ Storage::url($token->logo) }}" alt="{{ $token->name }}">
                                                        </div>
                                                    @else
                                                        <div class="jf-token-cell__avatar jf-token-cell__avatar--placeholder">
                                                            <i class="voyager-trophy"></i>
                                                        </div>
                                                    @endif
                                                    <div class="jf-token-cell__body">
                                                        <a href="{{ route('voyager.tokens.show', $token->id) }}" class="jf-token-cell__name">{{ $token->name }}</a>
                                                        <div class="jf-token-cell__meta">
                                                            <span>{{ $token->symbol }}</span>
                                                            @if($token->is_verified)
                                                                <i class="voyager-check jf-token-cell__verified" title="Verified"></i>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><strong>${{ number_format($token->current_price, 8) }}</strong></td>
                                            <td>
                                                <span class="jf-token-change {{ $token->price_change_color }}">
                                                    <i class="{{ $token->price_change_icon }}"></i>
                                                    {{ number_format($token->change_24h, 2) }}%
                                                </span>
                                            </td>
                                            <td>${{ number_format((float) $token->market_cap, 2) }}</td>
                                            <td>${{ number_format((float) $token->volume_24h, 2) }}</td>
                                            <td>
                                                <span class="jf-token-badge jf-token-badge--network">{{ $token->display_network_label }}</span>
                                            </td>
                                            <td>
                                                @if($token->is_active)
                                                    <span class="jf-token-badge jf-token-badge--success">Active</span>
                                                @else
                                                    <span class="jf-token-badge jf-token-badge--danger">Inactive</span>
                                                @endif
                                                @if(!$token->transferable)
                                                    <span class="jf-token-badge jf-token-badge--warning">Frozen</span>
                                                @endif
                                            </td>
                                            <td class="jf-tokens-table__actions">
                                                <div class="dropdown jf-token-actions">
                                                    <button type="button" class="jf-dash-btn jf-dash-btn--blue jf-token-actions-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <span class="jf-pill-label">Actions</span>
                                                        <span class="caret"></span>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-right jf-token-dropdown jf-token-dropdown--grid" role="menu">
                                                        <li>
                                                            <a href="{{ route('voyager.tokens.show', $token->id) }}">
                                                                <i class="voyager-eye"></i> View
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('voyager.tokens.edit', $token->id) }}">
                                                                <i class="voyager-edit"></i> Edit
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('voyager.tokens.toggle-verification', $token->id) }}"
                                                               onclick="return confirm('Are you sure?')">
                                                                <i class="voyager-check"></i>
                                                                {{ $token->is_verified ? 'Unverify' : 'Verify' }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('voyager.tokens.toggle-freeze', $token->id) }}"
                                                               onclick="return confirm('Are you sure?')">
                                                                <i class="voyager-lock"></i>
                                                                {{ $token->transferable ? 'Freeze' : 'Unfreeze' }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="{{ route('voyager.tokens.toggle-status', $token->id) }}"
                                                               onclick="return confirm('Are you sure?')">
                                                                <i class="voyager-power"></i>
                                                                {{ $token->is_active ? 'Deactivate' : 'Activate' }}
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <a href="#"
                                                               onclick="showSupplyModal({{ $token->id }}, '{{ $token->name }}')"
                                                               data-toggle="modal"
                                                               data-target="#supplyModal">
                                                                <i class="voyager-dollar"></i> Supply
                                                            </a>
                                                        </li>
                                                        <li class="jf-token-dropdown__danger">
                                                            <a href="#"
                                                               onclick="deleteToken({{ $token->id }})"
                                                               class="text-danger">
                                                                <i class="voyager-trash"></i> Delete
                                                            </a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8">
                                                <div class="jf-dash-card__empty">
                                                    <p>No tokens found.</p>
                                                    <a href="{{ route('voyager.tokens.create') }}" class="jf-dash-card__btn jf-dash-card__btn--blue">
                                                        <i class="voyager-plus"></i> Create First Token
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($tokens->hasPages())
                            <div class="jf-tokens-pagination">
                                {{ $tokens->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Supply Adjustment Modal -->
    <div class="modal fade jf-tokens-modal" id="supplyModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="supplyForm" method="POST">
                    @csrf
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                        <h4 class="modal-title">Adjust Token Supply</h4>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Token</label>
                            <input type="text" id="tokenName" class="form-control" disabled>
                        </div>

                        <div class="form-group">
                            <label>Supply Type</label>
                            <select name="supply_type" class="form-control" required>
                                <option value="total_supply">Total Supply</option>
                                <option value="available_supply">Available Supply</option>
                                <option value="circulating_supply">Circulating Supply</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>New Amount</label>
                            <input type="number"
                                   name="new_amount"
                                   class="form-control"
                                   step="0.01"
                                   min="0"
                                   required>
                        </div>

                        <div class="form-group">
                            <label>Reason for Adjustment</label>
                            <textarea name="reason"
                                      class="form-control"
                                      rows="3"
                                      placeholder="Explain why you're adjusting the supply..."
                                      required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="jf-dash-btn jf-dash-btn--blue">Update Supply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade jf-tokens-modal" id="deleteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                    <h4 class="modal-title text-danger">Delete Token</h4>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this token? This action cannot be undone.</p>
                    <p><strong>Warning:</strong> This will permanently remove all token data.</p>
                </div>
                <div class="modal-footer">
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="jf-dash-btn jf-dash-btn--rose">Delete Token</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
function showSupplyModal(tokenId, tokenName) {
    document.getElementById('tokenName').value = tokenName;
    document.getElementById('supplyForm').action = '{{ route("voyager.tokens.adjust-supply", ":id") }}'.replace(':id', tokenId);
}

function deleteToken(tokenId) {
    document.getElementById('deleteForm').action = '{{ route("voyager.tokens.destroy", ":id") }}'.replace(':id', tokenId);
    $('#deleteModal').modal('show');
}
</script>
@stop
