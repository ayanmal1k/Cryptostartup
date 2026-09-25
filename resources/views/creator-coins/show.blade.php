@extends('layouts.generic')

@section('content')
@php $isOwner = (int) auth()->id() === (int) $coin->creator_user_id; @endphp
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">

            @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
            @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
            @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

            <div class="card shadow-sm mb-4">
                <div class="card-body d-flex align-items-center flex-wrap">
                    @if($coin->logo_url)
                        <img src="{{ $coin->logo_url }}" class="rounded-circle mr-3" style="width:72px;height:72px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3" style="width:72px;height:72px;">
                            <span class="font-weight-bold text-primary">{{ strtoupper(substr($coin->symbol,0,3)) }}</span>
                        </div>
                    @endif
                    <div class="flex-grow-1">
                        <h2 class="h4 mb-0">{{ $coin->name }} <small class="text-muted">${{ $coin->symbol }}</small></h2>
                        <div class="text-muted">by {{ optional($coin->creator)->name ?? 'Unknown' }}</div>
                        <div class="small mt-1">
                            <span class="mr-3"><i class="fas fa-coins text-warning"></i>
                                {{ rtrim(rtrim(number_format($coin->price_per_point,4),'0'),'.') }} credits / point</span>
                            <span class="text-muted"><i class="fas fa-users"></i> {{ $coin->holders_count }} holders</span>
                        </div>
                    </div>
                </div>
                @if($coin->description)
                    <div class="card-footer bg-white text-muted small">{{ $coin->description }}</div>
                @endif
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    @if($isOwner)
                        <div class="card h-100 shadow-sm">
                            <div class="card-header">Your coin</div>
                            <div class="card-body">
                                <p class="mb-2"><i class="fas fa-chart-line text-success"></i>
                                    <strong>{{ rtrim(rtrim(number_format($coin->points_issued,4),'0'),'.') }}</strong> points issued</p>
                                <p class="text-muted small mb-0">
                                    You earn {{ 100 - (float)$coin->platform_fee_percentage }}% of each purchase as
                                    withdrawable credits. You can't buy your own coin.
                                </p>
                            </div>
                        </div>
                    @else
                        <div class="card h-100 shadow-sm">
                            <div class="card-header">Buy {{ $coin->symbol }}</div>
                            <div class="card-body">
                                <p class="small text-muted mb-2">
                                    Your balance: <strong>{{ rtrim(rtrim(number_format($myBalance,4),'0'),'.') }} {{ $coin->symbol }}</strong><br>
                                    Your credits: <strong>{{ number_format($myCredits,2) }}</strong>
                                </p>
                                <form action="{{ route('creator-coins.buy', $coin->id) }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label>How many points?</label>
                                        <input type="number" name="points" id="points-input" min="1" step="1" value="{{ old('points', 10) }}"
                                               class="form-control @error('points') is-invalid @enderror"
                                               data-price="{{ (float) $coin->price_per_point }}" required>
                                        @error('points')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <p class="mb-3">Cost: <strong id="cost-display">—</strong> credits</p>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-shopping-cart"></i> Buy with credits
                                    </button>
                                </form>
                                <p class="text-muted small mt-2 mb-0">
                                    Points are non-refundable and can only be spent on this creator's perks.
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header">Your recent activity</div>
                        <ul class="list-group list-group-flush">
                            @forelse($recent as $tx)
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span class="text-capitalize">{{ $tx->type }}</span>
                                    <span class="{{ $tx->type === 'spend' ? 'text-danger' : 'text-success' }}">
                                        {{ $tx->type === 'spend' ? '-' : '+' }}{{ rtrim(rtrim(number_format($tx->points,4),'0'),'.') }}
                                    </span>
                                </li>
                            @empty
                                <li class="list-group-item text-muted">No activity yet.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('points-input');
        var out = document.getElementById('cost-display');
        if (!input || !out) return;
        var price = parseFloat(input.getAttribute('data-price')) || 0;
        function update() {
            var n = parseInt(input.value, 10);
            out.textContent = (n > 0) ? (n * price).toFixed(2) : '—';
        }
        input.addEventListener('input', update);
        update();
    })();
</script>
@endsection
