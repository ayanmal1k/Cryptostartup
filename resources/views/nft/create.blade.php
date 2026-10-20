@extends('layouts.generic')

@section('content')
@php
    $walletAddress = auth()->user()->wallet_address ?? null;
@endphp
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">

            @include('nft.partials.wallet-connect')

            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0"><i class="fas fa-plus-circle"></i> Create NFT</h3>
                </div>
                <div class="card-body">
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    @unless($walletAddress)
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-wallet"></i>
                            Connect your wallet above first — an NFT must be owned by a wallet address.
                        </div>
                    @else
                    <form id="create-nft-form" action="{{ route('nft.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <div class="form-group">
                            <label for="name">NFT Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror"
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror"
                                      id="description" name="description" rows="4">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="image">Image <span class="text-danger">*</span></label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input @error('image') is-invalid @enderror"
                                       id="image" name="image" accept="image/*" required>
                                <label class="custom-file-label" for="image">Choose image...</label>
                            </div>
                            <small class="form-text text-muted">Max size: 10MB. Recommended: 1000x1000px</small>
                            @error('image')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div id="image-preview" class="mt-3" style="display: none;">
                                <img id="preview-img" src="" alt="Preview" class="img-thumbnail" style="max-width: 300px;">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="price">Asking price (optional)</label>
                            <input type="number" step="0.0001" min="0"
                                   class="form-control @error('price') is-invalid @enderror"
                                   id="price" name="price" value="{{ old('price') }}">
                            <small class="form-text text-muted">
                                Saved as a hint for when you list this NFT for sale later. Minting only establishes ownership.
                            </small>
                            @error('price')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            Minting mints the token to your connected wallet
                            (<code>{{ substr($walletAddress, 0, 8) }}…{{ substr($walletAddress, -6) }}</code>)
                            and may take a moment to confirm on-chain. It will then appear under <strong>My NFTs</strong>.
                        </div>

                        <div class="form-group mb-0">
                            <button type="submit" class="btn btn-primary btn-lg btn-block">
                                <i class="fas fa-magic"></i> Mint NFT
                            </button>
                        </div>
                    </form>
                    @endunless
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Image preview
    var imageInput = document.getElementById('image');
    if (imageInput) {
        imageInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function (ev) {
                    document.getElementById('preview-img').src = ev.target.result;
                    document.getElementById('image-preview').style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
            // Bootstrap custom-file label
            var label = e.target.nextElementSibling;
            if (label && file) label.textContent = file.name;
        });
    }
</script>
@endsection
