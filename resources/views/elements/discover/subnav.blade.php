@php
    /**
     * Sticky tab bar for the discover pages.
     *
     * $tabs         - list of ['label', 'href', 'active' => bool, 'count' => ?int]
     * $searchAction - form action URL
     * $search       - current search string
     * $searchHidden - [name => value] kept when searching
     * $searchLabel  - placeholder text
     * $cta          - ['label', 'href' => ?string, 'onclick' => ?string]
     */
    $searchHidden = $searchHidden ?? [];
    $search = $search ?? '';
    $searchAction = $searchAction ?? null;
    $searchLabel = $searchLabel ?? '';
    $searching = $search !== '';
@endphp
<nav class="dx-subnav {{ $searching ? 'is-searching' : '' }}" data-dx-subnav>
    <div class="dx-subnav__bar">
        <div class="dx-tabs">
            @foreach($tabs as $t)
                <a href="{{ $t['href'] }}" class="dx-tab {{ $t['active'] ? 'is-active' : '' }}"
                   @if($t['active']) aria-current="page" @endif>
                    {{ $t['label'] }}
                    @if(!empty($t['count']))
                        <span class="dx-tab__count">{{ $t['count'] > 99 ? '99+' : $t['count'] }}</span>
                    @endif
                </a>
            @endforeach
        </div>

        @if(!empty($searchAction))
        <button type="button" class="dx-icon-btn" data-dx-search-toggle
                aria-expanded="{{ $searching ? 'true' : 'false' }}" aria-label="{{ __('Search') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
            </svg>
        </button>
        @endif

        @if(!empty($cta))
            @php($plus = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>')
            @if(!empty($cta['onclick']))
                <button type="button" class="dx-btn dx-btn--primary" onclick="{{ $cta['onclick'] }}">{!! $plus !!}{{ $cta['label'] }}</button>
            @else
                <a href="{{ $cta['href'] }}" class="dx-btn dx-btn--primary">{!! $plus !!}{{ $cta['label'] }}</a>
            @endif
        @endif
    </div>

    @if(!empty($searchAction))
    <form class="dx-search" action="{{ $searchAction }}" method="GET" role="search">
        @foreach($searchHidden as $name => $value)
            <input type="hidden" name="{{ $name }}" value="{{ $value }}">
        @endforeach
        <div class="dx-search__field">
            <span class="dx-search__icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>
                </svg>
            </span>
            <input type="search" name="search" class="dx-search__input" value="{{ $search }}"
                   placeholder="{{ $searchLabel }}" aria-label="{{ $searchLabel }}"
                   autocomplete="off" enterkeyhint="search" data-dx-search-input>
            @if($searching)
                <a href="{{ $searchAction }}{{ $searchHidden ? '?' . http_build_query($searchHidden) : '' }}"
                   class="dx-icon-btn dx-search__clear" aria-label="{{ __('Clear search') }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                        <path d="M18 6 6 18M6 6l12 12"/>
                    </svg>
                </a>
            @endif
        </div>
    </form>
    @endif
</nav>
