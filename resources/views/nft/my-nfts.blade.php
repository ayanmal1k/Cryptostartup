@extends('layouts.generic')

@section('page_title', __('My NFTs'))

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
        @foreach(['success', 'error', 'info'] as $flash)
            @if(session($flash))
                <div class="dx-alert dx-alert--{{ $flash }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        <section>
            <div class="dx-section__head">
                <div>
                    <h2 class="dx-section__title">{{ __('My NFTs') }}</h2>
                    <p class="dx-section__sub">{{ trans_choice('{0} Nothing here yet|{1} :count piece you own|[2,*] :count pieces you own', $nfts->total(), ['count' => $nfts->total()]) }}</p>
                </div>
                <a href="{{ route('nft.my-listings') }}" class="dx-section__link">{{ __('My listings') }}</a>
            </div>

            @if($nfts->count())
                <div class="nx-grid">
                    @foreach($nfts as $nft)
                        @include('nft.partials.card', [
                            'nft' => $nft,
                            'note' => $nft->isPendingMint() ? __('Being created') : null,
                        ])
                    @endforeach
                </div>
                @if($nfts->hasPages())
                    <div class="dx-pagination nx-pagination">{{ $nfts->links() }}</div>
                @endif
            @else
                <div class="dx-empty">
                    <span class="dx-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </span>
                    <h3>{{ __('You don\'t own any NFTs yet') }}</h3>
                    <p>{{ __('Buy one from a creator you love, or turn something you posted into one you can sell.') }}</p>
                    <a href="{{ route('nft.marketplace') }}" class="dx-btn dx-btn--primary">{{ __('Explore NFTs') }}</a>
                    <a href="{{ route('nft.mintable') }}" class="dx-btn dx-btn--ghost">{{ __('Create from my uploads') }}</a>
                </div>
            @endif
        </section>
    </main>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
@endsection
