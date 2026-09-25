<div class="conversation-header d-none">
    <div class="details-holder">
        <div class="d-flex align-items-center">
            <button type="button" class="conversation-header-back d-md-none" id="messenger-conversation-back" aria-label="{{ __('Back') }}">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m15 6-6 6 6 6"/>
                </svg>
            </button>
            <div class="d-flex align-items-center flex-grow-1 min-w-0">
                <img class="conversation-header-avatar" src="{{asset('/img/no-avatar.png')}}" alt="Avatar" />
                <div class="conversation-header-user text-truncate ml-2 ml-md-3">
                </div>
            </div>
            <div class="d-flex align-items-center flex-shrink-0">
                <div class="dropdown {{GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft'}}">
                    <a class="btn btn-sm btn-outline-primary dropdown-toggle px-2 py-1 d-flex align-items-center justify-content-center" data-toggle="dropdown" href="#" role="button" aria-haspopup="true" aria-expanded="false" style="width: 36px; height: 36px;">
                        @include('elements.icon',['icon'=>'ellipsis-horizontal-outline'])
                    </a>
                    <div class="dropdown-menu dropdown-menu-right">
                        <!-- Dropdown menu links -->
                        <a class="dropdown-item d-flex align-items-center tip-btn"
                           data-toggle="modal"
                           data-target="#checkout-center"
                           data-type="chat-tip"
                           data-first-name="{{Auth::user()->first_name}}"
                           data-last-name="{{Auth::user()->last_name}}"
                           data-billing-address="{{Auth::user()->billing_address}}"
                           data-country="{{Auth::user()->country}}"
                           data-city="{{Auth::user()->city}}"
                           data-state="{{Auth::user()->state}}"
                           data-postcode="{{Auth::user()->postcode}}"
                           data-available-credit="{{Auth::user()->wallet ? Auth::user()->wallet->total : 0}}"
                        >{{__('Send a tip')}}</a>
                        <a class="dropdown-item d-flex align-items-center conversation-profile-link" href="#" target="_blank">{{__('Go to profile')}}</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item unfollow-btn" href="javascript:void(0);">{{__('Unfollow')}}</a>
                        <a class="dropdown-item block-btn" href="javascript:void(0);">{{__('Block')}}</a>
                        <a class="dropdown-item report-btn" href="javascript:void(0);">{{__('Report')}}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
