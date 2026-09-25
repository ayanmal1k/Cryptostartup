{{-- Said plainly until a video service is connected (StreamController docblock). --}}
<div class="lv-note">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
    <span>
        <strong>{{ __('Live video is being switched on.') }}</strong>
        {{ $slot ?? __('You can set up, go live and chat with viewers now. Your camera is shown to you only until video streaming is connected.') }}
    </span>
</div>
