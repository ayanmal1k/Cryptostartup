@extends('layouts.generic')

@section('page_title', __('Cache cleared'))

@section('content')
@php
    $failed = collect($steps)->filter(fn ($s) => !$s['ok']);
@endphp

<div class="cc-wrap">
    <div class="cc-card">
        <div class="cc-head">
            <span class="cc-badge {{ $failed->isEmpty() ? 'is-ok' : 'is-warn' }}">
                {{ $failed->isEmpty() ? '✓' : '!' }}
            </span>
            <div>
                <h1 class="cc-title">
                    {{ $failed->isEmpty() ? __('Cache cleared') : __('Cleared, with problems') }}
                </h1>
                <p class="cc-sub">
                    @if($failed->isEmpty())
                        {{ __('Everything below was flushed. Reload the page you were testing.') }}
                    @else
                        {{ trans_choice(':count step did not complete.|:count steps did not complete.', $failed->count(), ['count' => $failed->count()]) }}
                    @endif
                </p>
            </div>
        </div>

        <ul class="cc-list">
            @foreach($steps as $step)
                <li class="cc-step {{ $step['ok'] ? '' : 'is-failed' }}">
                    <span class="cc-step__mark">{{ $step['ok'] ? '✓' : '✕' }}</span>
                    <span class="cc-step__body">
                        <span class="cc-step__label">{{ $step['label'] }}</span>
                        <code class="cc-step__cmd">{{ $step['command'] }}</code>
                        @if(!empty($step['detail']))
                            <span class="cc-step__detail">{{ $step['detail'] }}</span>
                        @endif
                    </span>
                </li>
            @endforeach
        </ul>

        <p class="cc-note">
            {{ __('Sessions are left alone on purpose — clearing them would sign out every logged-in user.') }}
        </p>

        <div class="cc-actions">
            <a href="{{ url('/') }}" class="cc-btn cc-btn--primary">{{ __('Back to the site') }}</a>
            <a href="{{ url('/c') }}" class="cc-btn">{{ __('Clear again') }}</a>
        </div>
    </div>
</div>

<style>
    .cc-wrap {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 32px 16px;
    }

    .cc-card {
        width: 100%;
        max-width: 620px;
        padding: 26px;
        border-radius: 18px;
        /* Uses the sheet tokens added with the theme hook, so this page is readable
           in light mode instead of being another hardcoded-dark screen. */
        background: var(--sheet-surface, #16181f);
        color: var(--sheet-text, #f2f4fa);
        border: 1px solid var(--sheet-border, rgba(255,255,255,.10));
        box-shadow: 0 16px 44px rgba(0, 0, 0, .18);
    }

    .cc-head {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        margin-bottom: 20px;
    }

    .cc-badge {
        flex: 0 0 auto;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: grid;
        place-items: center;
        font-size: 19px;
        font-weight: 800;
        color: #fff;
    }

    .cc-badge.is-ok   { background: #2eb872; }
    .cc-badge.is-warn { background: #e0a42e; }

    .cc-title { margin: 0; font-size: 21px; font-weight: 700; letter-spacing: -.01em; }
    .cc-sub   { margin: 4px 0 0; font-size: 14px; color: var(--sheet-text-dim, #a2a9bd); }

    .cc-list { list-style: none; margin: 0; padding: 0; }

    .cc-step {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 11px 0;
        border-top: 1px solid var(--sheet-border-soft, rgba(255,255,255,.06));
    }

    .cc-step__mark {
        flex: 0 0 auto;
        width: 20px;
        text-align: center;
        font-weight: 800;
        color: #2eb872;
    }

    .cc-step.is-failed .cc-step__mark { color: #e0245e; }

    .cc-step__body { display: flex; flex-direction: column; gap: 2px; min-width: 0; }
    .cc-step__label { font-size: 14px; font-weight: 650; }

    .cc-step__cmd {
        font-size: 12px;
        color: var(--sheet-text-faint, #767d92);
        background: transparent;
        padding: 0;
    }

    .cc-step__detail {
        font-size: 12.5px;
        color: var(--sheet-text-dim, #a2a9bd);
        overflow-wrap: anywhere;
    }

    .cc-note {
        margin: 18px 0 0;
        padding: 11px 13px;
        border-radius: 10px;
        font-size: 12.5px;
        color: var(--sheet-text-dim, #a2a9bd);
        background: var(--sheet-raised, rgba(255,255,255,.04));
    }

    .cc-actions { display: flex; gap: 10px; margin-top: 18px; flex-wrap: wrap; }

    .cc-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 44px;
        padding: 0 18px;
        border-radius: 12px;
        font-size: 14px;
        font-weight: 650;
        text-decoration: none;
        background: var(--sheet-raised, rgba(255,255,255,.06));
        color: var(--sheet-text, #f2f4fa);
        border: 1px solid var(--sheet-border, rgba(255,255,255,.10));
    }

    .cc-btn:hover { text-decoration: none; color: var(--sheet-text, #f2f4fa); }

    .cc-btn--primary {
        background: var(--sheet-accent, #ff4d9d);
        border-color: transparent;
        color: #fff;
    }

    .cc-btn--primary:hover { color: #fff; }
</style>
@stop
