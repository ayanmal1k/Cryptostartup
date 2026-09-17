@extends('layouts.generic')

@section('page_title', __('Sell :name', ['name' => $cryptocurrency->name]))

@php
    /** Dollars only: the holding, the amount and the fees are all shown in dollars. */
    $price = (float) $cryptocurrency->current_price;
    $holding = \App\Support\TokenMoney::toUsd((float) $wallet->balance, $price);
    $platformPct = (float) ($cryptocurrency->platform_fee_percentage ?? 2.5);
    $creatorPct = (float) ($cryptocurrency->creator_fee_percentage ?? 0);
    $start = old('amount_usd', min($holding, round($holding / 2, 2)));
@endphp

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/token-trade.css') }}?v=20260917a">
@endsection

@section('content')
<div class="dx tt">
<div class="tt-wrap">

    <a href="{{ route('cryptocurrency.show', $cryptocurrency->id) }}" class="tt-back">← {{ $cryptocurrency->name }}</a>

    <form method="POST" action="{{ route('cryptocurrency.sell', $cryptocurrency->id) }}" class="tt-card" data-tt-form
          data-max="{{ $holding }}" data-fee="{{ $platformPct + $creatorPct }}">
        @csrf
        <input type="hidden" name="sell_all" value="0" data-tt-all>

        <header class="tt-head">
            @if($cryptocurrency->logo)
                <img src="{{ asset('storage/' . $cryptocurrency->logo) }}" alt="" class="tt-logo">
            @else
                <span class="tt-logo tt-logo--letter" aria-hidden="true">{{ strtoupper(substr($cryptocurrency->name, 0, 1)) }}</span>
            @endif
            <div>
                <h1 class="tt-title">{{ __('Sell :name', ['name' => $cryptocurrency->name]) }}</h1>
                <p class="tt-sub">{{ __('You have') }} <strong>{{ \App\Support\TokenMoney::format($holding) }}</strong> {{ __('of :name', ['name' => $cryptocurrency->name]) }}</p>
            </div>
        </header>

        @if(session('error'))
            <div class="dx-alert dx-alert--error">{{ session('error') }}</div>
        @endif

        <label class="tt-label" for="amount_usd">{{ __('Amount to sell') }}</label>
        <div class="tt-amount @error('amount_usd') is-invalid @enderror">
            <span aria-hidden="true">$</span>
            <input type="number" id="amount_usd" name="amount_usd" inputmode="decimal" step="0.01" min="0.01" max="{{ $holding }}"
                   value="{{ $start }}" data-tt-amount>
        </div>
        @error('amount_usd')<p class="tt-error">{{ $message }}</p>@enderror

        <div class="tt-presets" role="group" aria-label="{{ __('Quick amounts') }}">
            @foreach([25, 50] as $pct)
                <button type="button" class="tt-preset" data-tt-preset="{{ round($holding * $pct / 100, 2) }}">{{ $pct }}%</button>
            @endforeach
            <button type="button" class="tt-preset" data-tt-sell-all="{{ $holding }}">{{ __('Sell all') }}</button>
        </div>

        <dl class="tt-summary">
            <div><dt>{{ __('Selling') }}</dt><dd data-tt-echo>—</dd></div>
            <div><dt>{{ __('Fees') }} ({{ rtrim(rtrim(number_format($platformPct + $creatorPct, 2), '0'), '.') }}%)</dt><dd data-tt-fee>—</dd></div>
            <div class="is-total"><dt>{{ __('After fees') }}</dt><dd data-tt-net>—</dd></div>
        </dl>

        <button type="submit" class="dx-btn dx-btn--primary dx-btn--block tt-submit">
            {{ __('Sell') }} <span data-tt-echo>—</span>
        </button>

        <p class="tt-note">{{ __('Shown in dollars at today\'s value. We work out the coin amounts for you.') }}</p>
    </form>

</div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/token-trade.js') }}?v=20260917a" defer></script>
@endsection
