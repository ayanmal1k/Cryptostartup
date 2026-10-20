@php
    $accent = $accent ?? '#7928ca';
    $icon = $icon ?? '';
    $label = $label ?? '';
    $value = $value ?? '';
    $footer = $footer ?? '';
    $compact = $compact ?? false;
    $variants = [
        '#4f8cff' => 'blue',
        '#22c55e' => 'green',
        '#f59e0b' => 'amber',
        '#f472b6' => 'rose',
    ];
    $variant = $variant ?? ($variants[$accent] ?? 'purple');
@endphp
<div class="jf-stat-card jf-stat-card--{{ $variant }}{{ !empty($compact) ? ' jf-stat-card--compact' : '' }}">
    <div class="jf-stat-card__icon">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="jf-stat-card__body">
        <span class="jf-stat-card__label">{{ $label }}</span>
        <div class="jf-stat-card__value" @if(!empty($valueId)) id="{{ $valueId }}" @endif>{{ $value }}</div>
        @if($footer !== '')
            <span class="jf-stat-card__footer">{!! $footer !!}</span>
        @endif
    </div>
</div>
