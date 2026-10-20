@extends('layouts.generic')

@section('content')
@php
    $walletAddress = auth()->user()->wallet_address ?? null;
    $imageUrl = \Illuminate\Support\Facades\Storage::disk($media->imageDisk)->url($media->imagePath);
    $animationUrl = $media->animationPath
        ? \Illuminate\Support\Facades\Storage::disk($media->animationDisk)->url($media->animationPath)
        : null;
@endphp
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-9">

            @include('nft.partials.wallet-connect')

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-magic"></i> Mint {{ $media->isVideo() ? 'video' : 'photo' }} as NFT</h3>
                </div>
                <div class="card-body">
                    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

                    <div class="row">
                        <div class="col-md-5 mb-3 text-center">
                            @if($media->isVideo() && $animationUrl)
                                <video src="{{ $animationUrl }}" poster="{{ $imageUrl }}" controls
                                       class="img-fluid rounded" style="max-height:280px;"></video>
                            @else
                                <img src="{{ $imageUrl }}" alt="" class="img-fluid rounded" style="max-height:280px;">
                            @endif
                        </div>
                        <div class="col-md-7">
                            @unless($walletAddress)
                                <div class="alert alert-warning">
                                    <i class="fas fa-wallet"></i> Connect your wallet above first — an NFT must be owned by a wallet.
                                </div>
                            @else
                            <form action="{{ route('nft.mint-from.store', [$type, $id]) }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label>Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                           value="{{ old('name', $media->name) }}" maxlength="255" required>
                                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" rows="3"
                                              class="form-control @error('description') is-invalid @enderror"
                                              maxlength="2000">{{ old('description', $media->description) }}</textarea>
                                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label>Royalty on resales (%)</label>
                                        <input type="number" step="0.5" min="0" max="50" name="royalty_percent"
                                               class="form-control @error('royalty_percent') is-invalid @enderror"
                                               value="{{ old('royalty_percent', $defaultRoyaltyPercent) }}" required>
                                        <small class="form-text text-muted">You earn this on every resale.</small>
                                        @error('royalty_percent')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Asking price (optional)</label>
                                        <input type="number" step="0.0001" min="0" name="price"
                                               class="form-control" value="{{ old('price') }}">
                                        <small class="form-text text-muted">Hint for listing later.</small>
                                    </div>
                                </div>
                                <div class="form-group form-check">
                                    <input type="checkbox" class="form-check-input @error('confirm_original') is-invalid @enderror"
                                           id="confirm_original" name="confirm_original" value="1" {{ old('confirm_original') ? 'checked' : '' }}>
                                    <label class="form-check-label" for="confirm_original">
                                        This is my original content and I have the right to mint it.
                                    </label>
                                    @error('confirm_original')<div class="invalid-feedback d-block">You must confirm this.</div>@enderror
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg btn-block">
                                    <i class="fas fa-magic"></i> Mint NFT
                                </button>
                            </form>
                            @endunless
                        </div>
                    </div>
                </div>
            </div>
            <a href="{{ route('nft.mintable') }}" class="btn btn-link mt-2">&larr; Back to your content</a>
        </div>
    </div>
</div>
@endsection
