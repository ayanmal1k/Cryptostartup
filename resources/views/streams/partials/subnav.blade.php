{{-- Tab bar for the livestream pages. $active: 'live' | 'mine' | null --}}
@include('elements.discover.subnav', [
    'tabs' => [
        ['label' => __('Live now'), 'href' => route('streams.index'), 'active' => ($active ?? null) === 'live', 'count' => $liveCount ?? null],
        ['label' => __('My streams'), 'href' => route('creator.streams'), 'active' => ($active ?? null) === 'mine'],
    ],
    'cta' => ['label' => __('Go live'), 'href' => route('streams.create')],
])
