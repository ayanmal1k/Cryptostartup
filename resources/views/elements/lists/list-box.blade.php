<div class="lists-card list-item">
    <a href="{{ route('my.lists.show', ['list_id' => $list->type !== \App\Model\UserList::FOLLOWERS_TYPE ? $list->id : 'followers']) }}" class="lists-card__link list-link">
        <div class="lists-card__content">
            <h3 class="lists-card__title">{{ __($list->name) }}</h3>
            <p class="lists-card__meta">{{ trans_choice('people', count($list->members), ['number' => count($list->members)]) }} · {{ trans_choice('posts', $list->posts_count, ['number' => $list->posts_count]) }}</p>
        </div>
        @if(count($list->members))
            <div class="lists-card__avatars list-box-avatars-wrapper">
                @foreach($list->members->reverse()->slice(0, 3) as $member)
                    <img src="{{ $member->avatar }}" class="rounded-circle user-avatar lists-card__avatar" alt="">
                @endforeach
            </div>
        @endif
    </a>
</div>
