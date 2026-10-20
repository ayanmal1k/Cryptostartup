@extends('layouts.generic')

@php
    $isDarkTheme = Cookie::get('app_theme') == null
        ? getSetting('site.default_user_theme') == 'dark'
        : Cookie::get('app_theme') == 'dark';

    $network = strtoupper(config('web3.network'));
@endphp

@section('styles')
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260804a">
@endsection

@section('content')
<div class="nftm-page {{ $isDarkTheme ? 'nftm-page--dark' : 'nftm-page--light' }}">
<div class="nftm-container">

    <header class="nftm-header">
        <div>
            <h1 class="nftm-header__title">{{ __('NFT Marketplace') }}</h1>
            <p class="nftm-header__sub">{{ __('Own the moments your creators make') }}</p>
        </div>
        {{-- Platform rule: an NFT can only come from media already uploaded to the site, so the
             entry point is the user's own uploads — never a blank "create" form. --}}
        <a href="{{ route('nft.mintable') }}" class="nftm-btn nftm-btn--primary">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            {{ __('Mint from my uploads') }}
        </a>
    </header>

    @if(session('success'))
        <div class="nftm-alert nftm-alert--success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="nftm-alert nftm-alert--error">{{ session('error') }}</div>
    @endif

    {{-- Real figures, not decoration: all four are computed from listings/mints. --}}
    <section class="nftm-stats">
        <div class="nftm-stat">
            <span class="nftm-stat__value">{{ number_format($stats['listed']) }}</span>
            <span class="nftm-stat__label">{{ __('For sale') }}</span>
        </div>
        <div class="nftm-stat">
            <span class="nftm-stat__value">
                {{ $stats['floor'] > 0 ? rtrim(rtrim(number_format($stats['floor'], 4), '0'), '.') : '—' }}
            </span>
            <span class="nftm-stat__label">{{ __('Floor') }} {{ $network }}</span>
        </div>
        <div class="nftm-stat">
            <span class="nftm-stat__value">{{ number_format($stats['minted']) }}</span>
            <span class="nftm-stat__label">{{ __('Minted') }}</span>
        </div>
        <div class="nftm-stat">
            <span class="nftm-stat__value">{{ number_format($stats['owners']) }}</span>
            <span class="nftm-stat__label">{{ __('Owners') }}</span>
        </div>
    </section>

    <section class="nftm-toolbar">
        <form action="{{ route('nft.marketplace') }}" method="GET" class="nftm-search" role="search">
            <input type="hidden" name="sort" value="{{ $sort }}">
            <span class="nftm-search__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.75"/>
                    <path d="m20 20-3.5-3.5" stroke="currentColor" stroke-width="1.75" stroke-linecap="round"/>
                </svg>
            </span>
            <input type="text" name="search" class="nftm-search__input"
                   value="{{ $search }}" autocomplete="off"
                   placeholder="{{ __('Search NFTs by name...') }}"
                   aria-label="{{ __('Search NFTs') }}">
            <button type="submit" class="nftm-search__btn">{{ __('Search') }}</button>
        </form>

        <div class="nftm-filters">
            @foreach([
                'newest' => __('Newest'),
                'price_low' => __('Price: Low'),
                'price_high' => __('Price: High'),
            ] as $key => $label)
                <a href="{{ route('nft.marketplace', array_filter(['search' => $search, 'sort' => $key])) }}"
                   class="nftm-filter {{ $sort === $key ? 'is-active' : '' }}">{{ $label }}</a>
            @endforeach
        </div>
    </section>

    @if($listings->count())
        <section class="nftm-grid">
            @foreach($listings as $listing)
                @php $nft = $listing->nft; @endphp
                @continue(!$nft)
                <article class="nftm-item">
                    <a href="{{ route('nft.show', $listing->nft_id) }}" class="nftm-item__media">
                        @if($nft->image_url)
                            <img src="{{ $nft->image_url }}" alt="{{ $nft->name }}" loading="lazy">
                        @else
                            <span class="nftm-item__placeholder" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/>
                                    <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                                    <path d="m21 15-5-5L5 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                        @endif
                        @if($nft->media_type)
                            <span class="nftm-item__type">{{ $nft->media_type }}</span>
                        @endif
                        <span class="nftm-item__price">
                            {{ rtrim(rtrim(number_format($listing->price, 6), '0'), '.') }} {{ $network }}
                        </span>
                    </a>
                    <div class="nftm-item__body">
                        <a href="{{ route('nft.show', $listing->nft_id) }}" class="nftm-item__name">{{ $nft->name }}</a>
                        <span class="nftm-item__seller">{{ __('by') }} {{ $listing->seller->name ?? __('Unknown') }}</span>
                        <div class="nftm-item__actions">
                            @if((int) $listing->seller_id !== (int) auth()->id() && auth()->user() && auth()->user()->wallet_address)
                                <form action="{{ route('nft.buy', $listing->id) }}" method="POST"
                                      onsubmit="return confirm('{{ __('Buy this NFT for') }} {{ rtrim(rtrim(number_format($listing->price, 6), '0'), '.') }} {{ $network }}?');">
                                    @csrf
                                    <button type="submit" class="nftm-btn nftm-btn--primary nftm-btn--sm nftm-btn--block">{{ __('Buy now') }}</button>
                                </form>
                            @else
                                <a href="{{ route('nft.show', $listing->nft_id) }}" class="nftm-btn nftm-btn--ghost nftm-btn--sm nftm-btn--block">{{ __('View') }}</a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </section>

        @if($listings->hasPages())
            <div class="nftm-pagination">{{ $listings->links() }}</div>
        @endif
    @else
        <div class="nftm-empty">
            <span class="nftm-empty__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none">
                    <rect x="3" y="3" width="18" height="18" rx="2" stroke="currentColor" stroke-width="1.75"/>
                    <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                    <path d="m21 15-5-5L5 21" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </span>
            <h3>{{ $search !== '' ? __('No NFTs match that search') : __('Nothing listed yet') }}</h3>
            <p>
                {{ $search !== ''
                    ? __('Try a different name.')
                    : __('Turn a video or photo you have already uploaded into an NFT.') }}
            </p>
            @if($search !== '')
                <a href="{{ route('nft.marketplace') }}" class="nftm-btn nftm-btn--ghost">{{ __('Clear search') }}</a>
            @else
                <a href="{{ route('nft.mintable') }}" class="nftm-btn nftm-btn--primary">{{ __('Mint from my uploads') }}</a>
            @endif
        </div>
    @endif

</div>
</div>
@endsection
