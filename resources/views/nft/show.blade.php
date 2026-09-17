@extends('layouts.generic')

@section('page_title', $nft->name)

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/challenge-detail.css') }}?v=20260917a">
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

            @elseif($isOwner && $nft->isMinted())
                <h2 class="cd-card__title">{{ __('Sell it') }}</h2>
                <form action="{{ route('nft.list', $nft->id) }}" method="POST" class="nx-sell"
                      data-fee="{{ $feePct }}" data-royalty="{{ $ownerIsCreator ? 0 : $royaltyPct }}">
                    @csrf
                    <div class="nx-sell__amount">
                        <span aria-hidden="true">{{ $credits ? '$' : '' }}</span>
                        <input type="number" name="price" step="0.01" min="{{ $credits ? '1' : '0.000001' }}"
                               inputmode="decimal" placeholder="0.00" required aria-label="{{ __('Price') }}"
                               value="{{ old('price', $nft->metadata['price_hint'] ?? '') }}">
                    </div>
                    @error('price')<p class="cd-note nx-error">{{ $message }}</p>@enderror
                    <p class="cd-note">{{ __('You get') }} <strong data-sell-net>—</strong>
                        {{ $ownerIsCreator ? __('after the :fee% fee', ['fee' => rtrim(rtrim(number_format($feePct, 2), '0'), '.')]) : __('after the fee and the creator\'s cut') }}</p>
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

</div>
</div>

<script>
/* Sell form: show what the seller actually receives as they type. */
document.addEventListener('DOMContentLoaded', function () {
    var form = document.querySelector('.nx-sell');
    if (!form) return;
    var input = form.querySelector('input[name="price"]');
    var out = form.querySelector('[data-sell-net]');
    var keep = 1 - ((parseFloat(form.dataset.fee) || 0) + (parseFloat(form.dataset.royalty) || 0)) / 100;
    var prefix = @json($credits ? '$' : '');
    var update = function () {
        var p = parseFloat(input.value);
        out.textContent = p > 0 ? prefix + (p * keep).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }) : '—';
    };
    input.addEventListener('input', update);
    update();
});
</script>
@endsection
