@php
    $current = Route::currentRouteName();
    $tabs = [
        [
            'route' => 'voyager.dashboard',
            'label' => 'Overview',
            'icon' => 'voyager-dashboard',
            'accent' => 'purple',
        ],
        [
            'route' => 'voyager.dashboard.realtime-stats',
            'label' => 'Realtime Stats',
            'icon' => 'voyager-activity',
            'accent' => 'green',
        ],
        [
            'route' => 'voyager.dashboard.chart-data',
            'label' => 'Chart Data',
            'icon' => 'voyager-bar-chart',
            'accent' => 'blue',
        ],
        [
            'route' => 'voyager.dashboard.top-performers',
            'label' => 'Top Performers',
            'icon' => 'voyager-trophy',
            'accent' => 'amber',
        ],
        [
            'route' => 'voyager.dashboard.system-health',
            'label' => 'System Health',
            'icon' => 'voyager-tools',
            'accent' => 'rose',
        ],
    ];
@endphp

<nav class="jf-dash-nav" aria-label="Dashboard sections">
    @foreach ($tabs as $tab)
        <a href="{{ route($tab['route']) }}"
           class="jf-dash-nav__item jf-dash-nav__item--{{ $tab['accent'] }} {{ $current === $tab['route'] ? 'is-active' : '' }}">
            <i class="{{ $tab['icon'] }}"></i>
            <span class="jf-pill-label">{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
