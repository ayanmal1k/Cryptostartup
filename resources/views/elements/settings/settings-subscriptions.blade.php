
<div class="subs-page">
    <div class="subs-page__tabs-drawer">
        <nav class="subs-page__nav" aria-label="{{ __('Subscriptions') }}">
            @foreach(['subscriptions', 'subscribers'] as $tab)
                <a class="subs-page__nav-link {{ $activeSubsTab == $tab ? 'active' : '' }}" href="{{ route('my.settings', ['type' => 'subscriptions', 'active' => $tab]) }}">
                    @if($tab == 'subscriptions')
                        @include('elements.icon',['icon'=>'people-outline','centered'=>true,'variant'=>'small','classes'=>'subs-icon'])
                    @else
                        @include('elements.icon',['icon'=>'person-add-outline','centered'=>true,'variant'=>'small','classes'=>'subs-icon'])
                    @endif
                    <span class="subs-page__nav-label">{{ ucfirst(__($tab)) }}</span>
                </a>
            @endforeach
        </nav>
    </div>

    @if(count($subscriptions))
        @include('elements/message-alert', ['classes' => 'subs-page__alert'])
        <div class="subs-page__list">
            @foreach($subscriptions as $subscription)
                @php
                    $profileUser = $activeSubsTab == 'subscriptions' ? $subscription->creator : $subscription->subscriber;
                @endphp
                <article class="subs-card">
                    <div class="subs-card__main">
                        <a href="{{ route('profile', ['username' => $profileUser->username]) }}" class="subs-card__profile">
                            <img src="{{ $profileUser->avatar }}" class="subs-card__avatar rounded-circle" alt="{{ $profileUser->name }}">
                            <div class="subs-card__identity">
                                <span class="subs-card__name">{{ $profileUser->name }}</span>
                                <span class="subs-card__role">{{ $activeSubsTab == 'subscriptions' ? __('To') : __('From') }}</span>
                            </div>
                        </a>
                        <div class="subs-card__aside">
                            <div class="subs-card__status">
                                @switch($subscription->status)
                                    @case('pending')
                                    @case('update-needed')
                                    @case('canceled')
                                        <span class="subs-badge subs-badge--warning">{{ ucfirst(__($subscription->status)) }}</span>
                                        @break
                                    @case('completed')
                                        <span class="subs-badge subs-badge--success">{{ ucfirst(__($subscription->status)) }}</span>
                                        @break
                                    @case('suspended')
                                    @case('expired')
                                    @case('failed')
                                        <span class="subs-badge subs-badge--danger">{{ ucfirst(__($subscription->status)) }}</span>
                                        @break
                                @endswitch
                            </div>
                            @if($subscription->status === \App\Model\Subscription::ACTIVE_STATUS)
                                <div class="subs-card__actions">
                                    <div class="dropdown {{ GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft' }}">
                                        <button type="button" class="subs-card__menu-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('More') }}">
                                            @include('elements.icon',['icon'=>'ellipsis-horizontal-outline','centered'=>true,'variant'=>'small','classes'=>'subs-icon'])
                                        </button>
                                        <div class="dropdown-menu">
                                            @if($subscription->status === \App\Model\Subscription::ACTIVE_STATUS && ($subscription->provider !== 'ccbill' || \App\Providers\SettingsServiceProvider::providedCCBillSubscriptionCancellingCredentials()))
                                                <a class="dropdown-item d-flex align-items-center" href="javascript:void(0)" onclick="SubscriptionsSettings.confirmSubCancelation({{ $subscription->id }},{{ $activeSubsTab == 'subscriptions' ? '"subscriptions"' : '"subscribers"' }})">
                                                    @include('elements.icon',['icon'=>'trash-outline','centered'=>false,'classes'=>'mr-2'])
                                                    {{ __('Cancel subscription') }}
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="subs-card__meta">
                        <div class="subs-card__meta-item d-none d-md-block">
                            <span class="subs-card__meta-label">{{ __('Paid with') }}</span>
                            <span class="subs-card__meta-value">{{ ucfirst($subscription->provider) }}</span>
                        </div>
                        <div class="subs-card__meta-item">
                            <span class="subs-card__meta-label">{{ __('Renews') }}</span>
                            <span class="subs-card__meta-value">{{ isset($subscription->expires_at) ? ($subscription->status == \App\Model\Subscription::CANCELED_STATUS ? '-' : $subscription->expires_at->format('M d Y')) : '-' }}</span>
                        </div>
                        <div class="subs-card__meta-item d-none d-md-block">
                            <span class="subs-card__meta-label">{{ __('Expires at') }}</span>
                            <span class="subs-card__meta-value">{{ isset($subscription->expires_at) ? ($subscription->status == \App\Model\Subscription::ACTIVE_STATUS ? '-' : $subscription->expires_at->format('M d Y')) : '-' }}</span>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        <div class="subs-page__pagination">
            {{ $subscriptions->withQueryString()->onEachSide(1)->links() }}
        </div>
    @else
        <div class="subs-empty">
            @include('elements.settings.subscriptions-empty-hero')
        </div>
    @endif
</div>

@include('elements.settings.transaction-cancel-dialog')
