{{-- One entry from WalletHubController::activityFeed(). --}}
@php
    $icoClass = $item['kind'] === 'nft' ? 'lh-ico--nft' : ($item['in'] ? 'lh-ico--in' : 'lh-ico--out');
@endphp
<div class="lh-row">
    <span class="lh-ico {{ $icoClass }}" aria-hidden="true">
        @if($item['kind'] === 'nft')
            <svg viewBox="0 0 24 24" fill="none"><path d="M6 3h12l3 6-9 12L3 9z" stroke="currentColor" stroke-width="1.75" stroke-linejoin="round"/><path d="M3 9h18" stroke="currentColor" stroke-width="1.5"/></svg>
        @elseif($item['in'])
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12l7 7 7-7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @else
            <svg viewBox="0 0 24 24" fill="none"><path d="M12 19V5M5 12l7-7 7 7" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
        @endif
    </span>
    <span class="lh-row__main">
        <span class="lh-row__title" style="font-size: 14.5px">{{ $item['label'] }}</span>
        <span class="lh-row__sub">{{ $item['at'] ? $item['at']->diffForHumans() : '' }}</span>
    </span>
    <span class="lh-row__end">
        @if($item['amount'] !== null)
            <span class="lh-row__value {{ $item['in'] ? 'lh-up' : 'lh-flat' }}">{{ $item['in'] ? '+' : '−' }}{{ $item['amount'] }}</span>
        @else
            <span class="lh-cap">{{ $item['in'] ? __('In') : __('Out') }}</span>
        @endif
    </span>
</div>
