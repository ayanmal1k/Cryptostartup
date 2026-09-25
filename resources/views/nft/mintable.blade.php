@extends('layouts.generic')

@section('page_title', __('Create an NFT'))

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
@endsection

@php
    /** Picks one of the user's own uploads to turn into an NFT. Every upload is a tile. */
    $videoThumb = fn ($v) => $v->thumbnail_path && is_file(public_path('storage/' . $v->thumbnail_path))
        ? asset('storage/' . $v->thumbnail_path) : null;
@endphp

@section('content')
<div class="dx nx">

    @include('elements.discover.subnav', [
        'tabs' => [
            ['label' => __('Explore'), 'href' => route('nft.marketplace'), 'active' => false],
            ['label' => __('For sale'), 'href' => route('nft.marketplace', ['view' => 'sale']), 'active' => false],
            ['label' => __('My NFTs'), 'href' => route('nft.my-nfts'), 'active' => false],
        ],
        'search' => '',
        'searchAction' => route('nft.marketplace'),
        'searchLabel' => __('Search NFTs'),
    ])

    <main class="dx-main">
        @foreach(['success', 'error', 'info'] as $flash)
            @if(session($flash))
                <div class="dx-alert dx-alert--{{ $flash }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        <header>
            <h1 class="dx-section__title nx-page-title">{{ __('Turn your uploads into NFTs') }}</h1>
            <p class="dx-section__sub">{{ __('Pick a video or photo you posted. It becomes a one-of-a-kind piece fans can buy — and you earn again every time it resells.') }}</p>
        </header>

        <section>
            <div class="dx-section__head">
                <h2 class="dx-section__title">{{ __('Your videos') }}</h2>
            </div>
            @if($videos->count())
                <div class="nx-grid">
                    @foreach($videos as $video)
                        @php $minted = $mintedMap->get('video:' . $video->id); $thumb = $videoThumb($video); @endphp
                        <a href="{{ $minted ? route('nft.show', $minted->id) : route('nft.mint-from', ['video', $video->id]) }}"
                           class="nx-tile {{ $thumb ? '' : 'nx-tile--blank' }}">
                            <div class="nx-tile__media">
                                @if($thumb)
                                    <img src="{{ $thumb }}" alt="" loading="lazy">
                                @else
                                    <span class="nx-tile__blank" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" fill="currentColor"><path d="M7 4v16l13-8z"/></svg>
                                    </span>
                                @endif
                                <span class="nx-tile__scrim"></span>
                            </div>
                            <div class="nx-tile__top">
                                <span class="nx-tile__chip {{ $minted ? '' : 'nx-tile__chip--money' }}">{{ $minted ? __('Already an NFT') : __('+ Sell it') }}</span>
                            </div>
                            <div class="nx-tile__info">
                                <span class="nx-tile__title">{{ $video->title ?: __('Untitled video') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="dx-section__sub">{{ __('You haven\'t posted any videos yet.') }}</p>
            @endif
        </section>

        <section>
            <div class="dx-section__head">
                <h2 class="dx-section__title">{{ __('Your photos') }}</h2>
            </div>
            @if($images->count())
                <div class="nx-grid">
                    @foreach($images as $img)
                        @php $minted = $mintedMap->get('attachment:' . $img->id); @endphp
                        <a href="{{ $minted ? route('nft.show', $minted->id) : route('nft.mint-from', ['attachment', $img->id]) }}" class="nx-tile">
                            <div class="nx-tile__media">
                                <img src="{{ $img->thumbnail ?? $img->path }}" alt="" loading="lazy" onerror="this.remove()">
                                <span class="nx-tile__scrim"></span>
                            </div>
                            <div class="nx-tile__top">
                                <span class="nx-tile__chip {{ $minted ? '' : 'nx-tile__chip--money' }}">{{ $minted ? __('Already an NFT') : __('+ Sell it') }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <p class="dx-section__sub">{{ __('You haven\'t posted any photos yet.') }}</p>
            @endif
        </section>
    </main>
</div>
@endsection
