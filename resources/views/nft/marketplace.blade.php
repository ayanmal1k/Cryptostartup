@extends('layouts.generic')

@section('page_title', __('NFT Marketplace'))

@php
    $currency = $economics['currency'];
    $prefix = $economics['prefix'];
    // "$40.00" in dollars; "40 POL" for on-chain settlement.
    $money = fn ($v) => $prefix
        ? $currency . number_format((float) $v, 2)
        : rtrim(rtrim(number_format((float) $v, 4), '0'), '.') . ' ' . $currency;

    $browsing = $search === '' && $view === 'explore' && $items->currentPage() === 1;

    // Earnings calculator. Mirrors the marketplace service: first sale = price - platform fee,
    // and every resale after that pays the creator their royalty.
    $calcSteps = [5, 10, 25, 50, 100, 250, 500, 1000];
    $calcIndex = 3;
    $calcResales = 10;
    $calcPrice = $calcSteps[$calcIndex];
    $calcFirst = $calcPrice * (1 - $economics['fee_pct'] / 100);
    $calcEach = $calcPrice * $economics['royalty_pct'] / 100;
    $pct = fn ($v) => rtrim(rtrim(number_format($v, 2), '0'), '.');
@endphp

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
            ['label' => __('Explore'), 'href' => route('nft.marketplace'), 'active' => $search === '' && $view === 'explore'],
            ['label' => __('For sale'), 'href' => route('nft.marketplace', ['view' => 'sale']), 'active' => $search === '' && $view === 'sale'],
            ['label' => __('My NFTs'), 'href' => route('nft.my-nfts'), 'active' => false],
        ],
        'search' => $search,
        'searchAction' => route('nft.marketplace'),
        'searchHidden' => $view === 'sale' ? ['view' => 'sale'] : [],
        'searchLabel' => __('Search NFTs'),
        'cta' => ['label' => __('Create'), 'href' => route('nft.mintable')],
    ])

    <main class="dx-main">

        @foreach(['success', 'error', 'info'] as $flash)
            @if(session($flash))
                <div class="dx-alert dx-alert--{{ $flash }}" role="status">{{ session($flash) }}</div>
            @endif
        @endforeach

        {{-- ============ Spotlight ============ --}}
        @if($spotlight && $browsing)
            @php
                $h = $spotlight;
                $hListing = $h->activeListing;
                $hUrl = route('nft.show', $h->id);
                $hImage = $h->display_image_url;
                $hVideo = $h->video_url;
            @endphp
            <section class="nx-spot" data-dx-media aria-label="{{ __('Featured') }}">
                <a href="{{ $hUrl }}" class="nx-spot__media" tabindex="-1" aria-hidden="true">
                    @if($hVideo)
                        <video data-dx-autoplay data-src="{{ $hVideo }}" @if($hImage) poster="{{ $hImage }}" @endif
                               muted loop playsinline preload="none"></video>
                    @elseif($hImage)
                        <img src="{{ $hImage }}" alt="">
                    @else
                        <span class="nx-spot__blank">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                        </span>
                    @endif
                </a>
                <span class="nx-spot__scrim"></span>

                <div class="nx-spot__top">
                    <span class="nx-tile__chip">{{ __('Featured') }}</span>
                    @if($hListing)
                        <span class="nx-tile__chip nx-tile__chip--money">{{ $money($hListing->price) }}</span>
                    @endif
                </div>

                <div class="nx-spot__info">
                    @if($h->creator)
                        <a href="{{ route('profile', ['username' => $h->creator->username]) }}" class="nx-spot__by">
                            <img src="{{ $h->creator->avatar }}" alt="" class="dx-avatar">
                            {{ $h->creator->name }}
                        </a>
                    @endif
                    <h1 class="nx-spot__title"><a href="{{ $hUrl }}">{{ $h->name }}</a></h1>
                    <div class="nx-spot__cta">
                        @if($hListing && (int) $hListing->seller_id !== (int) auth()->id())
                            <a href="{{ $hUrl }}" class="dx-btn dx-btn--money">{{ __('Buy for :price', ['price' => $money($hListing->price)]) }}</a>
                        @else
                            <a href="{{ $hUrl }}" class="dx-btn dx-btn--primary">{{ __('Take a look') }}</a>
                        @endif
                    </div>
                </div>
            </section>

            <a href="#earn" class="nx-earn-link">
                <span class="nx-earn-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17 9 11l4 4 8-8"/><path d="M14 7h7v7"/></svg>
                </span>
                <span>{{ __('Creators earn') }} <strong>{{ $pct($economics['royalty_pct']) }}%</strong> {{ __('every time their NFT resells. See what yours could make.') }}</span>
                <span class="nx-earn-link__go" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m9 6 6 6-6 6"/></svg>
                </span>
            </a>
        @endif

        {{-- ============ Collection ============ --}}
        <section>
            <div class="dx-section__head">
                <div>
                    @if($search !== '')
                        <h2 class="dx-section__title">{{ __('Results for') }} “{{ $search }}”</h2>
                        <p class="dx-section__sub">{{ trans_choice('{0} Nothing found|{1} :count NFT|[2,*] :count NFTs', $items->total(), ['count' => $items->total()]) }}</p>
                    @elseif($view === 'sale')
                        <h2 class="dx-section__title">{{ __('For sale now') }}</h2>
                        @if($stats['floor'] > 0)
                            <p class="dx-section__sub">{{ __('From') }} {{ $money($stats['floor']) }}</p>
                        @endif
                    @else
                        <h2 class="dx-section__title">{{ __('Explore') }}</h2>
                        <div class="dx-pulse dx-section__sub">
                            @if($stats['minted'] > 0)
                                <span><strong>{{ number_format($stats['minted']) }}</strong> {{ trans_choice('{1} NFT|[2,*] NFTs', $stats['minted']) }}</span>
                            @endif
                            @if($stats['listed'] > 0)
                                <span><strong>{{ number_format($stats['listed']) }}</strong> {{ __('for sale') }}</span>
                            @endif
                            @if($stats['volume'] > 0)
                                <span class="is-money"><strong>{{ $money($stats['volume']) }}</strong> {{ __('sold') }}</span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            @if($view === 'sale' && $items->count() > 1)
                <nav class="nx-sort" aria-label="{{ __('Sort') }}">
                    @foreach(['newest' => __('Newest'), 'price_low' => __('Lowest price'), 'price_high' => __('Highest price')] as $key => $label)
                        <a href="{{ route('nft.marketplace', array_filter(['view' => 'sale', 'search' => $search, 'sort' => $key])) }}"
                           class="{{ $sort === $key ? 'is-active' : '' }}">{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            @if($items->count())
                <div class="nx-grid">
                    @foreach($items as $nft)
                        @include('nft.partials.card', ['nft' => $nft])
                    @endforeach
                </div>

                @if($items->hasPages())
                    <div class="dx-pagination nx-pagination">{{ $items->links() }}</div>
                @endif
            @else
                <div class="dx-empty">
                    <span class="dx-empty__icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </span>
                    @if($search !== '')
                        <h3>{{ __('Nothing by that name') }}</h3>
                        <p>{{ __('Try a different word.') }}</p>
                        <a href="{{ route('nft.marketplace', $view === 'sale' ? ['view' => 'sale'] : []) }}" class="dx-btn dx-btn--ghost">{{ __('Clear search') }}</a>
                    @elseif($view === 'sale')
                        <h3>{{ __('Nothing for sale right now') }}</h3>
                        <p>{{ __('Put something you own up for sale, or browse everything on the platform.') }}</p>
                        <a href="{{ route('nft.marketplace') }}" class="dx-btn dx-btn--ghost">{{ __('Browse all NFTs') }}</a>
                    @else
                        <h3>{{ __('The first NFT could be yours') }}</h3>
                        <p>{{ __('Every NFT here starts as a photo or video posted on the site. Turn one of yours into a one-of-a-kind piece people can buy.') }}</p>
                        <a href="{{ route('nft.mintable') }}" class="dx-btn dx-btn--primary">{{ __('Create from my uploads') }}</a>
                    @endif
                </div>
            @endif
        </section>

        {{-- ============ The viewer's own uploads, ready to turn into NFTs ============ --}}
        @if($mintable->count())
            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Sell your uploads') }}</h2>
                        <p class="dx-section__sub">{{ __('Turn a video you posted into a one-of-a-kind NFT.') }}</p>
                    </div>
                    <a href="{{ route('nft.mintable') }}" class="dx-section__link">{{ __('See all') }}</a>
                </div>
                <div class="dx-rail nx-uploads">
                    @foreach($mintable as $video)
                        <a href="{{ route('nft.mint-from', ['video', $video->id]) }}" class="nx-upload">
                            @if($video->thumbnail_path)
                                <img src="{{ asset('storage/' . $video->thumbnail_path) }}" alt="" loading="lazy">
                            @endif
                            <span class="nx-upload__foot">
                                <span class="nx-upload__title">{{ $video->title }}</span>
                                <span class="nx-upload__mint">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                                    {{ __('Sell it') }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- ============ What creators make ============ --}}
        @if($browsing)
            <section class="nx-earn" id="earn">
                <div class="nx-earn__copy">
                    <span class="dx-eyebrow">{{ __('For creators') }}</span>
                    <h2>{{ __('Sell it once. Get paid every time it sells again.') }}</h2>
                    <p>
                        {{ __('Turn a photo or video you have already posted into a one-of-a-kind NFT. You keep the first sale, and every time a fan resells it here, your cut comes back to you.') }}
                    </p>
                    <a href="{{ route('nft.mintable') }}" class="dx-btn dx-btn--primary">{{ __('Create from my uploads') }}</a>
                </div>

                <div class="nx-calc" data-dx-earn
                     data-steps="{{ implode(',', $calcSteps) }}"
                     data-fee="{{ $economics['fee_pct'] }}"
                     data-royalty="{{ $economics['royalty_pct'] }}"
                     data-resales="{{ $calcResales }}"
                     data-currency="{{ $currency }}"
                     data-prefix="{{ $prefix ? '1' : '0' }}">
                    {{-- Not a <label>: app.css forces every label to a grey !important colour. --}}
                    <div class="nx-calc__label">
                        <span id="nx-calc-price-label">{{ __('If it sells for') }}</span>
                        <output data-out="price" for="nx-calc-price">{{ $money($calcPrice) }}</output>
                    </div>
                    <input type="range" id="nx-calc-price" aria-labelledby="nx-calc-price-label"
                           min="0" max="{{ count($calcSteps) - 1 }}" step="1" value="{{ $calcIndex }}">
                    <dl>
                        <div>
                            <dt>{{ __('You keep on the first sale') }}</dt>
                            <dd data-out="first">{{ $money($calcFirst) }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('You earn on every resale') }}</dt>
                            <dd data-out="resale">{{ $money($calcEach) }}</dd>
                        </div>
                        <div class="is-total">
                            <dt>{{ __('After :count resales', ['count' => $calcResales]) }}</dt>
                            <dd data-out="total">{{ $money($calcFirst + $calcEach * $calcResales) }}</dd>
                        </div>
                    </dl>
                    <p class="nx-calc__note">
                        {{ __('An example, not a promise. Assumes each resale is at the same price. :fee% marketplace fee per sale; :royalty% is the default creator cut, and you can set your own.', ['fee' => $pct($economics['fee_pct']), 'royalty' => $pct($economics['royalty_pct'])]) }}
                    </p>
                </div>
            </section>
        @endif

    </main>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/discover.js') }}?v=20260917b" defer></script>
@endsection
