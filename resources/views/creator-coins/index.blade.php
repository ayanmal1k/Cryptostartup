@extends('layouts.generic')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Creator Coins</h1>
            <p class="text-muted mb-0">Support your favourite creators — buy their coins and unlock perks.</p>
        </div>
        <div class="mt-2 mt-md-0">
            <a href="{{ route('creator-coins.holdings') }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-wallet"></i> My Coins
            </a>
            <a href="{{ route('creator-coins.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Launch your coin
            </a>
        </div>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

    <div class="row">
        @forelse($coins as $coin)
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body text-center d-flex flex-column">
                        @if($coin->logo_url)
                            <img src="{{ $coin->logo_url }}" alt="{{ $coin->symbol }}" class="rounded-circle mx-auto mb-2" style="width:64px;height:64px;object-fit:cover;">
                        @else
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mx-auto mb-2" style="width:64px;height:64px;">
                                <span class="font-weight-bold text-primary">{{ strtoupper(substr($coin->symbol,0,3)) }}</span>
                            </div>
                        @endif
                        <h5 class="card-title mb-0">{{ $coin->name }}</h5>
                        <div class="text-muted small mb-2">${{ $coin->symbol }}</div>
                        <div class="small text-muted mb-2">by {{ optional($coin->creator)->name ?? 'Unknown' }}</div>
                        <div class="small flex-grow-1">
                            <div><i class="fas fa-coins text-warning"></i> {{ rtrim(rtrim(number_format($coin->price_per_point,4),'0'),'.') }} credits / point</div>
                            <div class="text-muted"><i class="fas fa-users"></i> {{ $coin->holders_count }} holders</div>
                        </div>
                        <a href="{{ route('creator-coins.show', $coin->id) }}" class="btn btn-sm btn-primary mt-3">View &amp; Buy</a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <i class="fas fa-coins fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No creator coins yet</h4>
                <a href="{{ route('creator-coins.create') }}" class="btn btn-primary mt-2">Be the first to launch one</a>
            </div>
        @endforelse
    </div>

    @if($coins->hasPages())
        <div class="d-flex justify-content-center mt-3">{{ $coins->links() }}</div>
    @endif
</div>
@endsection
