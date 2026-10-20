@extends('voyager::master')

@section('page_title', 'Dashboard Top Performers')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-trophy"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Dashboard Top Performers</h1>
                    <p class="jf-dash-page-header__desc">Highest-value tokens, wallet holders, distributions, and recent large payouts.</p>
                </div>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content browse container-fluid jf-dash-page jf-top-performers-page">
        @include('voyager::alerts')
        @include('admin.dashboard.partials.section-nav')

        @if($error)
            <div class="jf-top-performers-page__alert alert alert-danger">{{ $error }}</div>
        @endif

        <div class="row jf-dash-cards-row">
            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--top-performers">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--purple"><i class="voyager-trophy"></i></span>
                            <span>Top Tokens By Market Cap</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body table-responsive jf-top-performers-page__table-wrap">
                        <table class="table table-hover jf-tokens-table jf-top-performers-table">
                            <thead><tr><th>Token</th><th>Symbol</th><th>Market Cap</th></tr></thead>
                            <tbody>
                                @forelse(($data['top_tokens_by_market_cap'] ?? []) as $token)
                                    <tr>
                                        <td>{{ $token->name }}</td>
                                        <td>{{ $token->symbol }}</td>
                                        <td><span class="jf-top-performers-table__money">${{ number_format($token->market_cap ?? 0, 2) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">No tokens found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--top-performers">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-people"></i></span>
                            <span>Top Users By Wallet Value</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body table-responsive jf-top-performers-page__table-wrap">
                        <table class="table table-hover jf-tokens-table jf-top-performers-table">
                            <thead><tr><th>User</th><th>Wallets</th><th>Total Value</th></tr></thead>
                            <tbody>
                                @forelse(($data['top_users_by_wallet_value'] ?? []) as $row)
                                    <tr>
                                        <td>{{ optional($row['user'])->name ?? optional($row['user'])->email ?? 'User #' . $row['user_id'] }}</td>
                                        <td>{{ number_format($row['wallet_count'] ?? 0) }}</td>
                                        <td><span class="jf-top-performers-table__money">${{ number_format($row['total_value_usd'] ?? 0, 2) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-muted">No wallet values found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="row jf-dash-cards-row">
            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--top-performers">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--green"><i class="voyager-dollar"></i></span>
                            <span>Most Distributed Tokens</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body table-responsive jf-top-performers-page__table-wrap">
                        <table class="table table-hover jf-tokens-table jf-top-performers-table">
                            <thead><tr><th>Token</th><th>Total Distributed</th></tr></thead>
                            <tbody>
                                @forelse(($data['most_distributed_tokens'] ?? []) as $distribution)
                                    <tr>
                                        <td>{{ optional($distribution->cryptocurrency)->name ?? 'Unknown token' }}</td>
                                        <td><span class="jf-top-performers-table__money">${{ number_format($distribution->total_distributed ?? 0, 2) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="2" class="text-muted">No distributed tokens found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--top-performers">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--amber"><i class="voyager-receipt"></i></span>
                            <span>Recent High-Value Distributions</span>
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body table-responsive jf-top-performers-page__table-wrap">
                        <table class="table table-hover jf-tokens-table jf-top-performers-table">
                            <thead><tr><th>User</th><th>Token</th><th>Amount</th><th>Distributed</th></tr></thead>
                            <tbody>
                                @forelse(($data['recent_high_value_distributions'] ?? []) as $distribution)
                                    <tr>
                                        <td>{{ optional($distribution->user)->name ?? optional($distribution->user)->email ?? 'Unknown user' }}</td>
                                        <td>{{ optional($distribution->cryptocurrency)->symbol ?? 'Unknown' }}</td>
                                        <td><span class="jf-top-performers-table__money">${{ number_format($distribution->distribution_amount ?? 0, 2) }}</span></td>
                                        <td>{{ $distribution->distributed_at ? $distribution->distributed_at->diffForHumans() : 'Unknown' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-muted">No high-value distributions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
