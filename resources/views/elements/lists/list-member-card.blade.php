<div class="lists-members__item" data-memberuserid="{{ $profile->id }}">
    <div class="lists-member-card">
        <a href="{{ route('profile', ['username' => $profile->username]) }}" class="lists-member-card__main">
            <img src="{{ $profile->avatar }}" class="lists-member-card__avatar" alt="">
            <div class="lists-member-card__content">
                <div class="lists-member-card__name">
                    {{ $profile->name }}
                    @if($profile->email_verified_at && $profile->birthdate && ($profile->verification && $profile->verification->status == 'verified'))
                        <span class="lists-member-card__verified" data-toggle="tooltip" data-placement="top" title="{{ __('Verified user') }}">
                            @include('elements.icon',['icon'=>'checkmark-circle-outline','centered'=>true,'classes'=>'ls-icon ls-icon--verified'])
                        </span>
                    @endif
                </div>
                <div class="lists-member-card__username">{{ '@' . $profile->username }}</div>
            </div>
        </a>
        @if(isset($isListMode) && $isListManageable)
            <button type="button"
                    class="lists-member-card__remove"
                    data-toggle="tooltip"
                    data-placement="left"
                    title="{{ __('Remove') }}"
                    aria-label="{{ __('Remove') }}"
                    onclick="Lists.showListMemberRemoveModal({{ $profile->id }})">
                @include('elements.icon',['icon'=>'trash-outline','centered'=>true,'variant'=>'small','classes'=>'ls-icon ls-icon--remove'])
            </button>
        @endif
    </div>
</div>
