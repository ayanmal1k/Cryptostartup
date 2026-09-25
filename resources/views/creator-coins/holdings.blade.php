@extends('layouts.generic')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h1 class="h3 mb-0">My Creator Coins</h1>
        <a href="{{ route('creator-coins.index') }}" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-store"></i> Browse coins
        </a>
    </div>

    @if($myCoin)
        <div class="card shadow-sm mb-4 border-primary">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <span class="badge badge-primary mb-1">Your coin</span>
                    <h5 class="mb-0">{{ $myCoin->name }} <small class="text-muted">${{ $myCoin->symbol }}</small></h5>
                    <div class="small text-muted">
                        {{ rtrim(rtrim(number_format($myCoin->points_issued,4),'0'),'.') }} points issued ·
                        {{ $myCoin->holders_count }} holders
                    </div>
                </div>
                <a href="{{ route('creator-coins.show', $myCoin->id) }}" class="btn btn-sm btn-primary mt-2 mt-md-0">Manage</a>
            </div>
        </div>
    @else
        <div class="alert alert-light border d-flex justify-content-between align-items-center flex-wrap">
            <span>You haven't launched a coin yet.</span>
            <a href="{{ route('creator-coins.create') }}" class="btn btn-sm btn-primary">Launch your coin</a>
        </div>
    @endif

    <h5 class="mb-3">Coins you hold</h5>
    <div class="row">
        @forelse($balances as $bal)
            @php $c = $bal->coin; @endphp
            @if($c)
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card shadow-sm">
                    <div class="card-body d-flex align-items-center">
                        @if($c->logo_url)
                            <img src="{{ $c->logo_url }}" class="rounded-circle mr-3" style="width:48px;height:48px;object-fit:cover;">
                        @else
                            <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mr-3" style="width:48px;height:48px;">
                                <span class="font-weight-bold text-primary small">{{ strtoupper(substr($c->symbol,0,3)) }}</span>
                            </div>
                        @endif
                        <div class="flex-grow-1">
                            <a href="{{ route('creator-coins.show', $c->id) }}" class="font-weight-bold text-dark">{{ $c->name }}</a>
                            <div class="small text-muted">by {{ optional($c->creator)->name }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-weight-bold">{{ rtrim(rtrim(number_format($bal->balance,4),'0'),'.') }}</div>
                            <div class="small text-muted">{{ $c->symbol }}</div>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        @empty
            <div class="col-12">
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-coins fa-3x mb-3"></i>
                    <p>You don't hold any creator coins yet.</p>
                    <a href="{{ route('creator-coins.index') }}" class="btn btn-primary">Browse coins</a>
                </div>
            </div>
        @endforelse
    </div>
</div>
@endsection
