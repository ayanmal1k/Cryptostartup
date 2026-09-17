@extends('layouts.generic')

@section('page_title', __('NFT Marketplace'))

@php
    $currency = $economics['currency'];
    $num = fn ($v) => rtrim(rtrim(number_format((float) $v, 4), '0'), '.');

    $browsing = $search === '' && $view === 'explore' && $items->currentPage() === 1;

    // Earnings calculator. Mirrors the marketplace service: first sale = price - platform fee,
    // and every resale after that pays the creator their royalty.
    $calcSteps = [5, 10, 25, 50, 100, 250, 500, 1000];
    $calcIndex = 3;
    $calcResales = 10;
    $calcPrice = $calcSteps[$calcIndex];
    $calcFirst = $calcPrice * (1 - $economics['fee_pct'] / 100);
    $calcEach = $calcPrice * $economics['royalty_pct'] / 100;
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917a">
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
        'cta' => ['label' => __('Mint'), 'href' => route('nft.mintable')],
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
                $hVideo = $h->media_type === 'video' ? ($h->metadata['animation_url'] ?? null) : null;
                $hMe = auth()->user();
                $hCanBuy = $hListing && $hMe && (int) $hListing->seller_id !== (int) $hMe->id;
            @endphp
            <section class="nx-hero" aria-label="{{ __('Spotlight') }}">
                <div class="nx-hero__media" data-dx-media>
                    <a href="{{ $hUrl }}" tabindex="-1" aria-hidden="true">
                        @if($hVideo)
                            <video data-dx-autoplay data-src="{{ $hVideo }}" poster="{{ $h->image_url }}"
                                   muted loop playsinline preload="none"></video>
                            <span class="nx-play"><svg viewBox="0 0 24 24"><path d="M7 4v16l13-8z"/></svg></span>
                        @elseif($h->image_url)
                            <img src="{{ $h->image_url }}" alt="">
                        @else
                            <span class="nx-placeholder">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                            </span>
                        @endif
                    </a>
                    <div class="nx-hero__chips">
                        <span class="nx-chip">{{ __('Spotlight') }}</span>
                        @if($hListing)
                            <span class="nx-chip nx-chip--money">{{ __('For sale') }}</span>
                        @endif
                    </div>
                </div>

                <div class="nx-hero__panel">
                    <h1 class="nx-hero__title"><a href="{{ $hUrl }}">{{ $h->name }}</a></h1>

                    @if($h->creator)
                        <a href="{{ route('profile', ['username' => $h->creator->username]) }}" class="nx-by">
                            <img src="{{ $h->creator->avatar }}" alt="" class="dx-avatar">
                            <span>{{ __('Created by') }}<strong>{{ $h->creator->name }}</strong></span>
                        </a>
                    @endif

                    <div class="nx-price">
                        @if($hListing)
                            <span class="nx-price__label">{{ __('Price') }}</span>
                            <span class="nx-price__value">{{ $num($hListing->price) }} <small>{{ $currency }}</small></span>
                        @else
                            <span class="nx-price__label">{{ __('1 of 1 · owned') }}</span>
                            <span class="nx-price__value is-muted">{{ __('Not for sale yet') }}</span>
                        @endif
                    </div>

                    <div class="nx-hero__actions">
                        @if($hCanBuy && $hMe->wallet_address)
                            <form action="{{ route('nft.buy', $hListing->id) }}" method="POST"
                                  data-confirm="{{ __('Buy :name for :price :currency?', ['name' => $h->name, 'price' => $num($hListing->price), 'currency' => $currency]) }}"
                                  onsubmit="return confirm(this.dataset.confirm)">
                                @csrf
                                <button type="submit" class="dx-btn dx-btn--money dx-btn--block">{{ __('Buy now') }}</button>
                            </form>
                        @elseif($hCanBuy)
                            <a href="{{ $hUrl }}" class="dx-btn dx-btn--money">{{ __('Buy now') }}</a>
                        @endif
                        <a href="{{ $hUrl }}" class="dx-btn dx-btn--ghost">{{ __('View details') }}</a>
                    </div>

                    <dl class="nx-facts">
                        @if($h->royalty_bps)
                            <div><dt>{{ __('Creator royalty') }}</dt><dd>{{ $num($h->royalty_bps / 100) }}%</dd></div>
                        @endif
                        @if($h->token_id !== null && $h->token_id !== '')
                            <div><dt>{{ __('Token') }}</dt><dd>#{{ $h->token_id }}</dd></div>
                        @endif
                        <div><dt>{{ __('Chain') }}</dt><dd>Polygon</dd></div>
                    </dl>
                </div>
            </section>

            <a href="#earn" class="nx-earn-link">
                <span class="nx-earn-link__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 17 9 11l4 4 8-8"/><path d="M14 7h7v7"/></svg>
                </span>
                <span>{{ __('Creators earn') }} <strong>{{ $num($economics['royalty_pct']) }}%</strong> {{ __('every time their NFT resells. See what yours could make.') }}</span>
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
                        <p class="dx-section__sub">{{ trans_choice('{0} No NFTs|{1} :count NFT|[2,*] :count NFTs', $items->total(), ['count' => $items->total()]) }}</p>
                    @elseif($view === 'sale')
                        <h2 class="dx-section__title">{{ __('For sale now') }}</h2>
                        @if($stats['floor'] > 0)
                            <p class="dx-section__sub">{{ __('Floor') }} {{ $num($stats['floor']) }} {{ $currency }}</p>
                        @endif
                    @else
                        <h2 class="dx-section__title">{{ __('Explore') }}</h2>
                        <div class="dx-pulse dx-section__sub">
                            @if($stats['minted'] > 0)
                                <span><strong>{{ number_format($stats['minted']) }}</strong> {{ trans_choice('{1} NFT minted|[2,*] NFTs minted', $stats['minted']) }}</span>
                            @endif
                            @if($stats['owners'] > 0)
                                <span><strong>{{ number_format($stats['owners']) }}</strong> {{ trans_choice('{1} collector|[2,*] collectors', $stats['owners']) }}</span>
                            @endif
                            @if($stats['volume'] > 0)
                                <span class="is-money"><strong>{{ $num($stats['volume']) }} {{ $currency }}</strong> {{ __('traded') }}</span>
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
                        @include('nft.partials.card', ['nft' => $nft, 'currency' => $currency])
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
                        <p>{{ __('Be the first: list something you own, or browse every NFT on the platform.') }}</p>
                        <a href="{{ route('nft.marketplace') }}" class="dx-btn dx-btn--ghost">{{ __('Browse all NFTs') }}</a>
                    @else
                        <h3>{{ __('The first NFT could be yours') }}</h3>
                        <p>{{ __('Every NFT here starts as a photo or video posted on the site. Turn one of yours into a 1-of-1.') }}</p>
                        <a href="{{ route('nft.mintable') }}" class="dx-btn dx-btn--primary">{{ __('Mint from my uploads') }}</a>
                    @endif
                </div>
            @endif
        </section>

        {{-- ============ The viewer's own uploads, ready to mint ============ --}}
        @if($mintable->count())
            <section>
                <div class="dx-section__head">
                    <div>
                        <h2 class="dx-section__title">{{ __('Your uploads, ready to mint') }}</h2>
                        <p class="dx-section__sub">{{ __('Posted it already? It can be an NFT in a minute.') }}</p>
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
                                    {{ __('Mint') }}
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
                        {{ __('Turn a photo or video you have already posted into a 1-of-1 NFT. You keep the first sale, and every time a collector resells it on this marketplace, your royalty comes back to you.') }}
                    </p>
                    <a href="{{ route('nft.mintable') }}" class="dx-btn dx-btn--primary">{{ __('Mint from my uploads') }}</a>
                </div>

                <div class="nx-calc" data-dx-earn
                     data-steps="{{ implode(',', $calcSteps) }}"
                     data-fee="{{ $economics['fee_pct'] }}"
                     data-royalty="{{ $economics['royalty_pct'] }}"
                     data-resales="{{ $calcResales }}"
                     data-currency="{{ $currency }}">
                    {{-- Not a <label>: app.css forces every label to a grey !important colour. --}}
                    <div class="nx-calc__label">
                        <span id="nx-calc-price-label">{{ __('If it sells for') }}</span>
                        <output data-out="price" for="nx-calc-price">{{ $num($calcPrice) }} {{ $currency }}</output>
                    </div>
                    <input type="range" id="nx-calc-price" aria-labelledby="nx-calc-price-label"
                           min="0" max="{{ count($calcSteps) - 1 }}" step="1" value="{{ $calcIndex }}">
                    <dl>
                        <div>
                            <dt>{{ __('You keep on the first sale') }}</dt>
                            <dd data-out="first">{{ $num($calcFirst) }} {{ $currency }}</dd>
                        </div>
                        <div>
                            <dt>{{ __('You earn on every resale') }}</dt>
                            <dd data-out="resale">{{ $num($calcEach) }} {{ $currency }}</dd>
                        </div>
                        <div class="is-total">
                            <dt>{{ __('After :count resales', ['count' => $calcResales]) }}</dt>
                            <dd data-out="total">{{ $num($calcFirst + $calcEach * $calcResales) }} {{ $currency }}</dd>
                        </div>
                    </dl>
                    <p class="nx-calc__note">
                        {{ __('An example, not a promise. Assumes each resale is at the same price. :fee% marketplace fee per sale; :royalty% is the default royalty, and you can set your own when you mint.', ['fee' => $num($economics['fee_pct']), 'royalty' => $num($economics['royalty_pct'])]) }}
                    </p>
                </div>
            </section>
        @endif

    </main>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/discover.js') }}?v=20260917a" defer></script>
@endsection
