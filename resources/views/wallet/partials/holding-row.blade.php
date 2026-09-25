{{-- One coin the user holds: $h from the wallet view's $holdings (crypto, value, move, dir, spark). --}}
<a href="{{ route('cryptocurrency.show', $h->crypto->id) }}" class="lh-row">
    <span class="lh-hex lh-hex--sm">
        @if($h->crypto->logo)
            <img src="{{ asset('storage/' . $h->crypto->logo) }}" alt="">
        @else
            <span class="lh-hex__glyph">{{ strtoupper(substr($h->crypto->name, 0, 1)) }}</span>
        @endif
    </span>
    <span class="lh-row__main">
        <span class="lh-row__title">{{ $h->crypto->name }}</span>
        <span class="lh-row__sub lh-{{ $h->dir }}">
            @if($h->dir === 'flat')
                {{ __('No change today') }}
            @else
                {{ $h->dir === 'up' ? '▲' : '▼' }} {{ \App\Support\TokenMoney::format(abs($h->move)) }} {{ __('today') }}
            @endif
        </span>
    </span>
    {{-- Only plotted from real recorded history; with none, nothing is drawn. --}}
    @if($h->spark !== null)
        <span class="lh-row__spark" aria-hidden="true">
            <svg viewBox="0 0 64 26" preserveAspectRatio="none">
                <path d="{{ $h->spark }}" fill="none" stroke="{{ $h->dir === 'down' ? 'var(--lh-red)' : 'var(--lh-green)' }}"
                      stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke"/>
            </svg>
        </span>
    @endif
    <span class="lh-row__end">
        <span class="lh-row__value">{{ \App\Support\TokenMoney::format($h->value) }}</span>
        <span class="lh-cap">{{ __('Worth') }}</span>
    </span>
</a>
