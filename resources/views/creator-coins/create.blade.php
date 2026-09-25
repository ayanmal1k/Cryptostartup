@extends('layouts.generic')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-coins"></i> Launch your Creator Coin</h3>
                </div>
                <div class="card-body">
                    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        Your coin is a <strong>loyalty currency</strong>: fans buy points with platform credits and spend
                        them on your perks. You earn withdrawable credits on every purchase
                        (platform fee: {{ $cfg['platform_fee_percentage'] }}%). Points are non-refundable and can't be cashed
                        out by fans.
                    </div>

                    <form action="{{ route('creator-coins.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label>Coin Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name') }}" maxlength="100" required>
                            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Symbol <span class="text-danger">*</span></label>
                            <input type="text" name="symbol" class="form-control text-uppercase @error('symbol') is-invalid @enderror"
                                   value="{{ old('symbol') }}" maxlength="16" placeholder="e.g. STAR" required>
                            <small class="form-text text-muted">Letters and numbers only.</small>
                            @error('symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" rows="3" class="form-control @error('description') is-invalid @enderror"
                                      maxlength="1000">{{ old('description') }}</textarea>
                            @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Price per point (in platform credits) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="price_per_point"
                                   min="{{ $cfg['min_price_per_point'] }}" max="{{ $cfg['max_price_per_point'] }}"
                                   class="form-control @error('price_per_point') is-invalid @enderror"
                                   value="{{ old('price_per_point', 1) }}" required>
                            <small class="form-text text-muted">
                                Between {{ $cfg['min_price_per_point'] }} and {{ $cfg['max_price_per_point'] }} credits.
                            </small>
                            @error('price_per_point')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="form-group">
                            <label>Logo</label>
                            <input type="file" name="logo" class="form-control-file @error('logo') is-invalid @enderror" accept="image/*">
                            @error('logo')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block">
                            <i class="fas fa-rocket"></i> Launch Coin
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
