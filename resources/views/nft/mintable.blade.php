@extends('layouts.generic')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
        <div>
            <h1 class="h3 mb-1">Turn your content into NFTs</h1>
            <p class="text-muted mb-0">Mint your own videos and photos as collectible NFTs you own on-chain.</p>
        </div>
        <a href="{{ route('nft.my-nfts') }}" class="btn btn-outline-secondary btn-sm mt-2 mt-md-0">
            <i class="fas fa-images"></i> My NFTs
        </a>
    </div>

    @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
    @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif

    @include('nft.partials.wallet-connect')

    <h5 class="mt-4 mb-3"><i class="fas fa-video text-primary"></i> Your videos</h5>
    <div class="row">
        @forelse($videos as $video)
            @php $nft = $mintedMap->get('video:' . $video->id); @endphp
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm">
                    <div style="position:relative;padding-top:100%;background:#f1f1f1;overflow:hidden;">
                        <img src="{{ $video->thumbnail_url ?? $video->video_url }}" alt=""
                             style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;"
                             onerror="this.style.display='none'">
                        <span class="badge badge-dark" style="position:absolute;top:8px;left:8px;"><i class="fas fa-play"></i> Video</span>
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-truncate">{{ $video->title ?: 'Video #'.$video->id }}</h6>
                        @if($nft)
                            <a href="{{ route('nft.show', $nft->id) }}" class="btn btn-sm btn-success mt-auto">
                                <i class="fas fa-check"></i> Minted — view NFT
                            </a>
                        @else
                            <a href="{{ route('nft.mint-from', ['video', $video->id]) }}" class="btn btn-sm btn-primary mt-auto">
                                <i class="fas fa-magic"></i> Mint as NFT
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-muted mb-3">You don't have any videos yet.</div>
        @endforelse
    </div>

    <h5 class="mt-4 mb-3"><i class="fas fa-image text-primary"></i> Your photos</h5>
    <div class="row">
        @forelse($images as $img)
            @php $nft = $mintedMap->get('attachment:' . $img->id); @endphp
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="card h-100 shadow-sm">
                    <div style="position:relative;padding-top:100%;background:#f1f1f1;overflow:hidden;">
                        <img src="{{ $img->thumbnail ?? $img->path }}" alt=""
                             style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover;"
                             onerror="this.style.display='none'">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <h6 class="card-title text-truncate">Photo</h6>
                        @if($nft)
                            <a href="{{ route('nft.show', $nft->id) }}" class="btn btn-sm btn-success mt-auto">
                                <i class="fas fa-check"></i> Minted — view NFT
                            </a>
                        @else
                            <a href="{{ route('nft.mint-from', ['attachment', $img->id]) }}" class="btn btn-sm btn-primary mt-auto">
                                <i class="fas fa-magic"></i> Mint as NFT
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-muted">You don't have any photos yet.</div>
        @endforelse
    </div>
</div>
@endsection
