@php
    /**
     * One leaderboard row on the coin market.
     *
     * $crypto             - Cryptocurrency
     * $rank               - position in the list
     * $volume24h/$holders - keyed collections from the controller (avoids an N+1 per row)
     * $myUnits            - what the viewer holds, keyed by coin id
     */
    $rolling = $crypto->rollingChange(24);
    $tokenHolders = (int) (($holders ?? collect())[$crypto->id] ?? 0);
    // What the viewer owns of this coin, in dollars (no unit counts or per-coin price).
    $ownedUsd = \App\Support\TokenMoney::toUsd((float) (($myUnits ?? collect())[$crypto->id] ?? 0), (float) $crypto->current_price);
    // Sparkline from REAL recorded history only; nothing is drawn without it.
    $spark = \App\Support\Sparkline::line(\App\Support\Sparkline::prices($crypto), 64, 26);
    $down = $rolling !== null && $rolling < 0;
@endphp
<article class="lh-row cm-row">
    <a href="{{ route('cryptocurrency.show', $crypto->id) }}" class="cm-row__hit" aria-label="{{ $crypto->name }}"></a>
    <span class="cm-row__rank">{{ $rank ?? '' }}</span>
    <span class="lh-hex lh-hex--sm">
        @if($crypto->logo)
            <img src="{{ asset('storage/' . $crypto->logo) }}" alt="">
        @else
            <span class="lh-hex__glyph">{{ strtoupper(substr($crypto->name, 0, 1)) }}</span>
        @endif
    </span>
    <span class="lh-row__main">
        <span class="lh-row__title">{{ $crypto->name }}</span>
        <span class="lh-row__sub">
            @if($ownedUsd > 0)
                <span class="lh-gold">{{ __('You own') }} {{ \App\Support\TokenMoney::format($ownedUsd) }}</span>
            @else
                {{ trans_choice('{0} No owners yet|{1} :n owner|[2,*] :n owners', $tokenHolders, ['n' => number_format($tokenHolders)]) }}
            @endif
        </span>
    </span>
    @if($spark !== null)
        <span class="lh-row__spark" aria-hidden="true">
            <svg viewBox="0 0 64 26" preserveAspectRatio="none">
                <path d="{{ $spark }}" fill="none" stroke="{{ $down ? 'var(--lh-red)' : 'var(--lh-green)' }}"
                      stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
            </svg>
        </span>
    @endif
    <span class="lh-row__end">
        @if($rolling !== null)
            <span class="lh-chip lh-chip--{{ $down ? 'down' : 'up' }}" style="padding: 3px 7px">{{ $down ? '▼' : '▲' }} {{ number_format(abs($rolling), 1) }}%</span>
        @else
            {{-- No price history yet: say so, rather than printing a misleading +0.00%. --}}
            <span class="lh-chip lh-chip--new" style="padding: 3px 7px" title="{{ __('Not enough price history yet') }}">{{ __('New') }}</span>
        @endif
    </span>
    <a href="{{ route('cryptocurrency.buy.form', $crypto->id) }}" class="cm-row__buy">{{ __('Buy') }}</a>
</article>
