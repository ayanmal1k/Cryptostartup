@php
    /**
     * One coin row on the market.
     *
     * $crypto             - Cryptocurrency
     * $rank               - position in the list
     * $volume24h/$holders - keyed collections from the controller (avoids an N+1 per row)
     * $myUnits            - what the viewer holds, keyed by coin id
     */
    $rolling = $crypto->rollingChange(24);
    $down = $rolling !== null && $rolling < 0;
    $tokenHolders = (int) (($holders ?? collect())[$crypto->id] ?? 0);
    // Dollars only: what the viewer owns of this coin.
    $ownedUsd = \App\Support\TokenMoney::toUsd((float) (($myUnits ?? collect())[$crypto->id] ?? 0), (float) $crypto->current_price);
    // Sparkline from REAL recorded history only; nothing is drawn without it.
    $spark = \App\Support\Sparkline::line(\App\Support\Sparkline::prices($crypto), 72, 28);
@endphp
<a href="{{ route('cryptocurrency.show', $crypto->id) }}" class="fx-row">
    <span class="fx-coin">
        @if($crypto->logo)<img src="{{ asset('storage/' . $crypto->logo) }}" alt="">@else{{ strtoupper(substr($crypto->name, 0, 1)) }}@endif
    </span>
    <span class="fx-row__main">
        <span class="fx-row__title">{{ $crypto->name }}</span>
        <span class="fx-row__sub">
            @if($ownedUsd > 0)
                <span style="color: var(--fx-ink)">{{ __('You own') }} {{ \App\Support\TokenMoney::format($ownedUsd) }}</span>
            @else
                {{ trans_choice('{0} No owners yet|{1} :n owner|[2,*] :n owners', $tokenHolders, ['n' => number_format($tokenHolders)]) }}
            @endif
        </span>
    </span>
    @if($spark)
        <span class="fx-row__spark" aria-hidden="true">
            <svg viewBox="0 0 72 28" preserveAspectRatio="none"><path d="{{ $spark }}" fill="none" stroke="{{ $down ? 'var(--fx-down)' : 'var(--fx-up)' }}" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/></svg>
        </span>
    @endif
    <span class="fx-row__end">
        @if($rolling !== null)
            <span class="fx-pill fx-pill--{{ $down ? 'down' : 'up' }}">{{ $down ? '▼' : '▲' }} {{ number_format(abs($rolling), 1) }}%</span>
        @else
            {{-- No price history yet: say so, rather than printing a misleading +0.00%. --}}
            <span class="fx-pill" title="{{ __('Not enough price history yet') }}">{{ __('New') }}</span>
        @endif
        <span class="fx-row__meta fx-flat">#{{ $rank ?? '' }}</span>
    </span>
</a>
