@php
    $notifBadgeIcon = 'notifications-outline';
    $notifBadgeKind = 'default';
    switch ($notification->type) {
        case \App\Model\Notification::NEW_TIP:
            $notifBadgeIcon = 'cash'; $notifBadgeKind = 'tip'; break;
        case \App\Model\Notification::NEW_REACTION:
            $notifBadgeIcon = 'heart'; $notifBadgeKind = 'like'; break;
        case \App\Model\Notification::NEW_COMMENT:
            $notifBadgeIcon = 'chatbubble-ellipses'; $notifBadgeKind = 'comment'; break;
        case \App\Model\Notification::NEW_SUBSCRIPTION:
            $notifBadgeIcon = 'person-add'; $notifBadgeKind = 'subscription'; break;
        case \App\Model\Notification::WITHDRAWAL_ACTION:
            $notifBadgeIcon = 'wallet'; $notifBadgeKind = 'tip'; break;
        case \App\Model\Notification::NEW_MESSAGE:
            $notifBadgeIcon = 'paper-plane'; $notifBadgeKind = 'message'; break;
        case \App\Model\Notification::EXPIRING_STREAM:
            $notifBadgeIcon = 'radio'; $notifBadgeKind = 'live'; break;
        case \App\Model\Notification::PPV_UNLOCK:
            $notifBadgeIcon = 'lock-open'; $notifBadgeKind = 'tip'; break;
    }
@endphp
<div class="notifications-card notification-box {{ !$notification->read ? 'notifications-card--unread unread' : '' }}" data-notification-read="{{ $notification->read ? '1' : '0' }}">
    <div class="notifications-card__body d-flex flex-row-no-rtl">
        <div class="notifications-card__avatar-wrap">
            @if($notification->fromUser)
                <img class="rounded-circle avatar notifications-card__avatar" src="{{$notification->fromUser->avatar}}" alt="{{$notification->fromUser->username}}">
            @else
                <img class="rounded-circle avatar notifications-card__avatar" src="{{\App\Providers\GenericHelperServiceProvider::getStorageAvatarPath(null)}}" alt="Avatar">
            @endif
            <span class="notifications-card__type-badge notifications-card__type-badge--{{ $notifBadgeKind }}" aria-hidden="true">
                @include('elements.icon',['icon'=>$notifBadgeIcon])
            </span>
        </div>
        <div class="notifications-card__content">
            <div class="d-flex flex-row-no-rtl justify-content-between">
                @if($notification->fromUser)
                    <div class="d-flex flex-column">
                        <h6 class="text-bold  m-0 p-0 d-flex"><a href="{{route('profile',['username'=>$notification->fromUser->username])}}" class="text-dark-r">{{$notification->fromUser->name}}</a></h6>
                        <div class="text-bold"><a href="{{route('profile',['username'=>$notification->fromUser->username])}}" class="text-muted">{{'@'}}{{$notification->fromUser->username}}</a></div>
                    </div>
                @endif
                <div class="position-absolute separator">
                </div>
            </div>
            <div>
                <div class="my-1 text-break {{!$notification->read?'text-bold':''}}">
                    @switch($notification->type)
                        @case(\App\Model\Notification::NEW_TIP)
                            @if(isset($notification->transaction))
                                {{$notification->transaction->sender->name}} {{__("sent you a tip of")}} {{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount(\App\Providers\PaymentsServiceProvider::getTransactionAmountWithTaxesDeducted($notification->transaction))}}.
                            @else
                                {{__('No transaction data')}}
                            @endif
                        @break
                        @case(\App\Model\Notification::NEW_REACTION)
                        @if($notification->post_id)
                            {{__(":name liked your",['name'=>$notification->fromUser->name])}} <a href="{{route('posts.get', ['username' => $notification->post->user->username, 'post_id' => $notification->post->id])}}" target="_blank">{{__('post')}}</a>
                        @endif
                        @if($notification->post_comment_id)
                            {{__(":name liked your comment",['name'=>$notification->postComment->author->name])}}
                        @endif
                        @break
                        @case(\App\Model\Notification::NEW_COMMENT)
                        {{__(':name added a new comment on your',['name'=>$notification->fromUser->name])}} <a href="{{route('posts.get', ['username' => $notification->postComment->post->user->username, 'post_id' => $notification->postComment->post->id])}}" target="_blank">{{__('post')}}</a>
                        @break
                        @case(\App\Model\Notification::NEW_SUBSCRIPTION)
                        {{__("A new user subscribed to your profile")}}
                        @break
                        @case(\App\Model\Notification::WITHDRAWAL_ACTION)
                        {{
                            __(\App\Providers\SettingsServiceProvider::leftAlignedCurrencyPosition() ? 'Withdrawal processed' : 'Withdrawal processed rightAligned',[
                                            'currencySymbol' => \App\Providers\SettingsServiceProvider::getWebsiteCurrencySymbol(),
                                            'amount' => $notification->withdrawal->amount,
                                            'status' =>  $notification->withdrawal->status,
                                        ])

                        }}
                        @break
                        @case(\App\Model\Notification::NEW_MESSAGE)
                        {{__("Send you a message: `:message`",['message'=>$notification->userMessage->message])}}
                        @break
                        @case(\App\Model\Notification::EXPIRING_STREAM)
                        {{__('Your live streaming is about to end in 30 minutes. You can start another one afterwards.')}}
                        @break
                        @case(\App\Model\Notification::PPV_UNLOCK)
                        {{__('Someone unlocked your'). ' ' . $notification->PPVUnlockType . '.'}}
                        @break
                    @endswitch

                </div>
                <div class="d-flex text-muted">
                    <div>{{ \Carbon\Carbon::parse($notification->created_at)->diffForhumans() }} </div>
                </div>
            </div>
        </div>
    </div>
</div>
