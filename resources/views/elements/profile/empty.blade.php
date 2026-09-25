{{-- Empty tab on the profile. $title, $text, $cta (['label', 'href'] or null) --}}
<div class="pp-empty">
    <span class="pp-empty__icon" aria-hidden="true">
        <svg viewBox="0 0 24 24"><rect x="3" y="6" width="13" height="12" rx="2"/><path d="m16 10 5-3v10l-5-3"/></svg>
    </span>
    <h3>{{ $title }}</h3>
    <p>{{ $text }}</p>
    @if(!empty($cta))
        <a href="{{ $cta['href'] }}" class="pp-btn pp-btn--brand">{{ $cta['label'] }}</a>
    @endif
</div>
