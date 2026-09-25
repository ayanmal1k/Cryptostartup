@extends('layouts.generic')

@section('page_title', __('My listings'))

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
@endsection

@section('content')
<div class="dx nx">

    @include('elements.discover.subnav', [
        'tabs' => [
            ['label' => __('Explore'), 'href' => route('nft.marketplace'), 'active' => false],
            ['label' => __('For sale'), 'href' => route('nft.marketplace', ['view' => 'sale']), 'active' => false],
            ['label' => __('My NFTs'), 'href' => route('nft.my-nfts'), 'active' => true],
        ],
        'search' => '',
        'searchAction' => route('nft.marketplace'),
        'searchLabel' => __('Search NFTs'),
        'cta' => ['label' => __('Create'), 'href' => route('nft.mintable')],
    ])

    <main class="dx-main">
        @foreach(['success', 'error'] as $flash)
            @if(session($flash))
                <div class="dx-alert dx-alert--{{ $flash }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        <section>
            <div class="dx-section__head">
                <div>
                    <h2 class="dx-section__title">{{ __('My listings') }}</h2>
                    <p class="dx-section__sub">{{ __('What you have put up for sale') }}</p>
                </div>
                <a href="{{ route('nft.my-nfts') }}" class="dx-section__link">{{ __('My NFTs') }}</a>
            </div>

            @php $shown = $listings->getCollection()->filter(fn ($l) => $l->nft); @endphp
            @if($shown->count())
                <div class="nx-grid">
                    @foreach($shown as $listing)
                        @php
                            $nft = $listing->nft;
                            $nft->setRelation('activeListing', $listing->status === 'active' ? $listing : null);
                            $note = [
                                'sold' => __('Sold · $:price', ['price' => number_format((float) $listing->price, 2)]),
                                'cancelled' => __('Stopped'),
                            ][$listing->status] ?? null;
                        @endphp
                        <div class="nx-tile-wrap">
                            @include('nft.partials.card', ['nft' => $nft, 'note' => $note])
                            @if($listing->status === 'active')
                                <form action="{{ route('nft.listing.cancel', $listing->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" class="dx-btn dx-btn--ghost dx-btn--sm dx-btn--block">{{ __('Stop selling') }}</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
                @if($listings->hasPages())
                    <div class="dx-pagination nx-pagination">{{ $listings->links() }}</div>
                @endif
            @else
                <div class="dx-empty">
                    <span class="dx-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>
                    </span>
                    <h3>{{ __('Nothing for sale yet') }}</h3>
                    <p>{{ __('Open one of your NFTs and put it up for sale.') }}</p>
                    <a href="{{ route('nft.my-nfts') }}" class="dx-btn dx-btn--primary">{{ __('My NFTs') }}</a>
                </div>
            @endif
        </section>
    </main>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
@endsection
