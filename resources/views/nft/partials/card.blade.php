@php
    /**
     * Collection card.
     * $nft      - NFT with `creator` and `activeListing` loaded
     * $currency - native token symbol
     */
    $listing = $nft->activeListing;
    $url = route('nft.show', $nft->id);
    $me = auth()->user();
    $canBuy = $listing && $me && (int) $listing->seller_id !== (int) $me->id;
    $price = $listing ? rtrim(rtrim(number_format((float) $listing->price, 4), '0'), '.') : null;
@endphp
<article class="nx-card">
    <a href="{{ $url }}" class="nx-card__media" tabindex="-1" aria-hidden="true">
        @if($nft->image_url)
            <img src="{{ $nft->image_url }}" alt="" loading="lazy" decoding="async">
        @else
            <span class="nx-placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="3"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
            </span>
        @endif
        @if($nft->media_type === 'video')
            <span class="nx-chip">
                <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 4v16l13-8z"/></svg>
                {{ __('Video') }}
            </span>
        @endif
    </a>

    <div class="nx-card__body">
        <a href="{{ $url }}" class="nx-card__name">{{ $nft->name }}</a>
        <span class="nx-card__by">
            @if($nft->creator)
                <img src="{{ $nft->creator->avatar }}" alt="" class="dx-avatar" loading="lazy">
            @endif
            <span>{{ $nft->creator->name ?? __('Unknown creator') }}</span>
        </span>

        <div class="nx-card__foot">
            <div class="nx-card__price">
                @if($listing)
                    <span>{{ __('Price') }}</span>
                    <strong>{{ $price }} {{ $currency }}</strong>
                @else
                    <span>{{ __('1 of 1') }}</span>
                    <strong class="is-muted">{{ __('Not for sale') }}</strong>
                @endif
            </div>

            @if($canBuy)
                {{-- Buying needs a connected wallet; without one the detail page walks them through it. --}}
                @if($me->wallet_address)
                    <form action="{{ route('nft.buy', $listing->id) }}" method="POST"
                          data-confirm="{{ __('Buy :name for :price :currency?', ['name' => $nft->name, 'price' => $price, 'currency' => $currency]) }}"
                          onsubmit="return confirm(this.dataset.confirm)">
                        @csrf
                        <button type="submit" class="dx-btn dx-btn--money">{{ __('Buy') }}</button>
                    </form>
                @else
                    <a href="{{ $url }}" class="dx-btn dx-btn--money">{{ __('Buy') }}</a>
                @endif
            @endif
        </div>
    </div>
</article>
