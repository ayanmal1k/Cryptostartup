@extends('layouts.user-no-nav')

@section('page_title', __('Bounty Campaigns'))

@section('content')
<div class="bounty-marketplace">
    <div class="bounty-hero">
        <div class="container">
            <h1 class="bounty-hero-title"><i class="fas fa-bullhorn"></i> {{ __('Bounty Campaigns') }}</h1>
            <p class="bounty-hero-subtitle">{{ __('Pool money to get your favourite creators to join. Funds are held safely in escrow until they join and a moderator approves the claim.') }}</p>
            @auth
                <button class="btn btn-bounty-primary" type="button" data-toggle="collapse" data-target="#createCampaign">
                    <i class="fas fa-plus-circle"></i> {{ __('Start a Campaign') }}
                </button>
            @endauth
        </div>
    </div>

    <div class="container bounty-container">

        @include('elements.message-alert')

        @auth
        <div class="collapse mb-4" id="createCampaign">
            <div class="bounty-card">
                <h5 class="mb-3">{{ __('Start a new campaign') }}</h5>
                <form action="{{ route('bounty-campaigns.store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>{{ __('Who do you want on the platform?') }}</label>
                        <input type="text" name="target_name" class="form-control" placeholder="e.g. Kim Kardashian" required>
                    </div>
                    <div class="form-group">
                        <label>{{ __('Their handle / social (optional)') }}</label>
                        <input type="text" name="target_handle" class="form-control" placeholder="@username">
                    </div>
                    <div class="form-group">
                        <label>{{ __('Why should they join? (optional)') }}</label>
                        <textarea name="target_description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="form-group col-6">
                            <label>{{ __('Goal amount ($)') }}</label>
                            <input type="number" name="goal_amount" class="form-control" min="1" step="1" required>
                        </div>
                        <div class="form-group col-6">
                            <label>{{ __('Deadline (days)') }}</label>
                            <input type="number" name="deadline_days" class="form-control" min="1" max="365" value="30">
                        </div>
                    </div>
                    <button class="btn btn-bounty-primary btn-block">{{ __('Create Campaign') }}</button>
                </form>
            </div>
        </div>
        @endauth

        <div class="bounty-stats">
            <div class="bounty-stat"><div class="bounty-stat-num">{{ $stats['active'] }}</div><div class="bounty-stat-label">{{ __('Active Campaigns') }}</div></div>
            <div class="bounty-stat"><div class="bounty-stat-num">${{ number_format($stats['raised'], 0) }}</div><div class="bounty-stat-label">{{ __('Held in Escrow') }}</div></div>
            <div class="bounty-stat"><div class="bounty-stat-num">{{ $stats['released'] }}</div><div class="bounty-stat-label">{{ __('Successful') }}</div></div>
        </div>

        <div class="bounty-grid">
            @forelse($campaigns as $campaign)
                <a href="{{ route('bounty-campaigns.show', $campaign->id) }}" class="bounty-item">
                    <div class="bounty-item-head">
                        <div class="bounty-avatar">{{ strtoupper(substr($campaign->target_name, 0, 1)) }}</div>
                        <div>
                            <h6 class="bounty-item-name">{{ $campaign->target_name }}</h6>
                            <span class="bounty-pill bounty-pill-{{ $campaign->status }}">{{ ucfirst(str_replace('_', ' ', $campaign->status)) }}</span>
                        </div>
                    </div>
                    @if($campaign->target_description)
                        <p class="bounty-item-desc">{{ \Str::limit($campaign->target_description, 90) }}</p>
                    @endif
                    <div class="bounty-progress"><div class="bounty-progress-bar" style="width: {{ $campaign->progress_percentage }}%"></div></div>
                    <div class="bounty-amounts mt-2">
                        <strong>${{ number_format($campaign->current_amount, 0) }}</strong>
                        <span>{{ __('of') }} ${{ number_format($campaign->goal_amount, 0) }}</span>
                    </div>
                </a>
            @empty
                <div class="bounty-empty">
                    <i class="fas fa-bullhorn"></i>
                    <h3>{{ __('No campaigns yet') }}</h3>
                    <p>{{ __('Be the first to start a campaign!') }}</p>
                </div>
            @endforelse
        </div>

        @if($campaigns->hasPages())
            <div class="mt-4 d-flex justify-content-center">{{ $campaigns->links() }}</div>
        @endif
    </div>
</div>

@include('bounty-campaigns.partials.styles')
@endsection
