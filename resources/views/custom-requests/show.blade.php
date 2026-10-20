@extends('layouts.user-no-nav')

@section('page_title', $customRequest->title)

@section('content')
<div class="container-fluid px-4 py-5">
    <!-- Hero Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="request-hero-card">
                <div class="hero-background">
                    <div class="hero-overlay"></div>
                    <div class="hero-content">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h1 class="hero-title mb-2">{{ $customRequest->title }}</h1>
                                <div class="hero-meta d-flex flex-wrap align-items-center gap-3 text-white-50">
                                    <span><i class="far fa-clock mr-1"></i>{{ $customRequest->created_at->diffForHumans() }}</span>
                                    @if($customRequest->deadline)
                                        <span><i class="far fa-calendar-alt mr-1"></i>{{ __('Due') }}: {{ $customRequest->deadline->format('M d, Y') }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="status-badge-large">
                                @if($customRequest->status == 'accepted')
                                    <span class="badge badge-success badge-lg">
                                        <i class="fas fa-play-circle mr-1"></i>{{ __('Active') }}
                                    </span>
                                @elseif($customRequest->status == 'completed')
                                    <span class="badge badge-info badge-lg">
                                        <i class="fas fa-trophy mr-1"></i>{{ __('Completed') }}
                                    </span>
                                @elseif($customRequest->status == 'rejected')
                                    <span class="badge badge-danger badge-lg">
                                        <i class="fas fa-times-circle mr-1"></i>{{ __('Rejected') }}
                                    </span>
                                @elseif($customRequest->status == 'cancelled')
                                    <span class="badge badge-secondary badge-lg">
                                        <i class="fas fa-ban mr-1"></i>{{ __('Cancelled') }}
                                    </span>
                                @else
                                    <span class="badge badge-warning badge-lg">
                                        <i class="fas fa-clock mr-1"></i>{{ __('Pending') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- User Info in Hero -->
                        <div class="hero-users d-flex flex-wrap align-items-center gap-4">
                            <div class="user-info-item d-flex align-items-center">
                                <div class="user-avatar-lg mr-3">
                                    @if($customRequest->creator->avatar)
                                        <img src="{{ asset('storage/' . $customRequest->creator->avatar) }}" alt="{{ $customRequest->creator->name }}" class="rounded-circle">
                                    @else
                                        <div class="avatar-placeholder-lg rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    @endif
                                </div>
                                <div>
                                    <div class="text-white-50 small">{{ __('Creator') }}</div>
                                    <a href="{{ route('profile', ['username' => $customRequest->creator->username]) }}" class="text-white text-decoration-none font-weight-bold">
                                        {{ $customRequest->creator->name }}
                                    </a>
                                </div>
                            </div>

                            @if($customRequest->requester)
                                <div class="user-info-item d-flex align-items-center">
                                    <div class="user-avatar-lg mr-3">
                                        @if($customRequest->requester->avatar)
                                            <img src="{{ asset('storage/' . $customRequest->requester->avatar) }}" alt="{{ $customRequest->requester->name }}" class="rounded-circle">
                                        @else
                                            <div class="avatar-placeholder-lg rounded-circle bg-info text-white d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <div class="text-white-50 small">{{ __('Requester') }}</div>
                                        <a href="{{ route('profile', ['username' => $customRequest->requester->username]) }}" class="text-white text-decoration-none font-weight-bold">
                                            {{ $customRequest->requester->name }}
                                        </a>
                                    </div>
                                </div>
                            @endif

                            <!-- Type Badge -->
                            <div class="type-indicator">
                                <span class="badge badge-light badge-lg">
                                    @if($customRequest->type == 'marketplace')
                                        <i class="fas fa-store mr-1"></i>{{ __('Marketplace Request') }}
                                    @elseif($customRequest->type == 'private')
                                        <i class="fas fa-lock mr-1"></i>{{ __('Private Request') }}
                                    @else
                                        <i class="fas fa-globe mr-1"></i>{{ __('Public Request') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <!-- Main Content Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <!-- Description Section -->
                    <div class="content-section mb-4">
                        <h4 class="section-title mb-3">
                            <i class="fas fa-align-left text-primary mr-2"></i>{{ __('Description') }}
                        </h4>
                        <div class="description-content">
                            <p class="lead">{{ $customRequest->description }}</p>
                        </div>
                    </div>

                    <!-- Progress/Price Section -->
                    @if($customRequest->is_marketplace)
                        <div class="content-section mb-4">
                            <h4 class="section-title mb-4">
                                <i class="fas fa-chart-line text-success mr-2"></i>{{ __('Funding Progress') }}
                            </h4>
                            <div class="progress-hero mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="progress-stats">
                                        <h3 class="progress-amount text-success mb-0">
                                            ${{ number_format($customRequest->current_amount, 0) }}
                                        </h3>
                                        <small class="text-muted">{{ __('Raised') }}</small>
                                    </div>
                                    <div class="progress-stats text-right">
                                        <h3 class="progress-amount text-muted mb-0">
                                            ${{ number_format($customRequest->goal_amount, 0) }}
                                        </h3>
                                        <small class="text-muted">{{ __('Goal') }}</small>
                                    </div>
                                </div>
                                <div class="progress progress-xl mb-3">
                                    <div class="progress-bar progress-bar-hero" role="progressbar"
                                         style="width: {{ min(100, $customRequest->progress_percentage) }}%"
                                         aria-valuenow="{{ $customRequest->progress_percentage }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="text-center">
                                    <span class="progress-percentage h4 text-primary font-weight-bold">
                                        {{ number_format($customRequest->progress_percentage, 1) }}%
                                    </span>
                                    <small class="text-muted d-block">{{ __('Complete') }}</small>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="content-section mb-4">
                            <h4 class="section-title mb-3">
                                <i class="fas fa-dollar-sign text-success mr-2"></i>{{ __('Price') }}
                            </h4>
                            <div class="price-display">
                                <span class="price-amount">${{ number_format($customRequest->price, 2) }}</span>
                                <small class="text-muted d-block">{{ __('Fixed price for this request') }}</small>
                            </div>
                        </div>
                    @endif

                    <!-- Payment Section (if payment required but not received) -->
                    @auth
                        @if($customRequest->requester_id == Auth::id() && $customRequest->upfront_payment > 0 && !$customRequest->payment_received)
                            <div class="payment-required-section mb-4">
                                <div class="alert alert-warning border-0 shadow-sm">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div>
                                            <h5 class="mb-2">
                                                <i class="fas fa-credit-card text-warning"></i>
                                                {{ __('Upfront Payment Required') }}
                                            </h5>
                                            <p class="mb-2">
                                                {{ __('To proceed with this request, you need to make an upfront payment of') }}
                                                <strong class="h5 text-dark">${{ number_format($customRequest->upfront_payment, 2) }}</strong>
                                            </p>
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle"></i>
                                                {{ __('Minimum upfront payment is $1.00. This ensures commitment to the request.') }}
                                            </small>
                                        </div>
                                        <div>
                                            <button type="button" class="btn btn-warning btn-lg" onclick="processUpfrontPayment({{ $customRequest->id }})">
                                                <i class="fas fa-credit-card"></i>
                                                {{ __('Pay Now') }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endauth

                    <!-- Voting Section (for public/marketplace requests) -->
                    @if($customRequest->requires_voting && in_array($customRequest->status, ['accepted', 'completed']))
                        <div class="voting-section mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-gradient-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-vote-yea"></i>
                                        {{ __('Voting & Fund Release') }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="voting-stats mb-4">
                                        <div class="row text-center">
                                            <div class="col-md-4">
                                                <div class="stat-box">
                                                    <h3 class="text-success mb-1">{{ $customRequest->approval_votes ?? 0 }}</h3>
                                                    <small class="text-muted">{{ __('Approvals') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="stat-box">
                                                    <h3 class="text-danger mb-1">{{ $customRequest->rejection_votes ?? 0 }}</h3>
                                                    <small class="text-muted">{{ __('Rejections') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="stat-box">
                                                    <h3 class="text-primary mb-1">{{ number_format($customRequest->approval_percentage ?? 0, 1) }}%</h3>
                                                    <small class="text-muted">{{ __('Approval Rate') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="progress mt-3" style="height: 20px;">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: {{ $customRequest->approval_percentage ?? 0 }}%"
                                                 aria-valuenow="{{ $customRequest->approval_percentage ?? 0 }}" 
                                                 aria-valuemin="0" 
                                                 aria-valuemax="100">
                                                {{ number_format($customRequest->approval_percentage ?? 0, 1) }}%
                                            </div>
                                        </div>
                                        
                                        @if($customRequest->hasMajorityApproval())
                                            <div class="alert alert-success mt-3 mb-0">
                                                <i class="fas fa-check-circle"></i>
                                                <strong>{{ __('Majority Approval Reached!') }}</strong>
                                                {{ __('Funds can now be released to the creator.') }}
                                            </div>
                                        @else
                                            <div class="alert alert-info mt-3 mb-0">
                                                <i class="fas fa-info-circle"></i>
                                                {{ __('Majority approval (50%+) is required to release funds.') }}
                                            </div>
                                        @endif
                                    </div>

                                    @auth
                                        @if($customRequest->canUserVote(Auth::id()) && !$customRequest->hasUserVoted(Auth::id()))
                                            <div class="voting-actions">
                                                <h6 class="mb-3">{{ __('Cast Your Vote') }}</h6>
                                                <div class="d-flex gap-2 mb-3">
                                                    <button type="button" class="btn btn-success btn-lg flex-fill" onclick="castVote({{ $customRequest->id }}, 'approve')">
                                                        <i class="fas fa-thumbs-up"></i>
                                                        {{ __('Approve Release') }}
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-lg flex-fill" onclick="castVote({{ $customRequest->id }}, 'reject')">
                                                        <i class="fas fa-thumbs-down"></i>
                                                        {{ __('Reject Release') }}
                                                    </button>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ __('Your vote helps determine if funds should be released to the creator.') }}
                                                </small>
                                            </div>
                                        @elseif($customRequest->hasUserVoted(Auth::id()))
                                            @php
                                                $userVote = $customRequest->getUserVote(Auth::id());
                                            @endphp
                                            <div class="alert alert-info">
                                                <i class="fas fa-check"></i>
                                                {{ __('You have already voted:') }}
                                                <strong>{{ ucfirst($userVote->vote_type) }}</strong>
                                                @if($userVote->comment)
                                                    <div class="mt-2">
                                                        <small><em>"{{ $userVote->comment }}"</em></small>
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="alert alert-warning">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                {{ __('Only the requester and contributors can vote on fund release.') }}
                                            </div>
                                        @endif

                                        @if($customRequest->creator_id == Auth::id() && $customRequest->canReleaseFunds() && !$customRequest->funds_released)
                                            <div class="fund-release-section mt-4 pt-4 border-top">
                                                <h6 class="mb-3">{{ __('Release Funds') }}</h6>
                                                <button type="button" class="btn btn-primary btn-lg" onclick="releaseFunds({{ $customRequest->id }})">
                                                    <i class="fas fa-unlock"></i>
                                                    {{ __('Release Funds to Creator') }}
                                                </button>
                                                <small class="d-block text-muted mt-2">
                                                    <i class="fas fa-info-circle"></i>
                                                    {{ __('Funds will be released to your account after majority approval.') }}
                                                </small>
                                            </div>
                                        @endif
                                    @endauth

                                    <!-- Votes List -->
                                    @if($customRequest->votes && $customRequest->votes->count() > 0)
                                        <div class="votes-list mt-4 pt-4 border-top">
                                            <h6 class="mb-3">{{ __('Votes') }}</h6>
                                            <div class="list-group">
                                                @foreach($customRequest->votes as $vote)
                                                    <div class="list-group-item">
                                                        <div class="d-flex justify-content-between align-items-start">
                                                            <div>
                                                                <strong>{{ $vote->voter->name ?? 'Unknown' }}</strong>
                                                                @if($vote->is_requester)
                                                                    <span class="badge badge-primary ml-2">{{ __('Requester') }}</span>
                                                                @endif
                                                                @if($vote->is_contributor)
                                                                    <span class="badge badge-info ml-2">{{ __('Contributor') }}</span>
                                                                @endif
                                                                <div class="mt-1">
                                                                    @if($vote->vote_type == 'approve')
                                                                        <span class="badge badge-success">
                                                                            <i class="fas fa-thumbs-up"></i> {{ __('Approved') }}
                                                                        </span>
                                                                    @elseif($vote->vote_type == 'reject')
                                                                        <span class="badge badge-danger">
                                                                            <i class="fas fa-thumbs-down"></i> {{ __('Rejected') }}
                                                                        </span>
                                                                    @else
                                                                        <span class="badge badge-secondary">
                                                                            <i class="fas fa-minus"></i> {{ __('Abstained') }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                @if($vote->comment)
                                                                    <p class="mb-0 mt-2 text-muted small">{{ $vote->comment }}</p>
                                                                @endif
                                                            </div>
                                                            <small class="text-muted">{{ $vote->created_at->diffForHumans() }}</small>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Support Ticket Section -->
                    @auth
                        <div class="support-section mb-4">
                            <div class="card border-0 shadow-sm">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">
                                        <i class="fas fa-headset"></i>
                                        {{ __('Customer Support') }}
                                    </h5>
                                </div>
                                <div class="card-body">
                                    @if($customRequest->has_support_ticket)
                                        <div class="alert alert-info">
                                            <i class="fas fa-ticket-alt"></i>
                                            {{ __('You have an open support ticket for this request.') }}
                                            <a href="#" class="btn btn-sm btn-outline-primary ml-2" onclick="showSupportTicket({{ $customRequest->id }})">
                                                {{ __('View Ticket') }}
                                            </a>
                                        </div>
                                    @else
                                        <p class="text-muted mb-3">
                                            {{ __('Need help with this request? Create a support ticket for assistance.') }}
                                        </p>
                                        <button type="button" class="btn btn-outline-primary" onclick="showCreateSupportTicketModal({{ $customRequest->id }})">
                                            <i class="fas fa-plus-circle"></i>
                                            {{ __('Create Support Ticket') }}
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endauth

                    <!-- Action Buttons -->
                    @auth
                        <div class="action-section">
                            @if($customRequest->creator_id == Auth::id())
                                <div class="creator-actions mb-4">
                                    <h5 class="mb-3">{{ __('Manage Request') }}</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($customRequest->status == 'pending')
                                            <button type="button" class="btn btn-success btn-lg px-4" onclick="handleRequestAction('accept', {{ $customRequest->id }})">
                                                <i class="fas fa-check-circle mr-2"></i>{{ __('Accept Request') }}
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-lg px-4" onclick="handleRequestAction('reject', {{ $customRequest->id }})">
                                                <i class="fas fa-times-circle mr-2"></i>{{ __('Reject Request') }}
                                            </button>
                                        @endif
                                        @if($customRequest->status == 'accepted')
                                            <button type="button" class="btn btn-primary btn-lg px-4" onclick="handleRequestAction('complete', {{ $customRequest->id }})">
                                                <i class="fas fa-check-double mr-2"></i>{{ __('Mark as Completed') }}
                                            </button>
                                        @endif
                                        @if(in_array($customRequest->status, ['pending', 'accepted']))
                                            <button type="button" class="btn btn-outline-secondary btn-lg px-4" onclick="cancelRequest({{ $customRequest->id }})">
                                                <i class="fas fa-ban mr-2"></i>{{ __('Cancel Request') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @elseif($customRequest->status == 'accepted' && $customRequest->is_marketplace)
                                <div class="contribution-section">
                                    <button class="btn btn-primary btn-xl contribute-btn w-100" data-request-id="{{ $customRequest->id }}">
                                        <i class="fas fa-hand-holding-heart mr-2"></i>{{ __('Contribute to This Request') }}
                                        <div class="small opacity-75">{{ __('Help make this happen!') }}</div>
                                    </button>
                                </div>
                            @elseif($customRequest->requester_id == Auth::id() && in_array($customRequest->status, ['pending', 'accepted']))
                                <div class="requester-actions">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        {{ __('You created this request. The creator will respond soon.') }}
                                        @if($customRequest->status == 'pending')
                                            <button type="button" class="btn btn-sm btn-outline-danger ml-3" onclick="cancelRequest({{ $customRequest->id }})">
                                                {{ __('Cancel Request') }}
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endauth
                </div>
            </div>

            <!-- Contributions Section -->
            @if($customRequest->is_marketplace && $customRequest->contributions->count() > 0)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light border-0">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">
                                <i class="fas fa-hand-holding-heart text-success mr-2"></i>{{ __('Contributions') }}
                                <span class="badge badge-success ml-2">{{ $customRequest->contributions->where('status', 'completed')->count() }}</span>
                            </h4>
                            <small class="text-muted">{{ __('Recent supporters') }}</small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="contributions-list">
                            @foreach($customRequest->contributions->where('status', 'completed')->take(10) as $contribution)
                                <div class="contribution-item p-3 border-bottom">
                                    <div class="d-flex align-items-start">
                                        <div class="contributor-avatar mr-3">
                                            @if($contribution->contributor->avatar)
                                                <img src="{{ asset('storage/' . $contribution->contributor->avatar) }}" alt="{{ $contribution->contributor->name }}" class="rounded-circle">
                                            @else
                                                <div class="avatar-placeholder-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <div>
                                                    <strong class="text-dark">{{ $contribution->contributor->name }}</strong>
                                                    <small class="text-muted ml-2">
                                                        <i class="far fa-clock mr-1"></i>{{ $contribution->created_at->diffForHumans() }}
                                                    </small>
                                                </div>
                                                <div class="contribution-amount">
                                                    <span class="badge badge-success badge-lg">
                                                        <i class="fas fa-dollar-sign mr-1"></i>${{ number_format($contribution->amount, 2) }}
                                                    </span>
                                                </div>
                                            </div>
                                            @if($contribution->message)
                                                <div class="contribution-message">
                                                    <p class="text-muted mb-0 small">"{{ Str::limit($contribution->message, 150) }}"</p>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        @if($customRequest->contributions->where('status', 'completed')->count() > 10)
                            <div class="text-center p-3">
                                <small class="text-muted">{{ __('And') }} {{ $customRequest->contributions->where('status', 'completed')->count() - 10 }} {{ __('more contributions...') }}</small>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Request Details Card -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-light border-0">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle text-primary mr-2"></i>{{ __('Request Details') }}
                    </h5>
                </div>
                <div class="card-body">
                    <div class="details-list">
                        <div class="detail-item mb-3">
                            <div class="detail-label text-muted small">{{ __('Request Type') }}</div>
                            <div class="detail-value">
                                @if($customRequest->type == 'marketplace')
                                    <span class="badge badge-info">
                                        <i class="fas fa-store mr-1"></i>{{ __('Marketplace') }}
                                    </span>
                                @elseif($customRequest->type == 'private')
                                    <span class="badge badge-warning">
                                        <i class="fas fa-lock mr-1"></i>{{ __('Private') }}
                                    </span>
                                @else
                                    <span class="badge badge-success">
                                        <i class="fas fa-globe mr-1"></i>{{ __('Public') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="detail-item mb-3">
                            <div class="detail-label text-muted small">{{ __('Status') }}</div>
                            <div class="detail-value">
                                @if($customRequest->status == 'accepted')
                                    <span class="badge badge-success">
                                        <i class="fas fa-play-circle mr-1"></i>{{ __('Active') }}
                                    </span>
                                @elseif($customRequest->status == 'completed')
                                    <span class="badge badge-info">
                                        <i class="fas fa-check-circle mr-1"></i>{{ __('Completed') }}
                                    </span>
                                @elseif($customRequest->status == 'rejected')
                                    <span class="badge badge-danger">
                                        <i class="fas fa-times-circle mr-1"></i>{{ __('Rejected') }}
                                    </span>
                                @elseif($customRequest->status == 'cancelled')
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-ban mr-1"></i>{{ __('Cancelled') }}
                                    </span>
                                @else
                                    <span class="badge badge-warning">
                                        <i class="fas fa-clock mr-1"></i>{{ __('Pending') }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="detail-item mb-3">
                            <div class="detail-label text-muted small">{{ __('Created') }}</div>
                            <div class="detail-value">
                                <i class="far fa-calendar-alt mr-2 text-muted"></i>
                                {{ $customRequest->created_at->format('M d, Y \a\t g:i A') }}
                            </div>
                        </div>

                        @if($customRequest->deadline)
                            <div class="detail-item mb-3">
                                <div class="detail-label text-muted small">{{ __('Deadline') }}</div>
                                <div class="detail-value">
                                    <i class="far fa-calendar-check mr-2 text-muted"></i>
                                    {{ $customRequest->deadline->format('M d, Y') }}
                                    @if($customRequest->deadline->isPast() && $customRequest->status == 'accepted')
                                        <span class="badge badge-danger badge-sm ml-2">{{ __('Overdue') }}</span>
                                    @elseif($customRequest->deadline->diffInDays() <= 7 && $customRequest->status == 'accepted')
                                        <span class="badge badge-warning badge-sm ml-2">{{ __('Due Soon') }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($customRequest->is_marketplace)
                            <div class="detail-item">
                                <div class="detail-label text-muted small">{{ __('Contributors') }}</div>
                                <div class="detail-value">
                                    <i class="fas fa-users mr-2 text-muted"></i>
                                    {{ $customRequest->contributions->where('status', 'completed')->count() }} {{ __('supporters') }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            @auth
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light border-0">
                        <h5 class="mb-0">
                            <i class="fas fa-bolt text-warning mr-2"></i>{{ __('Quick Actions') }}
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <a href="{{ route('custom-requests.marketplace') }}" class="btn btn-outline-primary btn-block mb-2">
                                <i class="fas fa-store mr-2"></i>{{ __('Browse Marketplace') }}
                            </a>
                            <a href="{{ route('custom-requests.my-requests') }}" class="btn btn-outline-secondary btn-block mb-2">
                                <i class="fas fa-list mr-2"></i>{{ __('My Requests') }}
                            </a>
                            <button type="button" class="btn btn-outline-success btn-block" onclick="CustomRequest.showCreateModal()">
                                <i class="fas fa-plus-circle mr-2"></i>{{ __('Create New Request') }}
                            </button>
                        </div>
                    </div>
                </div>
            @endauth
        </div>
    </div>
</div>

@auth
<!-- Enhanced Contribution Modal -->
<div class="modal fade" id="contributeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-gradient border-0">
                <h5 class="modal-title font-weight-bold text-white">
                    <i class="fas fa-hand-holding-heart mr-2"></i>{{ __('Support This Request') }}
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="contributeForm">
                <div class="modal-body p-4">
                    <div class="request-preview-card mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <div class="preview-icon mr-3">
                                <i class="fas fa-lightbulb text-warning fa-2x"></i>
                            </div>
                            <div>
                                <h6 class="font-weight-bold mb-1" id="modal-request-title">{{ $customRequest->title }}</h6>
                                <p class="text-muted small mb-0">{{ __('Your contribution helps bring this idea to life!') }}</p>
                            </div>
                        </div>
                        <div class="progress progress-sm mb-2">
                            <div class="progress-bar bg-success" role="progressbar"
                                 style="width: {{ min(100, $customRequest->progress_percentage) }}%"
                                 aria-valuenow="{{ $customRequest->progress_percentage }}"
                                 aria-valuemin="0" aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">{{ number_format($customRequest->progress_percentage, 1) }}% {{ __('funded') }}</small>
                    </div>

                    <input type="hidden" id="request_id" name="request_id">
                    <div class="form-group">
                        <label for="amount" class="font-weight-bold h6">
                            <i class="fas fa-dollar-sign text-success mr-1"></i>{{ __('Your Contribution Amount') }}
                        </label>
                        <div class="input-group input-group-lg">
                            <div class="input-group-prepend">
                                <span class="input-group-text">$</span>
                            </div>
                            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" required placeholder="0.00">
                        </div>
                        <small class="form-text text-muted">{{ __('Enter any amount you\'d like to contribute') }}</small>
                    </div>
                    <div class="form-group">
                        <label for="message" class="font-weight-bold h6">
                            <i class="fas fa-comment-heart text-info mr-1"></i>{{ __('Encouraging Message') }} <small class="text-muted">({{ __('Optional') }})</small>
                        </label>
                        <textarea class="form-control" id="message" name="message" rows="3" placeholder="{{ __('Leave a supportive message for the creator...') }}"></textarea>
                        <small class="form-text text-muted">{{ __('Your message will be displayed publicly with your contribution') }}</small>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-outline-secondary btn-lg px-4" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>{{ __('Maybe Later') }}
                    </button>
                    <button type="submit" class="btn btn-success btn-lg px-4">
                        <i class="fas fa-heart mr-1"></i>{{ __('Make My Contribution') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endauth

<style>
/* Hero Section */
.request-hero-card {
    position: relative;
    border-radius: 20px;
    overflow: hidden;
    margin-bottom: 2rem;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.hero-background {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    position: relative;
    min-height: 250px;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.3);
}

.hero-content {
    position: relative;
    z-index: 2;
    padding: 2rem;
    color: white;
}

.hero-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.hero-meta {
    font-size: 0.9rem;
}

.status-badge-large .badge {
    font-size: 1rem;
    padding: 0.75rem 1.5rem;
    border-radius: 25px;
    font-weight: 600;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.hero-users {
    margin-top: 2rem;
}

.user-avatar-lg img,
.avatar-placeholder-lg {
    width: 60px;
    height: 60px;
    border: 3px solid rgba(255,255,255,0.3);
}

.avatar-placeholder-lg {
    font-size: 1.5rem;
}

/* Content Sections */
.content-section {
    padding: 1.5rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.content-section:last-child {
    border-bottom: none;
}

.section-title {
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 1rem;
}

.description-content {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    border-left: 4px solid #667eea;
}

/* Progress Hero */
.progress-hero {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 50%, #4facfe 100%);
    padding: 2rem;
    border-radius: 15px;
    color: white;
    text-align: center;
}

.progress-amount {
    font-size: 2.5rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.progress-xl {
    height: 12px;
    border-radius: 10px;
    background-color: rgba(255,255,255,0.3);
    overflow: hidden;
}

.progress-bar-hero {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    border-radius: 10px;
    transition: width 1s ease;
}

.progress-percentage {
    margin-top: 1rem;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Price Display */
.price-display {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    color: white;
    padding: 2rem;
    border-radius: 15px;
    text-align: center;
}

.price-amount {
    font-size: 3rem;
    font-weight: 700;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

/* Action Buttons */
.btn-xl {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    border-radius: 12px;
    font-weight: 600;
    position: relative;
    overflow: hidden;
}

.btn-xl::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    transition: left 0.5s;
}

.btn-xl:hover::before {
    left: 100%;
}

/* Contributions List */
.contributions-list {
    max-height: 400px;
    overflow-y: auto;
}

.contribution-item:hover {
    background-color: #f8f9fa;
}

.contributor-avatar img,
.avatar-placeholder-sm {
    width: 40px;
    height: 40px;
}

.contribution-amount .badge {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.contribution-message {
    background: #f8f9fa;
    padding: 0.75rem;
    border-radius: 8px;
    margin-top: 0.5rem;
}

/* Details Sidebar */
.details-list .detail-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}

.detail-label {
    flex: 0 0 40%;
    font-weight: 500;
}

.detail-value {
    flex: 1;
    text-align: right;
}

.detail-value .badge {
    font-size: 0.8rem;
}

/* Quick Actions */
.quick-actions .btn {
    border-radius: 8px;
    font-weight: 500;
}

/* Modal Styles */
.bg-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.request-preview-card {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 1.5rem;
    border-radius: 12px;
}

.preview-icon {
    opacity: 0.9;
}

.progress-sm {
    height: 6px;
    border-radius: 3px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-content {
        padding: 1.5rem;
    }

    .hero-title {
        font-size: 2rem;
    }

    .hero-users {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
    }

    .progress-hero {
        padding: 1.5rem;
    }

    .progress-amount {
        font-size: 2rem;
    }

    .price-display {
        padding: 1.5rem;
    }

    .price-amount {
        font-size: 2rem;
    }

    .details-list .detail-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.25rem;
    }

    .detail-value {
        text-align: left;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .hero-background {
        min-height: 200px;
    }

    .hero-title {
        font-size: 1.5rem;
    }

    .status-badge-large .badge {
        font-size: 0.9rem;
        padding: 0.5rem 1rem;
    }

    .btn-xl {
        padding: 0.75rem 1.5rem;
        font-size: 1rem;
    }
}

/* Loading Animation */
@keyframes shimmer {
    0% { background-position: -200px 0; }
    100% { background-position: calc(200px + 100%) 0; }
}

.btn-xl:hover {
    animation: shimmer 1.5s infinite;
}

/* Badge enhancements */
.badge-lg {
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
    border-radius: 20px;
}

/* Payment Required Section */
.payment-required-section .alert {
    border-radius: 16px;
    border-left: 4px solid #ffc107;
}

/* Voting Section */
.voting-section .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px 16px 0 0;
}

.voting-stats .stat-box {
    padding: 1rem;
}

.voting-stats h3 {
    font-size: 2rem;
    font-weight: 700;
}

.voting-actions .btn {
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.voting-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.votes-list .list-group-item {
    border-radius: 8px;
    margin-bottom: 0.5rem;
    border: 1px solid #e2e8f0;
}

/* Support Section */
.support-section .card-header {
    background: #f7fafc;
    border-bottom: 1px solid #e2e8f0;
}

.badge-sm {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Enhanced contribution modal functionality
    const contributeBtn = document.querySelector('.contribute-btn');
    if (contributeBtn) {
        const contributeModal = new bootstrap.Modal(document.getElementById('contributeModal'));
        const contributeForm = document.getElementById('contributeForm');

        contributeBtn.addEventListener('click', function() {
            const requestId = this.getAttribute('data-request-id');
            document.getElementById('request_id').value = requestId;
            contributeModal.show();
        });

        contributeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const requestId = document.getElementById('request_id').value;
            const amount = document.getElementById('amount').value;
            const message = document.getElementById('message').value;

            // Show loading state
            const submitBtn = contributeForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>{{ __("Processing Contribution...") }}';

            fetch(`/custom-requests/${requestId}/contribute`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    amount: amount,
                    message: message
                })
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                const isJson = contentType && contentType.includes('application/json');

                if (isJson) {
                    return response.json();
                } else {
                    throw { message: '{{ __("Invalid response from server") }}' };
                }
            })
            .then(data => {
                if (data.success) {
                    // Show success message
                    if (typeof launchToast !== 'undefined') {
                        launchToast('success', '{{ __("Success") }}', data.message || '{{ __("Thank you for your contribution!") }}');
                    } else {
                        alert(data.message || '{{ __("Thank you for your contribution!") }}');
                    }

                    contributeModal.hide();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    // Show error
                    const errorMsg = data.message || '{{ __("Failed to process contribution") }}';
                    if (typeof launchToast !== 'undefined') {
                        launchToast('danger', '{{ __("Error") }}', errorMsg);
                    } else {
                        alert('{{ __("Error") }}: ' + errorMsg);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || '{{ __("An error occurred. Please try again.") }}';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert(errorMsg);
                }
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    // Enhanced request action handlers
    window.handleRequestAction = function(action, requestId) {
        const actionMessages = {
            'accept': '{{ __("Accepting request...") }}',
            'reject': '{{ __("Rejecting request...") }}',
            'complete': '{{ __("Completing request...") }}'
        };

        const actionText = actionMessages[action] || '{{ __("Processing...") }}';

        // Find and disable the button
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>' + actionText;

        fetch(`/custom-requests/${requestId}/${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');

            if (isJson) {
                return response.json();
            } else {
                throw { message: '{{ __("Invalid response from server") }}' };
            }
        })
        .then(data => {
            if (data.success) {
                // Show success message
                const successMessages = {
                    'accept': '{{ __("Request accepted successfully!") }}',
                    'reject': '{{ __("Request rejected successfully!") }}',
                    'complete': '{{ __("Request marked as completed!") }}'
                };
                const successMsg = data.message || successMessages[action] || '{{ __("Action completed successfully!") }}';

                if (typeof launchToast !== 'undefined') {
                    launchToast('success', '{{ __("Success") }}', successMsg);
                } else {
                    alert(successMsg);
                }

                // Reload page after short delay to show updated status
                setTimeout(() => {
                    location.reload();
                }, 1000);
            } else {
                const errorMsg = data.message || `{{ __("Failed to") }} ${action} {{ __("request") }}`;
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert('{{ __("Error") }}: ' + errorMsg);
                }
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMsg = error.message || '{{ __("An error occurred. Please try again.") }}';
            if (typeof launchToast !== 'undefined') {
                launchToast('danger', '{{ __("Error") }}', errorMsg);
            } else {
                alert(errorMsg);
            }
            button.disabled = false;
            button.innerHTML = originalText;
        });
    };

    // Cancel request function
    window.cancelRequest = function(requestId) {
        if (confirm('{{ __("Are you sure you want to cancel this request? This action cannot be undone.") }}')) {
            fetch(`/custom-requests/${requestId}/cancel`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (typeof launchToast !== 'undefined') {
                        launchToast('success', '{{ __("Success") }}', data.message || '{{ __("Request cancelled successfully!") }}');
                    } else {
                        alert(data.message || '{{ __("Request cancelled successfully!") }}');
                    }
                    setTimeout(() => location.reload(), 1000);
                } else {
                    const errorMsg = data.message || '{{ __("Failed to cancel request") }}';
                    if (typeof launchToast !== 'undefined') {
                        launchToast('danger', '{{ __("Error") }}', errorMsg);
                    } else {
                        alert('{{ __("Error") }}: ' + errorMsg);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = '{{ __("An error occurred. Please try again.") }}';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert(errorMsg);
                }
            });
        }
    };

    // Process upfront payment
    window.processUpfrontPayment = function(requestId) {
        if (confirm('{{ __("You will be redirected to complete the payment. Continue?") }}')) {
            fetch(`/custom-requests/${requestId}/payment`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    provider: 'credit' // Default to credit/wallet payment
                })
            })
            .then(async response => {
                const contentType = response.headers.get('content-type');
                const isJson = contentType && contentType.includes('application/json');
                
                if (isJson) {
                    return response.json();
                } else {
                    throw { message: '{{ __("Invalid response from server") }}' };
                }
            })
            .then(data => {
                if (data.success) {
                    if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    } else {
                        if (typeof launchToast !== 'undefined') {
                            launchToast('success', '{{ __("Success") }}', data.message || '{{ __("Payment processed successfully!") }}');
                        }
                        setTimeout(() => location.reload(), 1500);
                    }
                } else {
                    const errorMsg = data.message || '{{ __("Payment processing failed") }}';
                    if (typeof launchToast !== 'undefined') {
                        launchToast('danger', '{{ __("Error") }}', errorMsg);
                    } else {
                        alert('{{ __("Error") }}: ' + errorMsg);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const errorMsg = error.message || '{{ __("An error occurred. Please try again.") }}';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert(errorMsg);
                }
            });
        }
    };

    // Cast vote on request
    window.castVote = function(requestId, voteType) {
        const comment = prompt('{{ __("Optional: Add a comment with your vote") }}');
        
        fetch(`/custom-requests/${requestId}/vote`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                vote_type: voteType,
                comment: comment || null
            })
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            
            if (isJson) {
                return response.json();
            } else {
                throw { message: '{{ __("Invalid response from server") }}' };
            }
        })
        .then(data => {
            if (data.success) {
                if (typeof launchToast !== 'undefined') {
                    launchToast('success', '{{ __("Success") }}', data.message || '{{ __("Your vote has been recorded!") }}');
                } else {
                    alert(data.message || '{{ __("Your vote has been recorded!") }}');
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                const errorMsg = data.message || '{{ __("Failed to record vote") }}';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert('{{ __("Error") }}: ' + errorMsg);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMsg = error.message || '{{ __("An error occurred. Please try again.") }}';
            if (typeof launchToast !== 'undefined') {
                launchToast('danger', '{{ __("Error") }}', errorMsg);
            } else {
                alert(errorMsg);
            }
        });
    };

    // Release funds to creator
    window.releaseFunds = function(requestId) {
        const releaseNotes = prompt('{{ __("Optional: Add notes about the fund release") }}');
        
        if (!confirm('{{ __("Are you sure you want to release funds? This action cannot be undone.") }}')) {
            return;
        }

        fetch(`/custom-requests/${requestId}/release-funds`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                release_notes: releaseNotes || null
            })
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            
            if (isJson) {
                return response.json();
            } else {
                throw { message: '{{ __("Invalid response from server") }}' };
            }
        })
        .then(data => {
            if (data.success) {
                if (typeof launchToast !== 'undefined') {
                    launchToast('success', '{{ __("Success") }}', data.message || '{{ __("Funds released successfully!") }}');
                } else {
                    alert(data.message || '{{ __("Funds released successfully!") }}');
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                const errorMsg = data.message || '{{ __("Failed to release funds") }}';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert('{{ __("Error") }}: ' + errorMsg);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMsg = error.message || '{{ __("An error occurred. Please try again.") }}';
            if (typeof launchToast !== 'undefined') {
                launchToast('danger', '{{ __("Error") }}', errorMsg);
            } else {
                alert(errorMsg);
            }
        });
    };

    // Show create support ticket modal
    window.showCreateSupportTicketModal = function(requestId) {
        // Create modal dynamically or use existing modal
        const subject = prompt('{{ __("Enter ticket subject") }}');
        if (!subject) return;
        
        const description = prompt('{{ __("Describe your issue") }}');
        if (!description) return;
        
        const type = prompt('{{ __("Ticket type (general/dispute/payment/voting/technical)") }}', 'general');
        const priority = prompt('{{ __("Priority (low/normal/high/urgent)") }}', 'normal');

        fetch(`/custom-requests/${requestId}/support-ticket`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                type: type || 'general',
                priority: priority || 'normal',
                subject: subject,
                description: description
            })
        })
        .then(async response => {
            const contentType = response.headers.get('content-type');
            const isJson = contentType && contentType.includes('application/json');
            
            if (isJson) {
                return response.json();
            } else {
                throw { message: '{{ __("Invalid response from server") }}' };
            }
        })
        .then(data => {
            if (data.success) {
                if (typeof launchToast !== 'undefined') {
                    launchToast('success', '{{ __("Success") }}', data.message || '{{ __("Support ticket created successfully!") }}');
                } else {
                    alert(data.message || '{{ __("Support ticket created successfully!") }}');
                }
                setTimeout(() => location.reload(), 1500);
            } else {
                const errorMsg = data.message || '{{ __("Failed to create support ticket") }}';
                if (typeof launchToast !== 'undefined') {
                    launchToast('danger', '{{ __("Error") }}', errorMsg);
                } else {
                    alert('{{ __("Error") }}: ' + errorMsg);
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMsg = error.message || '{{ __("An error occurred. Please try again.") }}';
            if (typeof launchToast !== 'undefined') {
                launchToast('danger', '{{ __("Error") }}', errorMsg);
            } else {
                alert(errorMsg);
            }
        });
    };

    // Show support ticket (placeholder - would show ticket details)
    window.showSupportTicket = function(requestId) {
        alert('{{ __("Support ticket details would be displayed here") }}');
        // In a full implementation, this would fetch and display ticket details
    };
});
</script>
@endsection
