@php
    $icons = [
        'warning' => 'voyager-warning',
        'success' => 'voyager-check',
        'danger'  => 'voyager-warning',
        'info'    => 'voyager-info-circled',
        'default' => 'voyager-info-circled',
    ];
@endphp

<div class="alerts jf-voyager-alerts">
    @foreach ($alerts as $alert)
        <div class="jf-glass-alert jf-glass-alert--{{ $alert->type }} alert-name-{{ $alert->name }}" role="alert">
            <div class="jf-glass-alert__icon" aria-hidden="true">
                <i class="{{ $icons[$alert->type] ?? $icons['default'] }}"></i>
            </div>
            <div class="jf-glass-alert__content">
                @foreach ($alert->components as $component)
                    {!! $component->render() !!}
                @endforeach
            </div>
        </div>
    @endforeach
</div>
