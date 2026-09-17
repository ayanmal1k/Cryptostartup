@extends('layouts.generic')

@section('page_title', __('Sell your upload'))

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/challenge-detail.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
@endsection

@php
    $credits = \App\Models\NFT::settlesInCredits();
    $needsWallet = !$credits && empty(auth()->user()->wallet_address);
    $imageUrl = \Illuminate\Support\Facades\Storage::disk($media->imageDisk)->url($media->imagePath);
    $animationUrl = $media->animationPath
        ? \Illuminate\Support\Facades\Storage::disk($media->animationDisk)->url($media->animationPath)
        : null;
    $feePct = (int) config('web3.marketplace_fee_bps', 250) / 100;
@endphp

@section('content')
<div class="dx cd cd--form">
<div class="cd-wrap">

    @if(session('error'))
        <div class="dx-alert dx-alert--error nx-flash" role="status">{{ session('error') }}</div>
    @endif

    <section class="cd-stage has-video nx-stage">
        @if($media->isVideo() && $animationUrl)
            <video src="{{ $animationUrl }}" poster="{{ $imageUrl }}" controls playsinline preload="metadata"></video>
        @else
            <img src="{{ $imageUrl }}" alt="">
        @endif
    </section>

    <div class="cd-grid">
    <div class="cd-col">
        <header class="cd-head">
            <div class="cd-chips">
                <span class="cd-type">{{ $media->isVideo() ? __('Video') : __('Photo') }} · {{ __('One of a kind') }}</span>
            </div>
            <h1 class="cd-title">{{ __('Sell this as an NFT') }}</h1>
            <p class="cd-meta">{{ __('Fans buy the original. You earn on the first sale and again whenever it resells.') }}</p>
        </header>

        @if($needsWallet)
            @include('nft.partials.wallet-connect')
        @else
        <form action="{{ route('nft.mint-from.store', [$type, $id]) }}" method="POST" class="cd-card nx-form" id="nxMintForm">
            @csrf

            <label class="nx-form__label" for="nx-name">{{ __('Title') }}</label>
            <input type="text" id="nx-name" name="name" class="nx-form__input" maxlength="255" required
                   value="{{ old('name', $media->name) }}">
            @error('name')<p class="cd-note nx-error">{{ $message }}</p>@enderror

            <label class="nx-form__label" for="nx-desc">{{ __('Description') }} <span>{{ __('optional') }}</span></label>
            <textarea id="nx-desc" name="description" class="nx-form__input" rows="3" maxlength="2000"
                      placeholder="{{ __('What makes this one special?') }}">{{ old('description', $media->description) }}</textarea>
            @error('description')<p class="cd-note nx-error">{{ $message }}</p>@enderror

            <label class="nx-form__label" for="nx-price">{{ __('Price') }} <span>{{ __('leave empty to keep it for now') }}</span></label>
            <div class="nx-sell__amount">
                <span aria-hidden="true">{{ $credits ? '$' : '' }}</span>
                <input type="number" id="nx-price" name="price" step="0.01" min="{{ $credits ? '1' : '0' }}"
                       inputmode="decimal" placeholder="0.00" value="{{ old('price') }}">
            </div>
            @error('price')<p class="cd-note nx-error">{{ $message }}</p>@enderror

            <label class="nx-form__label" for="nx-royalty">{{ __('Your cut when fans resell it') }}</label>
            <div class="nx-form__royalty">
                @foreach([5, 10, 15, 20] as $r)
                    <button type="button" class="nx-form__chip {{ (float) old('royalty_percent', $defaultRoyaltyPercent) == $r ? 'is-active' : '' }}" data-royalty="{{ $r }}">{{ $r }}%</button>
                @endforeach
                <input type="number" id="nx-royalty" name="royalty_percent" class="nx-form__input nx-form__input--small"
                       step="0.5" min="0" max="50" required value="{{ old('royalty_percent', $defaultRoyaltyPercent) }}"
                       aria-label="{{ __('Resale cut in percent') }}">
            </div>
            @error('royalty_percent')<p class="cd-note nx-error">{{ $message }}</p>@enderror

            <label class="nx-form__check">
                <input type="checkbox" name="confirm_original" value="1" {{ old('confirm_original') ? 'checked' : '' }}>
                <span>{{ __('This is my own content and I have the right to sell it.') }}</span>
            </label>
            @error('confirm_original')<p class="cd-note nx-error">{{ __('Please confirm this is your own content.') }}</p>@enderror
        </form>
        @endif
    </div>

    <aside class="cd-side">
        <section class="cd-card cd-money">
            <div class="cd-money__amounts">
                <span class="cd-money__raised" data-mint-net>{{ $credits ? '$0.00' : '—' }}</span>
                <span class="cd-money__goal">{{ __('you get from the first sale') }}</span>
            </div>
            <p class="cd-note nx-owner-note">
                {{ __('After the :fee% marketplace fee. Plus your cut of every resale.', ['fee' => rtrim(rtrim(number_format($feePct, 2), '0'), '.')]) }}
            </p>
            @unless($needsWallet)
                <div class="cd-actions">
                    <button type="submit" form="nxMintForm" class="dx-btn dx-btn--primary dx-btn--block" data-mint-submit>{{ __('Create NFT') }}</button>
                </div>
            @endunless
        </section>
        <nav class="cd-card cd-more" aria-label="{{ __('More') }}">
            <a href="{{ route('nft.mintable') }}" class="cd-more__link">
                <span>{{ __('Pick something else') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
        </nav>
    </aside>
    </div>

</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('nxMintForm');
    if (!form) return;
    var price = document.getElementById('nx-price');
    var royalty = document.getElementById('nx-royalty');
    var net = document.querySelector('[data-mint-net]');
    var submit = document.querySelector('[data-mint-submit]');
    var fee = {{ $feePct }} / 100;
    var prefix = @json($credits ? '$' : '');

    var update = function () {
        var p = parseFloat(price.value);
        if (net) net.textContent = p > 0 ? prefix + (p * (1 - fee)).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : prefix + '0.00';
        if (submit) submit.textContent = p > 0 ? @json(__('Create and put up for sale')) : @json(__('Create NFT'));
        document.querySelectorAll('.nx-form__chip').forEach(function (c) {
            c.classList.toggle('is-active', parseFloat(c.dataset.royalty) === parseFloat(royalty.value));
        });
    };

    document.querySelectorAll('.nx-form__chip').forEach(function (c) {
        c.addEventListener('click', function () { royalty.value = c.dataset.royalty; update(); });
    });
    price.addEventListener('input', update);
    royalty.addEventListener('input', update);
    update();
});
</script>
@endsection
