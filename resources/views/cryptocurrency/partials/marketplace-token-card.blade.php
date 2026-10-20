@php
    /**
     * $crypto            - Cryptocurrency
     * $volume24h/$holders - keyed collections from the controller (avoids an N+1 per card)
     */
    $rolling = $crypto->rollingChange(24);
    $hasHistory = $rolling !== null;
    $change = $hasHistory ? $rolling : (float) $crypto->price_change_percentage;
    $isUp = $change >= 0;

    $priceDecimals = $crypto->current_price < 1 ? 4 : 2;
    $tokenVolume = (float) (($volume24h ?? collect())[$crypto->id] ?? 0);
    $tokenHolders = (int) (($holders ?? collect())[$crypto->id] ?? 0);

    // Sparkline from REAL recorded history. Null when there isn't enough to plot — we draw a
    // flat baseline rather than inventing movement.
    $points = collect($crypto->priceHistoryPoints())->pluck('price')->take(-24)->values()->all();
    $sparkPath = null;
    if (count($points) >= 2) {
        $min = min($points);
        $max = max($points);
        $range = ($max - $min) ?: 1;
        $step = 120 / (count($points) - 1);
        $sparkPath = '';
        foreach ($points as $i => $p) {
            $x = round($i * $step, 2);
            $y = round(44 - (($p - $min) / $range) * 44, 2);
            $sparkPath .= ($i === 0 ? 'M' : 'L') . $x . ' ' . $y . ' ';
        }
        $sparkPath = trim($sparkPath);
    }
@endphp
<article class="mp-token">
    <a href="{{ route('cryptocurrency.show', $crypto->id) }}" class="mp-token__body">
        <div class="mp-token__top">
            <div class="mp-token__id">
                @if($crypto->logo)
                    <img src="{{ asset('storage/' . $crypto->logo) }}" alt="{{ $crypto->name }}" class="mp-token__logo">
                @else
                    <span class="mp-token__logo mp-token__logo--fallback">{{ strtoupper(substr($crypto->symbol ?? $crypto->name, 0, 2)) }}</span>
                @endif
                <div class="mp-token__meta">
                    <span class="mp-token__name">{{ $crypto->name }}</span>
                    <span class="mp-token__symbol">{{ $crypto->symbol }}</span>
                </div>
            </div>
            @if($hasHistory)
                <span class="mp-token__change mp-token__change--{{ $isUp ? 'up' : 'down' }}">
                    {{ $isUp ? '+' : '' }}{{ number_format($change, 2) }}%
                </span>
            @else
                {{-- No price history yet: say so, rather than printing a misleading +0.00%. --}}
                <span class="mp-token__change mp-token__change--flat" title="{{ __('Not enough price history yet') }}">{{ __('New') }}</span>
            @endif
        </div>

        <div class="mp-token__price">${{ number_format($crypto->current_price, $priceDecimals) }}</div>

        <div class="mp-token__spark">
            <svg viewBox="0 0 120 44" preserveAspectRatio="none" aria-hidden="true">
                <path d="{{ $sparkPath ?? 'M0 22 L120 22' }}"
                      fill="none"
                      stroke="{{ $sparkPath === null ? 'currentColor' : ($isUp ? '#22c55e' : '#f43f5e') }}"
                      stroke-width="2"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      @if($sparkPath === null) opacity="0.2" @endif/>
            </svg>
        </div>

        <div class="mp-token__stats">
            <div class="mp-token__stat">
                <span class="mp-token__stat-label">{{ __('Holders') }}</span>
                <span class="mp-token__stat-value">{{ number_format($tokenHolders) }}</span>
            </div>
            <div class="mp-token__stat">
                <span class="mp-token__stat-label">{{ __('24h Vol') }}</span>
                <span class="mp-token__stat-value">${{ number_format($tokenVolume, 2) }}</span>
            </div>
            <div class="mp-token__stat">
                <span class="mp-token__stat-label">{{ __('Market Cap') }}</span>
                <span class="mp-token__stat-value">
                    {{-- Market cap is circulating_supply x price; show a dash rather than "$0"
                         when nothing is circulating, which reads as broken. --}}
                    @if((float) $crypto->market_cap > 0)
                        ${{ number_format($crypto->market_cap, 0) }}
                    @else
                        &mdash;
                    @endif
                </span>
            </div>
        </div>

        @if($crypto->creator)
            <div class="mp-token__creator">
                <img src="{{ $crypto->creator->avatar ?? asset('img/default-avatar.png') }}" alt="" class="mp-token__creator-avatar" loading="lazy">
                <span>{{ __('by') }} {{ $crypto->creator->name }}</span>
            </div>
        @endif
    </a>

    <div class="mp-token__actions">
        <a href="{{ route('cryptocurrency.buy.form', $crypto->id) }}" class="mp-token__action mp-token__action--buy">{{ __('Buy') }}</a>
        <a href="{{ route('cryptocurrency.show', $crypto->id) }}" class="mp-token__action">{{ __('Details') }}</a>
    </div>
</article>
