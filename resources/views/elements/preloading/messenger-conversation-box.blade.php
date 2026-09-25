<div class="messenger-skeleton-chat conversation-loading-box d-none" aria-hidden="true">
  @for($i = 0; $i < 4; $i++)
    <div class="messenger-skeleton-chat__row {{ $i % 2 === 0 ? 'messenger-skeleton-chat__row--left' : 'messenger-skeleton-chat__row--right' }}">
      <div class="messenger-skeleton-chat__bubble"></div>
    </div>
  @endfor
</div>
