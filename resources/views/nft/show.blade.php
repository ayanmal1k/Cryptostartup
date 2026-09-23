@extends('layouts.generic')

@section('page_title', $nft->name)

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/challenge-detail.css') }}?v=20260921a">
    <link rel="stylesheet" href="{{ asset('css/pages/nft-marketplace.css') }}?v=20260917b">
@endsection

@php
    /** Product page for one NFT. Same layout as a challenge; no token ids, chains or hashes. */
    $me = auth()->user();
    $credits = \App\Models\NFT::settlesInCredits();
    $money = fn ($v) => $credits
        ? '$' . number_format((float) $v, 2)
        : rtrim(rtrim(number_format((float) $v, 4), '0'), '.') . ' ' . config('web3.currency_symbol', 'POL');

    $isOwner = $nft->isOwnedBy($me);
    $isSeller = $listing && (int) $listing->seller_id === (int) $me->id;
    $image = $nft->display_image_url;
    $video = $nft->video_url;
    $royaltyPct = (int) ($nft->royalty_bps ?? 0) / 100;
    $owner = $nft->owner ?? $nft->creator;
    $ownerIsCreator = (int) $nft->ownerId() === (int) $nft->user_id;

    if ($nft->isPendingMint()) {
        $status = ['label' => __('Being created'), 'tone' => 'muted'];
    } elseif ($nft->status === \App\Models\NFT::STATUS_MINT_FAILED) {
        $status = ['label' => __("Couldn't be created"), 'tone' => 'hot'];
    } elseif ($listing) {
        $status = ['label' => __('For sale'), 'tone' => 'money'];
    } else {
        $status = ['label' => __('Not for sale'), 'tone' => 'muted'];
    }

    $canAfford = $listing && $balance >= (float) $listing->price;
    $shortfall = $listing ? max(0, (float) $listing->price - $balance) : 0;

    // Plain facts for the Details card.
    $fmtPct = fn ($v) => rtrim(rtrim(number_format((float) $v, 2), '0'), '.') . '%';
    $timesSold = (int) ($saleStats->times ?? 0);
    $highest = (float) ($saleStats->highest ?? 0);
    $lastSale = $sales->first();
    $ownedSince = $lastSale ? $lastSale->created_at : $nft->created_at;
    $creatorName = $nft->creator->name ?? __('the creator');
    $madeFrom = [
        'video' => __('A video :name posted', ['name' => $creatorName]),
        'attachment' => __('A post :name made', ['name' => $creatorName]),
    ][$nft->source_type] ?? null;

    // Suggested asking prices: around the last sale when there is one, round numbers when not.
    $hint = $nft->metadata['price_hint'] ?? null;
    $priceValue = old('price', is_numeric($hint) ? number_format((float) $hint, 2, '.', '') : '');
    $quickPrices = $lastSale
        ? array_values(array_unique(array_map(fn ($m) => round((float) $lastSale->price * $m, 2), [1, 1.1, 1.25, 1.5])))
        : [25, 50, 100, 250];
@endphp

@section('content')
<div class="dx cd">
<div class="cd-wrap">

    @foreach(['success', 'error', 'info'] as $flash)
        @if(session($flash))
            <div class="dx-alert dx-alert--{{ $flash }} nx-flash" role="status">{{ session($flash) }}</div>
        @endif
    @endforeach

    {{-- ============ The piece itself ============ --}}
    <section class="cd-stage {{ $image || $video ? 'has-video nx-stage' : '' }}">
        @if($video)
            <video src="{{ $video }}" @if($image) poster="{{ $image }}" @endif controls playsinline preload="metadata"></video>
        @elseif($image)
            <img src="{{ $image }}" alt="{{ $nft->name }}">
        @else
            <div class="cd-stage__empty">
                <span class="cd-stage__icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                </span>
                <p class="cd-stage__line">{{ __('The picture for this one isn\'t available right now.') }}</p>
            </div>
        @endif
    </section>

    <div class="cd-grid">
    <div class="cd-col">

        <header class="cd-head">
            <div class="cd-chips">
                <span class="cd-status cd-status--{{ $status['tone'] }}">
                    <span class="cd-status__dot" aria-hidden="true"></span>{{ $status['label'] }}
                </span>
                <span class="cd-type">{{ $nft->media_type === 'video' ? __('Video') : __('Photo') }} · {{ __('One of a kind') }}</span>
            </div>
            <h1 class="cd-title">{{ $nft->name }}</h1>
            <p class="cd-meta">{{ __('Created') }} {{ optional($nft->created_at)->diffForHumans() }}</p>
        </header>

        <div class="cd-people">
            @if($nft->creator)
                <a href="{{ route('profile', ['username' => $nft->creator->username]) }}" class="cd-person">
                    <img src="{{ $nft->creator->avatar }}" alt="" class="dx-avatar">
                    <span class="cd-person__text">
                        <span class="cd-person__role">{{ __('Creator') }}</span>
                        <span class="cd-person__name">{{ $nft->creator->name }}</span>
                    </span>
                </a>
            @endif
            @if($owner)
                <a href="{{ route('profile', ['username' => $owner->username]) }}" class="cd-person">
                    <img src="{{ $owner->avatar }}" alt="" class="dx-avatar">
                    <span class="cd-person__text">
                        <span class="cd-person__role">{{ __('Owner') }}</span>
                        <span class="cd-person__name">{{ $isOwner ? __('You') : $owner->name }}</span>
                    </span>
                </a>
            @endif
        </div>

        @if(trim((string) $nft->description) !== '')
            <section class="cd-card">
                <h2 class="cd-card__title">{{ __('About this piece') }}</h2>
                <p class="cd-story">{{ $nft->description }}</p>
            </section>
        @endif

        <section class="cd-card">
            <div class="cd-card__head">
                <h2 class="cd-card__title">{{ __('Sales') }}</h2>
                @if($royaltyPct > 0)
                    <span class="cd-card__aside">{{ __('Creator gets :pct% of every resale', ['pct' => rtrim(rtrim(number_format($royaltyPct, 2), '0'), '.')]) }}</span>
                @endif
            </div>
            @if($sales->count())
                <ul class="cd-list">
                    @foreach($sales as $sale)
                        @php $buyer = $buyers->get($sale->buyer_id); @endphp
                        <li class="cd-list__row">
                            @if($buyer)
                                <img src="{{ $buyer->avatar }}" alt="" class="dx-avatar">
                            @endif
                            <span class="cd-list__main">
                                <strong>{{ $buyer ? ((int) $buyer->id === (int) $me->id ? __('You') : $buyer->name) : __('A fan') }}</strong>
                                {{ __('bought it') }}
                                <span class="cd-list__time">{{ $sale->created_at->diffForHumans() }}</span>
                            </span>
                            <span class="cd-list__side is-money">{{ $money($sale->price) }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="cd-note">{{ __('Never sold yet — the first buyer owns the original.') }}</p>
            @endif
        </section>

        <section class="cd-card">
            <h2 class="cd-card__title">{{ __('Details') }}</h2>
            <dl class="cd-facts">
                <div><dt>{{ __('Kind') }}</dt><dd>{{ $nft->media_type === 'video' ? __('Video') : __('Photo') }}</dd></div>
                <div><dt>{{ __('Copies') }}</dt><dd>{{ __('Just this one — one of a kind') }}</dd></div>
                @if($madeFrom)
                    <div><dt>{{ __('Made from') }}</dt><dd>{{ $madeFrom }}</dd></div>
                @endif
                <div><dt>{{ __('Created') }}</dt><dd>{{ optional($nft->created_at)->format('M j, Y') }}</dd></div>
                @if($nft->creator)
                    <div><dt>{{ __('Creator') }}</dt><dd><a href="{{ route('profile', ['username' => $nft->creator->username]) }}">{{ $nft->creator->name }}</a></dd></div>
                @endif
                @if($owner)
                    <div><dt>{{ __('Owner') }}</dt><dd>{{ $isOwner ? __('You') : $owner->name }}</dd></div>
                @endif
                @if($ownedSince)
                    <div><dt>{{ __('Owned since') }}</dt><dd>{{ $ownedSince->format('M j, Y') }}</dd></div>
                @endif
                <div><dt>{{ __('Times sold') }}</dt><dd>{{ $timesSold ? number_format($timesSold) : __('Never') }}</dd></div>
                @if($lastSale)
                    <div><dt>{{ __('Last sold for') }}</dt><dd class="is-money">{{ $money($lastSale->price) }}</dd></div>
                @endif
                @if($timesSold > 1)
                    <div><dt>{{ __('Highest sale') }}</dt><dd class="is-money">{{ $money($highest) }}</dd></div>
                @endif
                <div><dt>{{ __('Creator’s cut of resales') }}</dt><dd>{{ $royaltyPct > 0 ? $fmtPct($royaltyPct) : __('None') }}</dd></div>
                <div><dt>{{ __('Marketplace fee') }}</dt><dd>{{ $fmtPct($feePct) }}</dd></div>
            </dl>
        </section>
    </div>

    <aside class="cd-side">
        <section class="cd-card cd-money">
            @if($nft->isPendingMint())
                <p class="cd-note">{{ __('This NFT is being created. It will be ready in a moment — refresh to check.') }}</p>

            @elseif($listing)
                <div class="cd-money__amounts">
                    <span class="cd-money__raised">{{ $money($listing->price) }}</span>
                    <span class="cd-money__goal">{{ $isSeller ? __('your asking price') : __('to own it') }}</span>
                </div>
                <div class="cd-actions">
                    @if($isSeller)
                        <form action="{{ route('nft.listing.cancel', $listing->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="dx-btn dx-btn--ghost dx-btn--block">{{ __('Stop selling') }}</button>
                        </form>
                    @elseif(!$credits || $canAfford)
                        <form action="{{ route('nft.buy', $listing->id) }}" method="POST"
                              data-confirm="{{ __('Buy “:name” for :price?', ['name' => $nft->name, 'price' => $money($listing->price)]) }}"
                              onsubmit="return confirm(this.dataset.confirm)">
                            @csrf
                            <button type="submit" class="dx-btn dx-btn--money dx-btn--block">{{ __('Buy now') }}</button>
                        </form>
                        @if($credits)
                            <p class="cd-note cd-note--center">{{ __('Paid from your balance') }} ({{ $money($balance) }})</p>
                        @endif
                    @else
                        <a href="{{ route('my.settings', ['type' => 'wallet', 'active' => 'deposit']) }}" class="dx-btn dx-btn--money dx-btn--block">
                            {{ __('Add :amount to buy', ['amount' => $money($shortfall)]) }}
                        </a>
                        <p class="cd-note cd-note--center">{{ __('Your balance is :amount', ['amount' => $money($balance)]) }}</p>
                    @endif
                </div>
                <dl class="cd-facts cd-facts--tight">
                    @if($listing->listed_at || $listing->created_at)
                        <div><dt>{{ __('Put up for sale') }}</dt><dd>{{ \Carbon\Carbon::parse($listing->listed_at ?? $listing->created_at)->diffForHumans() }}</dd></div>
                    @endif
                    @if($lastSale)
                        <div><dt>{{ __('Last sold for') }}</dt><dd class="is-money">{{ $money($lastSale->price) }}</dd></div>
                    @endif
                    @if($royaltyPct > 0 && !$ownerIsCreator)
                        <div><dt>{{ __('Goes to the creator') }}</dt><dd>{{ $money((float) $listing->price * $royaltyPct / 100) }}</dd></div>
                    @endif
                    @unless($isSeller)
                        <div><dt>{{ __('What you get') }}</dt><dd>{{ __('The original — one of a kind') }}</dd></div>
                    @endunless
                </dl>

            @elseif($isOwner && $nft->isMinted())
                <h2 class="cd-card__title">{{ __('Sell it') }}</h2>
                <form action="{{ route('nft.list', $nft->id) }}" method="POST" class="nx-sell"
                      data-fee="{{ $feePct }}" data-royalty="{{ $ownerIsCreator ? 0 : $royaltyPct }}">
                    @csrf
                    <div class="nx-sell__amount">
                        <span aria-hidden="true">{{ $credits ? '$' : '' }}</span>
                        <input type="number" name="price" step="0.01" min="{{ $credits ? '1' : '0.000001' }}"
                               inputmode="decimal" placeholder="0.00" required aria-label="{{ __('Price') }}"
                               value="{{ $priceValue }}">
                    </div>
                    @if($credits)
                        <div class="cd-quick" role="group" aria-label="{{ __('Suggested prices') }}">
                            @foreach($quickPrices as $i => $q)
                                <button type="button" data-price="{{ number_format($q, 2, '.', '') }}">
                                    {{ $lastSale && $i === 0 ? __('Last sale') . ' · ' : '' }}${{ number_format($q, $q == floor($q) ? 0 : 2) }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                    @error('price')<p class="cd-note nx-error">{{ $message }}</p>@enderror
                    <p class="cd-note">{{ __('You get') }} <strong data-sell-net>—</strong>
                        {{ $ownerIsCreator ? __('after the :fee% fee', ['fee' => rtrim(rtrim(number_format($feePct, 2), '0'), '.')]) : __('after the fee and the creator\'s cut') }}</p>
                    <dl class="cd-facts cd-facts--tight">
                        <div><dt>{{ __('Buyer pays') }}</dt><dd data-sell-gross>—</dd></div>
                        <div><dt>{{ __('Marketplace fee') }} ({{ $fmtPct($feePct) }})</dt><dd data-sell-fee>—</dd></div>
                        @if(!$ownerIsCreator && $royaltyPct > 0)
                            <div><dt>{{ __('Creator’s cut') }} ({{ $fmtPct($royaltyPct) }})</dt><dd data-sell-royalty>—</dd></div>
                        @endif
                    </dl>
                    <div class="cd-actions nx-sell__actions">
                        <button type="submit" class="dx-btn dx-btn--primary dx-btn--block">{{ __('Put it up for sale') }}</button>
                    </div>
                </form>

            @elseif($nft->status === \App\Models\NFT::STATUS_MINT_FAILED)
                <p class="cd-note">{{ __('Something went wrong creating this NFT.') }}</p>

            @else
                <div class="cd-money__amounts">
                    <span class="cd-money__raised nx-not-for-sale">{{ __('Not for sale') }}</span>
                </div>
                <p class="cd-note nx-owner-note">{{ __(':name owns it. If they put it up for sale, it will show here.', ['name' => $owner->name ?? __('The owner')]) }}</p>
            @endif
        </section>

        <nav class="cd-card cd-more" aria-label="{{ __('More') }}">
            <button type="button" class="cd-more__link" data-share data-share-title="{{ $nft->name }}" data-copied="{{ __('Link copied') }}">
                <span>{{ __('Share this NFT') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 12v7a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-7M16 6l-4-4-4 4M12 2v13"/></svg>
            </button>
            <button type="button" class="cd-more__link" data-copy-link data-copied="{{ __('Link copied') }}">
                <span>{{ __('Copy link') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7"/><path d="M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/></svg>
            </button>
            @if($nft->creator)
                <a href="{{ route('profile', ['username' => $nft->creator->username]) }}" class="cd-more__link">
                    <span>{{ __('See :name’s profile', ['name' => $nft->creator->name]) }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </a>
            @endif
            <a href="{{ route('nft.marketplace') }}" class="cd-more__link">
                <span>{{ __('Explore more NFTs') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
            <a href="{{ route('nft.my-nfts') }}" class="cd-more__link">
                <span>{{ __('My NFTs') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
            <a href="{{ route('nft.mintable') }}" class="cd-more__link">
                <span>{{ __('Sell one of my uploads') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
            </a>
        </nav>
    </aside>
    </div>

    @if($moreFromCreator->count())
        <section class="cd-related">
            <div class="cd-related__head">
                <h2 class="cd-related__title">{{ __('More from :name', ['name' => $creatorName]) }}</h2>
                @if($nft->creator)
                    <a href="{{ route('profile', ['username' => $nft->creator->username]) }}" class="cd-related__link">{{ __('See profile') }}</a>
                @endif
            </div>
            <div class="nx-grid">
                @foreach($moreFromCreator as $other)
                    @include('nft.partials.card', ['nft' => $other])
                @endforeach
            </div>
        </section>
    @endif

</div>
</div>

<script src="{{ asset('js/pages/detail-share.js') }}?v=20260921a" defer></script>

<script>
/* Sell form: show what the seller actually receives as they type. */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.nx-sell');
    if (!form) return;
    var input = form.querySelector('input[name="price"]');
    var out = form.querySelector('[data-sell-net]');
    var keep = 1 - ((parseFloat(form.dataset.fee) || 0) + (parseFloat(form.dataset.royalty) || 0)) / 100;
    var prefix = @json($credits ? '$' : '');
    var fee = (parseFloat(form.dataset.fee) || 0) / 100;
    var royalty = (parseFloat(form.dataset.royalty) || 0) / 100;
    var fmt = function (v) { return prefix + v.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); };
    var set = function (sel, v) { var el = form.querySelector(sel); if (el) el.textContent = v; };
    var chips = form.querySelectorAll('.cd-quick [data-price]');
    var update = function () {
        var p = parseFloat(input.value);
        var ok = p > 0;
        out.textContent = ok ? fmt(p * keep) : '—';
        set('[data-sell-gross]', ok ? fmt(p) : '—');
        set('[data-sell-fee]', ok ? '−' + fmt(p * fee) : '—');
        set('[data-sell-royalty]', ok ? '−' + fmt(p * royalty) : '—');
        chips.forEach(function (c) { c.classList.toggle('is-active', ok && parseFloat(c.dataset.price) === p); });
    };
    chips.forEach(function (c) {
        c.addEventListener('click', function () { input.value = c.dataset.price; update(); input.focus(); });
    });
    input.addEventListener('input', update);
    update();
});
</script>
@endsection
