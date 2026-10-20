@extends('layouts.generic')

@section('content')
@php
    $me = auth()->user();
    $myWallet = $me->wallet_address ?? null;
    $isOwner = $myWallet
        ? strtolower($myWallet) === strtolower((string) $nft->owner_address)
        : ((int) $me->id === (int) $nft->user_id && empty($nft->owner_address));
    $token = strtoupper(config('web3.network'));
    $animationUrl = $nft->metadata['animation_url'] ?? null;
@endphp
<div class="container py-4">
    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

    @include('nft.partials.wallet-connect')

    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card shadow">
                <div class="nft-image-container-large">
                    @if($nft->media_type === 'video' && $animationUrl)
                        <video src="{{ $animationUrl }}" poster="{{ $nft->image_url }}" controls></video>
                    @else
                        <img src="{{ $nft->image_url ?? asset('img/default-nft.png') }}" alt="{{ $nft->name }}">
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-body">
                    <h1 class="h2 mb-3">{{ $nft->name }}</h1>
                    @if($nft->description)
                        <p class="text-muted mb-4">{{ $nft->description }}</p>
                    @endif

                    <div class="mb-4">
                        <h6 class="text-muted">Owner</h6>
                        @if($nft->owner_address)
                            <code>{{ substr($nft->owner_address, 0, 8) }}…{{ substr($nft->owner_address, -6) }}</code>
                            @if($isOwner) <span class="badge badge-info">You</span> @endif
                        @else
                            <span>{{ optional($nft->user)->name ?? 'Unknown' }}</span>
                        @endif
                        <div class="small text-muted mt-1">Created by {{ optional($nft->user)->name ?? 'Unknown' }}</div>
                    </div>

                    @if($nft->status === 'pending_mint')
                        <div class="alert alert-warning"><i class="fas fa-spinner fa-spin"></i> Minting in progress…</div>
                    @elseif($listing && $listing->status === 'active')
                        <div class="border-top border-bottom py-3 mb-3">
                            <span class="text-muted">Price</span>
                            <h3 class="mb-0">{{ rtrim(rtrim(number_format($listing->price, 6), '0'), '.') }} {{ $token }}</h3>
                        </div>
                        @if((int) $listing->seller_id === (int) $me->id)
                            <form action="{{ route('nft.listing.cancel', $listing->id) }}" method="POST">
                                @csrf
                                <button class="btn btn-outline-danger btn-block"><i class="fas fa-times"></i> Cancel listing</button>
                            </form>
                        @elseif($myWallet)
                            <form action="{{ route('nft.buy', $listing->id) }}" method="POST"
                                  onsubmit="return confirm('Buy this NFT for {{ $listing->price }} {{ $token }}?');">
                                @csrf
                                <button class="btn btn-primary btn-lg btn-block"><i class="fas fa-shopping-cart"></i> Buy now</button>
                            </form>
                        @else
                            <div class="alert alert-warning mb-0">Connect your wallet above to buy.</div>
                        @endif
                    @elseif($isOwner)
                        <form action="{{ route('nft.list', $nft->id) }}" method="POST" class="border-top pt-3">
                            @csrf
                            <label class="small">List this NFT for sale</label>
                            <div class="input-group">
                                <input type="number" step="0.000001" min="0.000001" name="price" class="form-control"
                                       placeholder="Price in {{ $token }}" required>
                                <div class="input-group-append">
                                    <button class="btn btn-primary"><i class="fas fa-tag"></i> List</button>
                                </div>
                            </div>
                            @error('price')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </form>
                    @else
                        <div class="alert alert-secondary mb-0"><i class="fas fa-info-circle"></i> Not currently listed for sale.</div>
                    @endif

                    <div class="mt-4 pt-3 border-top">
                        <table class="table table-sm mb-0">
                            <tr><td class="text-muted">Token ID</td><td class="text-right"><code>{{ $nft->token_id ?? '—' }}</code></td></tr>
                            <tr><td class="text-muted">Type</td><td class="text-right">{{ ucfirst($nft->media_type ?? 'image') }}</td></tr>
                            <tr><td class="text-muted">Creator royalty</td><td class="text-right">{{ ($nft->royalty_bps ?? 0) / 100 }}%</td></tr>
                            <tr><td class="text-muted">Network</td><td class="text-right">{{ $token }}</td></tr>
                            <tr><td class="text-muted">Created</td><td class="text-right">{{ optional($nft->created_at)->format('M d, Y') }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .nft-image-container-large { width:100%; padding-top:100%; position:relative; overflow:hidden; background:#f8f9fa; }
    .nft-image-container-large img, .nft-image-container-large video {
        position:absolute; top:0; left:0; width:100%; height:100%; object-fit:cover;
    }
</style>
@endsection
