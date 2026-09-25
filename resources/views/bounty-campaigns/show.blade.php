@extends('layouts.user-no-nav')

@section('page_title', $campaign->target_name)

@section('content')
<div class="bounty-show">
    <div class="container py-4">
        @include('elements.message-alert')

        <a href="{{ route('bounty-campaigns.marketplace') }}" class="bounty-back"><i class="fas fa-arrow-left"></i> {{ __('All campaigns') }}</a>

        {{-- Campaign detail --}}
        <div class="bounty-card">
            <div class="bounty-detail-head">
                <div class="bounty-avatar lg">{{ strtoupper(substr($campaign->target_name, 0, 1)) }}</div>
                <div>
                    <h2 class="bounty-detail-name">{{ $campaign->target_name }}</h2>
                    @if($campaign->target_handle)<div class="text-muted">{{ $campaign->target_handle }}</div>@endif
                    <span class="bounty-pill bounty-pill-{{ $campaign->status }}">{{ ucfirst(str_replace('_', ' ', $campaign->status)) }}</span>
                </div>
            </div>
            @if($campaign->target_description)<p class="mt-3 mb-0">{{ $campaign->target_description }}</p>@endif

            <div class="bounty-progress mt-4"><div class="bounty-progress-bar" style="width: {{ $campaign->progress_percentage }}%"></div></div>
            <div class="bounty-amounts mt-2">
                <strong>${{ number_format($campaign->current_amount, 0) }}</strong>
                <span>{{ __('of') }} ${{ number_format($campaign->goal_amount, 0) }} ({{ $campaign->progress_percentage }}%)</span>
            </div>
            @if($campaign->deadline)
                <div class="text-muted mt-2"><i class="far fa-clock"></i> {{ __('Deadline') }}: {{ $campaign->deadline->format('M d, Y') }}</div>
            @endif
        </div>

        @auth
            @if($campaign->isOpenForContributions())
                {{-- Contribute --}}
                <div class="bounty-card">
                    <h5>{{ __('Contribute (held in escrow)') }}</h5>
                    <form action="{{ route('bounty-campaigns.contribute', $campaign->id) }}" method="POST">
                        @csrf
                        <div class="form-group"><input type="number" name="amount" class="form-control" min="1" step="1" placeholder="{{ __('Amount ($)') }}" required></div>
                        <div class="form-group"><input type="text" name="message" class="form-control" placeholder="{{ __('Message (optional)') }}"></div>
                        <button class="btn btn-bounty-primary btn-block"><i class="fas fa-hand-holding-heart"></i> {{ __('Contribute') }}</button>
                    </form>
                    <small class="text-muted">{{ __('Your funds are held safely and refunded if the campaign is cancelled or expires.') }}</small>
                </div>

                {{-- Claim --}}
                <div class="bounty-card">
                    <h5>{{ __('Are you') }} {{ $campaign->target_name }}?</h5>
                    <p class="text-muted">{{ __('If this is you, submit a claim. A moderator will verify your identity before releasing the funds.') }}</p>
                    <form action="{{ route('bounty-campaigns.claim', $campaign->id) }}" method="POST">
                        @csrf
                        <div class="form-group"><textarea name="claim_message" class="form-control" rows="2" placeholder="{{ __('Tell the moderators how to verify you (optional)') }}"></textarea></div>
                        <button class="btn btn-outline-bounty btn-block">{{ __('Claim this campaign') }}</button>
                    </form>
                </div>
            @elseif($campaign->claim_status == 'pending')
                <div class="bounty-card text-center"><i class="fas fa-hourglass-half" style="color:#830866;font-size:2rem;"></i><h5 class="mt-2">{{ __('Claim under review') }}</h5><p class="text-muted mb-0">{{ __('A moderator is verifying the claim.') }}</p></div>
            @elseif($campaign->status == 'released')
                <div class="bounty-card text-center"><i class="fas fa-check-circle text-success" style="font-size:2rem;"></i><h5 class="mt-2">{{ __('Completed') }}</h5><p class="text-muted mb-0">{{ __('Funds were released to the claimer.') }}</p></div>
            @elseif($campaign->status == 'refunded')
                <div class="bounty-card text-center"><i class="fas fa-undo" style="font-size:2rem;color:#830866;"></i><h5 class="mt-2">{{ __('Refunded') }}</h5><p class="text-muted mb-0">{{ __('Contributions were returned to contributors.') }}</p></div>
            @endif
        @else
            <div class="bounty-card text-center"><a href="{{ route('login') }}" class="btn btn-bounty-primary">{{ __('Login to contribute') }}</a></div>
        @endauth

        {{-- Moderator panel --}}
        @auth
            @if(Auth::user()->role_id === 1)
                <div class="bounty-card bounty-mod">
                    <h5><i class="fas fa-user-shield"></i> {{ __('Moderator') }}</h5>
                    @if($campaign->claim_status == 'pending')
                        <p class="mb-2">{{ __('Pending claim by') }}: <strong>{{ optional($campaign->claimer)->name }}</strong> (&#64;{{ optional($campaign->claimer)->username }})</p>
                        @if($campaign->claim_message)<p class="text-muted">"{{ $campaign->claim_message }}"</p>@endif
                        <div class="d-flex" style="gap:10px;">
                            <form action="{{ route('bounty-campaigns.approve-claim', $campaign->id) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Approve & release') }}</button></form>
                            <form action="{{ route('bounty-campaigns.reject-claim', $campaign->id) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Reject claim') }}</button></form>
                        </div>
                    @endif
                    @if(!in_array($campaign->status, ['released', 'refunded']))
                        <form action="{{ route('bounty-campaigns.refund', $campaign->id) }}" method="POST" class="mt-3" onsubmit="return confirm('Refund all contributors?')">@csrf<button class="btn btn-outline-secondary btn-sm">{{ __('Refund all contributors') }}</button></form>
                    @endif
                </div>
            @endif
        @endauth

        {{-- Contributions list --}}
        <div class="bounty-card">
            <h5>{{ __('Contributions') }} ({{ $campaign->contributions->count() }})</h5>
            @forelse($campaign->contributions->sortByDesc('created_at') as $c)
                <div class="bounty-contrib">
                    <span>{{ optional($c->contributor)->name ?? __('Someone') }} @if($c->message)<small class="text-muted">— {{ $c->message }}</small>@endif</span>
                    <strong>${{ number_format($c->amount, 0) }}</strong>
                </div>
            @empty
                <p class="text-muted mb-0">{{ __('No contributions yet — be the first!') }}</p>
            @endforelse
        </div>
    </div>
</div>

@include('bounty-campaigns.partials.styles')
@endsection
