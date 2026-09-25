@php
    $type = $alert['type'] ?? 'info';
    $icons = [
        'warning' => 'voyager-warning',
        'info' => 'voyager-info-circled',
        'danger' => 'voyager-exclamation',
        'success' => 'voyager-check',
    ];
    $icon = $icons[$type] ?? 'voyager-bell';
    if (str_contains(strtolower($alert['title'] ?? ''), 'wallet')) {
        $icon = 'voyager-wallet';
    } elseif (str_contains(strtolower($alert['title'] ?? ''), 'token')) {
        $icon = 'voyager-trophy';
    } elseif (str_contains(strtolower($alert['title'] ?? ''), 'distribution')) {
        $icon = 'voyager-dollar';
    }
@endphp
<div class="alert alert-{{ $type }} alert-dismissible platform-alert-item platform-alert-item--{{ $type }}" role="alert">
    <div class="platform-alert-item__icon" aria-hidden="true">
        <i class="{{ $icon }}"></i>
    </div>
    <div class="platform-alert-item__content">
        <strong class="platform-alert-item__title">{{ $alert['title'] }}</strong>
        <p class="platform-alert-item__message">{{ $alert['message'] }}</p>
    </div>
    @if(!empty($alert['action_url']))
        <a href="{{ $alert['action_url'] }}" class="platform-alert-item__action">
            {{ $alert['action_text'] }}
        </a>
    @endif
    <button type="button" class="close platform-alert-item__close" data-dismiss="alert" aria-label="Dismiss">&times;</button>
</div>
