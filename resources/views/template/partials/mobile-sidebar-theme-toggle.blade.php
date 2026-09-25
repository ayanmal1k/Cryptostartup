@php
    $isDarkMode = Cookie::get('app_theme') == 'dark'
        || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark');
    $themeToggleSub = $themeToggleSub ?? false;
@endphp

<div class="mobile-sidebar__theme-row{{ $themeToggleSub ? ' mobile-sidebar__theme-row--sub' : '' }}">
  <span class="mobile-sidebar__icon" aria-hidden="true">
    @include('template.partials.mobile-nav-icon', ['icon' => 'contrast'])
  </span>
  <span class="mobile-sidebar__theme-label">{{ __('Theme') }}</span>
  <button
    type="button"
    class="mobile-sidebar__theme-toggle dark-mode-switcher{{ $isDarkMode ? ' is-dark' : '' }}"
    aria-label="{{ $isDarkMode ? __('Switch to light mode') : __('Switch to dark mode') }}"
    aria-pressed="{{ $isDarkMode ? 'true' : 'false' }}"
  >
    <span class="mobile-sidebar__theme-track" aria-hidden="true">
      <span class="mobile-sidebar__theme-icon mobile-sidebar__theme-icon--sun">
        @include('template.partials.mobile-nav-icon', ['icon' => 'sun'])
      </span>
      <span class="mobile-sidebar__theme-icon mobile-sidebar__theme-icon--moon">
        @include('template.partials.mobile-nav-icon', ['icon' => 'moon'])
      </span>
      <span class="mobile-sidebar__theme-thumb"></span>
    </span>
  </button>
</div>
