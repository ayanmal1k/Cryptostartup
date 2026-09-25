@extends('layouts.generic')

@section('page_title', __('Buy :name', ['name' => $cryptocurrency->name]))

@php
    /** Dollars only: no ticker, unit count or per-coin price is ever shown or asked for. */
    $price = (float) $cryptocurrency->current_price;
    $available = \App\Support\TokenMoney::toUsd((float) $cryptocurrency->available_supply, $price);
    $maxUsd = min(10000, floor($available));
    $presets = array_values(array_filter([5, 10, 25, 50, 100], fn ($p) => $p <= $maxUsd));
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

    <form method="POST" action="{{ route('cryptocurrency.buy', $cryptocurrency->id) }}" class="tt-card" data-tt-form data-max="{{ $maxUsd }}">
        @csrf

        <header class="tt-head">
            @if($cryptocurrency->logo)
                <img src="{{ asset('storage/' . $cryptocurrency->logo) }}" alt="" class="tt-logo">
            @else
                <span class="tt-logo tt-logo--letter" aria-hidden="true">{{ strtoupper(substr($cryptocurrency->name, 0, 1)) }}</span>
            @endif
            <div>
                <h1 class="tt-title">{{ __('Buy :name', ['name' => $cryptocurrency->name]) }}</h1>
                <p class="tt-sub">{{ __('Choose how much you want to spend.') }}</p>
            </div>
        </header>

        @if(session('error'))
            <div class="dx-alert dx-alert--error">{{ session('error') }}</div>
        @endif

        <label class="tt-label" for="amount_usd">{{ __('Amount') }}</label>
        <div class="tt-amount @error('amount_usd') is-invalid @enderror">
            <span aria-hidden="true">$</span>
            <input type="number" id="amount_usd" name="amount_usd" inputmode="decimal" step="0.01" min="1" max="{{ $maxUsd }}"
                   value="{{ old('amount_usd', 25) }}" required data-tt-amount>
        </div>
        @error('amount_usd')<p class="tt-error">{{ $message }}</p>@enderror

        @if($presets)
            <div class="tt-presets" role="group" aria-label="{{ __('Quick amounts') }}">
                @foreach($presets as $p)
                    <button type="button" class="tt-preset" data-tt-preset="{{ $p }}">${{ $p }}</button>
                @endforeach
            </div>
        @endif

        <dl class="tt-summary">
            <div><dt>{{ __('You get') }}</dt><dd><span data-tt-echo>$25.00</span> {{ __('of :name', ['name' => $cryptocurrency->name]) }}</dd></div>
            <div class="is-total"><dt>{{ __('Total') }}</dt><dd data-tt-echo>$25.00</dd></div>
        </dl>

        <label class="tt-check">
            <input type="checkbox" required>
            <span>{{ __(':name can go down in value as well as up. I understand what I buy today may be worth less later.', ['name' => $cryptocurrency->name]) }}</span>
        </label>

        <button type="submit" class="dx-btn dx-btn--money dx-btn--block tt-submit">
            {{ __('Buy') }} <span data-tt-echo>$25.00</span>
        </button>

        <p class="tt-note">
            {{ __('Everything here is shown in dollars at today\'s value. You never need to work out coin amounts — we do that for you.') }}
        </p>
    </form>

</div>
</div>
@endsection

@section('scripts')
    <script src="{{ asset('js/pages/token-trade.js') }}?v=20260917a" defer></script>
@endsection
