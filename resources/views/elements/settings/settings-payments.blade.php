<div class="payments-page">
@if(count($payments))
    <div class="payments-table">
        <div class="payments-table__head d-none d-md-flex">
            <div class="payments-table__cell payments-table__cell--type">{{ __('Type') }}</div>
            <div class="payments-table__cell payments-table__cell--status">{{ __('Status') }}</div>
            <div class="payments-table__cell payments-table__cell--amount">{{ __('Amount') }}</div>
            <div class="payments-table__cell payments-table__cell--from">{{ __('From') }}</div>
            <div class="payments-table__cell payments-table__cell--to">{{ __('To') }}</div>
            <div class="payments-table__cell payments-table__cell--actions"></div>
        </div>

        @foreach($payments as $payment)
            <div class="payments-table__row">
                <div class="payments-table__cell payments-table__cell--type">
                    <span class="payments-table__label d-md-none">{{ __('Type') }}</span>
                    <span class="payments-table__value">
                        @if($payment->type == 'stream-access')
                            @if($payment->stream->status == 'in-progress')
                                <a href="{{ route('public.stream.get', ['streamID' => $payment->stream->id, 'slug' => $payment->stream->slug]) }}" class="payments-table__link">{{ ucfirst(__($payment->type)) }}</a>
                            @else
                                @if($payment->stream->settings['dvr'] && $payment->stream->vod_link)
                                    <a href="{{ route('public.vod.get', ['streamID' => $payment->stream->id, 'slug' => $payment->stream->slug]) }}" class="payments-table__link">{{ ucfirst(__($payment->type)) }}</a>
                                @else
                                    <span data-toggle="tooltip" data-placement="top" title="{{ __('Stream VOD unavailable') }}">{{ ucfirst(__($payment->type)) }}</span>
                                @endif
                            @endif
                        @elseif($payment->type == 'post-unlock')
                            <a href="{{ route('posts.get', ['post_id' => $payment->post->id, 'username' => $payment->receiver->username]) }}" class="payments-table__link">{{ ucfirst(__($payment->type)) }}</a>
                        @elseif($payment->type == 'tip')
                            {{ ucfirst(__($payment->type)) }}
                            @if($payment->post_id)
                                (<a href="{{ route('posts.get', ['post_id' => $payment->post->id, 'username' => $payment->receiver->username]) }}" class="payments-table__link">{{ __('Post') }}</a>)
                            @elseif($payment->stream_id)
                                @if($payment->stream->status == 'in-progress')
                                    <a href="{{ route('public.stream.get', ['streamID' => $payment->stream->id, 'slug' => $payment->stream->slug]) }}" class="payments-table__link"> ({{ __('Stream') }})</a>
                                @else
                                    @if($payment->stream->settings['dvr'] && $payment->stream->vod_link)
                                        <a href="{{ route('public.vod.get', ['streamID' => $payment->stream->id, 'slug' => $payment->stream->slug]) }}" class="payments-table__link"> ({{ __('Stream') }})</a>
                                    @else
                                        <span data-toggle="tooltip" data-placement="top" title="{{ __('Stream VOD unavailable') }}">({{ __('Stream') }})</span>
                                    @endif
                                @endif
                            @else
                                ({{ __('User') }})
                            @endif
                        @else
                            {{ ucfirst(__($payment->type)) }}
                        @endif
                    </span>
                </div>

                <div class="payments-table__cell payments-table__cell--status">
                    <span class="payments-table__label d-md-none">{{ __('Status') }}</span>
                    <span class="payments-table__value">
                        @switch($payment->status)
                            @case('approved')
                                <span class="payments-badge payments-badge--success">{{ ucfirst(__($payment->status)) }}</span>
                                @break
                            @case('initiated')
                            @case('pending')
                                <span class="payments-badge payments-badge--info">{{ ucfirst(__($payment->status)) }}</span>
                                @break
                            @case('canceled')
                            @case('refunded')
                                <span class="payments-badge payments-badge--warning">{{ ucfirst(__($payment->status)) }}</span>
                                @break
                            @case('partially-paid')
                                <span class="payments-badge payments-badge--primary">{{ ucfirst(__($payment->status)) }}</span>
                                @break
                            @case('declined')
                                <span class="payments-badge payments-badge--danger">{{ ucfirst(__($payment->status)) }}</span>
                                @break
                        @endswitch
                    </span>
                </div>

                <div class="payments-table__cell payments-table__cell--amount">
                    <span class="payments-table__label d-md-none">{{ __('Amount') }}</span>
                    <span class="payments-table__value payments-table__amount">
                        {{ $payment->decodedTaxes && Auth::user()->id == $payment->recipient_user_id ? \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($payment->amount - $payment->decodedTaxes->taxesTotalAmount) : \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($payment->amount) }}
                    </span>
                </div>

                <div class="payments-table__cell payments-table__cell--from d-none d-md-block">
                    <a href="{{ route('profile', ['username' => $payment->sender->username]) }}" class="payments-table__link">
                        {{ $payment->sender->name }}
                    </a>
                </div>

                <div class="payments-table__cell payments-table__cell--to d-none d-md-block">
                    <a href="{{ route('profile', ['username' => $payment->receiver->username]) }}" class="payments-table__link">
                        {{ $payment->receiver->name }}
                    </a>
                </div>

                <div class="payments-table__cell payments-table__cell--actions">
                    @if($payment->invoice_id && $payment->receiver->id !== \Illuminate\Support\Facades\Auth::user()->id && $payment->status === \App\Model\Transaction::APPROVED_STATUS)
                        <div class="dropdown {{ GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft' }}">
                            <button type="button" class="payments-table__menu-btn dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" aria-label="{{ __('More') }}">
                                @include('elements.icon', ['icon' => 'ellipsis-horizontal-outline', 'variant' => 'small', 'centered' => true])
                            </button>
                            <div class="dropdown-menu dropdown-menu-right">
                                <a class="dropdown-item d-flex align-items-center" href="{{ route('invoices.get', ['id' => $payment->invoice_id]) }}">
                                    @include('elements.icon', ['icon' => 'document-outline', 'centered' => false, 'classes' => 'mr-2'])
                                    {{ __('View invoice') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    <div class="payments-page__pagination">
        {{ $payments->onEachSide(1)->links() }}
    </div>
@else
    <div class="payments-empty">
        <div class="payments-empty__icon" aria-hidden="true">
            @include('elements.icon', ['icon' => 'card-outline', 'variant' => 'medium', 'centered' => true, 'classes' => 'payments-icon--empty'])
        </div>
        <h2 class="payments-empty__title">{{ __('No payments yet') }}</h2>
        <p class="payments-empty__text">{{ __('There are no payments on this account.') }}</p>
        <a href="{{ route('my.settings', ['type' => 'wallet']) }}" class="payments-empty__cta">
            @include('elements.icon', ['icon' => 'wallet-outline', 'variant' => 'small', 'centered' => true, 'classes' => 'payments-icon'])
            <span>{{ __('Go to wallet') }}</span>
        </a>
    </div>
@endif
</div>
