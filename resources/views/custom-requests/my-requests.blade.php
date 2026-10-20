@extends('layouts.user-no-nav')

@section('page_title', __('My Custom Requests'))

@section('content')
<div class="container-fluid px-4 py-5">
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center mb-4">
                <div class="mb-4 mb-lg-0">
                    <h1 class="display-4 font-weight-bold text-gradient mb-2">{{ __('My Custom Requests') }}</h1>
                    <p class="text-muted lead mb-0">{{ __('Manage all your custom requests in one place') }}</p>
                </div>
                <div class="d-flex flex-column flex-sm-row gap-2">
                    <button type="button" class="btn btn-primary btn-lg px-4" onclick="CustomRequest.showCreateModal()">
                        <i class="fas fa-plus-circle mr-2"></i>{{ __('Create Request') }}
                    </button>
                    <a href="{{ route('custom-requests.marketplace') }}" class="btn btn-outline-primary btn-lg px-4">
                        <i class="fas fa-store mr-2"></i>{{ __('Browse Marketplace') }}
                    </a>
                </div>
            </div>

            <!-- Enhanced Tabs Navigation -->
            <div class="card shadow-sm border-0 bg-light">
                <div class="card-body p-0">
                    <div class="d-flex flex-column flex-md-row">
                        <a class="nav-link-custom {{ $type == 'all' ? 'active' : '' }}"
                           href="{{ route('custom-requests.my-requests', ['type' => 'all']) }}">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-list mr-2"></i>
                                <span>{{ __('All Requests') }}</span>
                                @if($type == 'all')
                                    <span class="badge badge-primary ml-2">{{ $requests->total() }}</span>
                                @endif
                            </div>
                        </a>
                        <a class="nav-link-custom {{ $type == 'created' ? 'active' : '' }}"
                           href="{{ route('custom-requests.my-requests', ['type' => 'created']) }}">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-paper-plane mr-2"></i>
                                <span>{{ __('Created by Me') }}</span>
                            </div>
                        </a>
                        <a class="nav-link-custom {{ $type == 'received' ? 'active' : '' }}"
                           href="{{ route('custom-requests.my-requests', ['type' => 'received']) }}">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-inbox mr-2"></i>
                                <span>{{ __('Received') }}</span>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Requests List -->
    <div class="row" id="requestsContainer">
        @forelse($requests as $request)
            <div class="col-12 col-lg-6 mb-4 request-item"
                 data-status="{{ $request->status }}"
                 data-type="{{ $request->type }}"
                 data-created="{{ $request->created_at->timestamp }}">
                <div class="card h-100 shadow-hover border-0 request-card-modern">
                    <!-- Status Banner -->
                    <div class="status-banner status-{{ $request->status }}">
                        <div class="d-flex align-items-center justify-content-between p-3">
                            <div class="d-flex align-items-center">
                                @if($request->status == 'accepted')
                                    <i class="fas fa-check-circle text-white mr-2"></i>
                                    <span class="font-weight-bold text-white">{{ __('Active') }}</span>
                                @elseif($request->status == 'completed')
                                    <i class="fas fa-trophy text-white mr-2"></i>
                                    <span class="font-weight-bold text-white">{{ __('Completed') }}</span>
                                @elseif($request->status == 'rejected')
                                    <i class="fas fa-times-circle text-white mr-2"></i>
                                    <span class="font-weight-bold text-white">{{ __('Rejected') }}</span>
                                @elseif($request->status == 'cancelled')
                                    <i class="fas fa-ban text-white mr-2"></i>
                                    <span class="font-weight-bold text-white">{{ __('Cancelled') }}</span>
                                @else
                                    <i class="fas fa-clock text-white mr-2"></i>
                                    <span class="font-weight-bold text-white">{{ __('Pending') }}</span>
                                @endif
                            </div>
                            <div class="type-badge">
                                <span class="badge badge-light badge-pill px-3 py-1">
                                    @if($request->type == 'marketplace')
                                        <i class="fas fa-store mr-1"></i>{{ __('Marketplace') }}
                                    @elseif($request->type == 'private')
                                        <i class="fas fa-lock mr-1"></i>{{ __('Private') }}
                                    @else
                                        <i class="fas fa-globe mr-1"></i>{{ __('Public') }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <!-- Title and Time -->
                        <div class="mb-3">
                            <h5 class="card-title mb-2">
                                <a href="{{ route('custom-requests.show', $request->id) }}" class="text-dark text-decoration-none title-link">
                                    {{ Str::limit($request->title, 60) }}
                                </a>
                            </h5>
                            <div class="d-flex align-items-center text-muted small">
                                <i class="far fa-clock mr-1"></i>
                                {{ $request->created_at->diffForHumans() }}
                                @if($request->deadline)
                                    <span class="mx-2">•</span>
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    {{ __('Due') }}: {{ $request->deadline->format('M d, Y') }}
                                @endif
                            </div>
                        </div>

                        <!-- Description -->
                        <p class="card-text text-muted mb-3 description">
                            {{ Str::limit($request->description, 120) }}
                        </p>

                        <!-- Progress/Price Section -->
                        @if($request->is_marketplace)
                            <div class="progress-section mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-muted small font-weight-bold">{{ __('Funding Progress') }}</span>
                                    <span class="text-muted small">{{ number_format($request->progress_percentage, 1) }}%</span>
                                </div>
                                <div class="progress progress-modern mb-2">
                                    @php
                                        $progress = $request->goal_amount > 0 ? ($request->current_amount / $request->goal_amount) * 100 : 0;
                                        $progress = min(100, max(0, $progress));
                                    @endphp
                                    <div class="progress-bar progress-bar-modern" role="progressbar"
                                         style="width: {{ $progress }}%"
                                         aria-valuenow="{{ $progress }}"
                                         aria-valuemin="0"
                                         aria-valuemax="100">
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <small class="text-success font-weight-bold">
                                        ${{ number_format($request->current_amount, 0) }}
                                        <span class="text-muted">{{ __('raised') }}</span>
                                    </small>
                                    <small class="text-muted">
                                        ${{ number_format($request->goal_amount, 0) }}
                                        <span class="text-muted">{{ __('goal') }}</span>
                                    </small>
                                </div>
                            </div>
                        @else
                            <div class="price-section mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="price-icon mr-2">
                                        <i class="fas fa-dollar-sign text-success"></i>
                                    </div>
                                    <div>
                                        <span class="h5 text-success font-weight-bold mb-0">
                                            ${{ number_format($request->price, 2) }}
                                        </span>
                                        <small class="text-muted d-block">{{ __('Fixed Price') }}</small>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- User Info -->
                        <div class="user-info border-top pt-3 mb-3">
                            @if($request->requester_id == Auth::id())
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-sm mr-3">
                                        @if($request->creator->avatar)
                                            <img src="{{ asset('storage/' . $request->creator->avatar) }}" alt="{{ $request->creator->name }}" class="rounded-circle">
                                        @else
                                            <div class="avatar-placeholder-sm rounded-circle bg-primary text-white d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small text-muted">{{ __('Sent to Creator') }}</div>
                                        <div class="font-weight-bold">{{ $request->creator->name ?? 'Unknown' }}</div>
                                        <div class="small text-muted">@{{ $request->creator->username ?? '' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <i class="fas fa-arrow-right text-primary"></i>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar-sm mr-3">
                                        @if($request->requester->avatar)
                                            <img src="{{ asset('storage/' . $request->requester->avatar) }}" alt="{{ $request->requester->name }}" class="rounded-circle">
                                        @else
                                            <div class="avatar-placeholder-sm rounded-circle bg-info text-white d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="small text-muted">{{ __('From Requester') }}</div>
                                        <div class="font-weight-bold">{{ $request->requester->name ?? 'Unknown' }}</div>
                                        <div class="small text-muted">{{ $request->requester->username ?? '' }}</div>
                                    </div>
                                    <div class="text-right">
                                        <i class="fas fa-arrow-left text-info"></i>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Action Buttons -->
                        <div class="action-buttons">
                            <div class="row g-2">
                                <div class="col-8">
                                    <a href="{{ route('custom-requests.show', $request->id) }}"
                                       class="btn btn-outline-primary btn-block btn-sm">
                                        <i class="fas fa-eye mr-1"></i>{{ __('View Details') }}
                                    </a>
                                </div>
                                <div class="col-4">
                                    <div class="dropdown">
                                        <button class="btn btn-outline-secondary btn-block btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="fas fa-ellipsis-h"></i>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-right">
                                            @if($request->requester_id == Auth::id() && in_array($request->status, ['pending', 'accepted']))
                                                <button class="dropdown-item text-danger" onclick="cancelRequest({{ $request->id }})">
                                                    <i class="fas fa-times mr-2"></i>{{ __('Cancel Request') }}
                                                </button>
                                            @endif
                                            @if($request->creator_id == Auth::id() && $request->status == 'pending')
                                                <button class="dropdown-item text-success" onclick="handleRequestAction('accept', {{ $request->id }})">
                                                    <i class="fas fa-check mr-2"></i>{{ __('Accept') }}
                                                </button>
                                                <button class="dropdown-item text-warning" onclick="handleRequestAction('reject', {{ $request->id }})">
                                                    <i class="fas fa-times mr-2"></i>{{ __('Reject') }}
                                                </button>
                                            @endif
                                            @if($request->creator_id == Auth::id() && $request->status == 'accepted')
                                                <button class="dropdown-item text-primary" onclick="handleRequestAction('complete', {{ $request->id }})">
                                                    <i class="fas fa-check-circle mr-2"></i>{{ __('Mark Complete') }}
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="empty-state-icon mb-4">
                            <i class="fas fa-inbox fa-4x text-muted"></i>
                        </div>
                        <h4 class="text-muted mb-3">{{ __('No custom requests found') }}</h4>
                        <p class="text-muted mb-4">
                            @if($type == 'created')
                                {{ __('You haven\'t created any custom requests yet. Start by creating your first request!') }}
                            @elseif($type == 'received')
                                {{ __('You haven\'t received any custom requests yet. Your requests will appear here when creators respond.') }}
                            @else
                                {{ __('You haven\'t created or received any custom requests yet. Start your journey now!') }}
                            @endif
                        </p>
                        <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                            <button type="button" class="btn btn-primary btn-lg px-4" onclick="CustomRequest.showCreateModal()">
                                <i class="fas fa-plus-circle mr-2"></i>{{ __('Create Request') }}
                            </button>
                            <a href="{{ route('custom-requests.marketplace') }}" class="btn btn-outline-primary btn-lg px-4">
                                <i class="fas fa-store mr-2"></i>{{ __('Browse Marketplace') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($requests->hasPages())
        <div class="row mt-5">
            <div class="col-12">
                <div class="d-flex justify-content-center">
                    <div class="pagination-wrapper">
                        {{ $requests->links() }}
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<style>
/* Text Gradient */
.text-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Enhanced Navigation */
.nav-link-custom {
    padding: 1rem 1.5rem;
    color: #6c757d;
    text-decoration: none;
    border-radius: 8px 8px 0 0;
    transition: all 0.3s ease;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    border-bottom: 3px solid transparent;
}

.nav-link-custom:hover {
    color: #667eea;
    background-color: rgba(102, 126, 234, 0.1);
    border-bottom-color: rgba(102, 126, 234, 0.3);
}

.nav-link-custom.active {
    color: #667eea;
    background-color: #fff;
    border-bottom-color: #667eea;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nav-link-custom .badge {
    font-size: 0.75rem;
}

/* Status Banners */
.status-banner {
    border-radius: 15px 15px 0 0;
    position: relative;
    overflow: hidden;
}

.status-accepted {
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
}

.status-completed {
    background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
}

.status-rejected {
    background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
}

.status-cancelled {
    background: linear-gradient(135deg, #a0aec0 0%, #718096 100%);
}

.status-pending {
    background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
}

.type-badge .badge {
    background-color: rgba(255, 255, 255, 0.2);
    border: 1px solid rgba(255, 255, 255, 0.3);
}

/* Request Cards */
.request-card-modern {
    border-radius: 15px;
    transition: all 0.3s ease;
    overflow: hidden;
}

.shadow-hover:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
}

.title-link:hover {
    color: #667eea !important;
    text-decoration: none;
}

.description {
    line-height: 1.6;
}

/* Progress Bar */
.progress-modern {
    height: 10px;
    border-radius: 10px;
    background-color: #e2e8f0;
    overflow: hidden;
}

.progress-bar-modern {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
    transition: width 0.6s ease;
}

/* Price Section */
.price-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

/* User Avatars */
.user-avatar-sm img,
.avatar-placeholder-sm {
    width: 40px;
    height: 40px;
}

.avatar-placeholder-sm {
    font-size: 1rem;
}

/* Action Buttons */
.action-buttons .btn {
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.action-buttons .btn:hover {
    transform: translateY(-2px);
}

.action-buttons .dropdown-menu {
    border: none;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    border-radius: 10px;
}

.action-buttons .dropdown-item {
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
}

.action-buttons .dropdown-item:hover {
    background-color: #f8f9fa;
}

.action-buttons .dropdown-item.text-success:hover {
    background-color: rgba(72, 187, 120, 0.1);
}

.action-buttons .dropdown-item.text-warning:hover {
    background-color: rgba(237, 137, 54, 0.1);
}

.action-buttons .dropdown-item.text-danger:hover {
    background-color: rgba(245, 101, 101, 0.1);
}

/* Pagination */
.pagination-wrapper .page-link {
    border-radius: 8px !important;
    border: none;
    color: #667eea;
    font-weight: 500;
}

.pagination-wrapper .page-item.active .page-link {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

/* Empty State */
.empty-state-icon {
    opacity: 0.5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .display-4 {
        font-size: 2rem;
    }

    .nav-link-custom {
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .status-banner .d-flex {
        flex-direction: column;
        align-items-start !important;
        gap: 0.5rem;
    }

    .type-badge {
        align-self: flex-end;
    }

    .action-buttons .row {
        gap: 0.5rem;
    }

    .user-info .d-flex {
        flex-direction: column;
        text-align: center;
    }

    .user-avatar-sm {
        align-self: center;
        margin-bottom: 0.5rem;
        margin-right: 0 !important;
    }
}

@media (max-width: 576px) {
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .btn-lg {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }

    .card-body {
        padding: 1rem !important;
    }
}

/* Loading Animation */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.request-item {
    animation: fadeInUp 0.6s ease-out;
}

/* Hidden items for filtering */
.request-item.hidden {
    display: none;
}
</style>

<script>
// Request action handlers
function cancelRequest(requestId) {
    if (confirm('{{ __("Are you sure you want to cancel this request?") }}')) {
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
}

function handleRequestAction(action, requestId) {
    const actionMessages = {
        'accept': '{{ __("Accepting request...") }}',
        'reject': '{{ __("Rejecting request...") }}',
        'complete': '{{ __("Marking as completed...") }}'
    };

    const actionText = actionMessages[action] || '{{ __("Processing...") }}';

    // Find and disable the button
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>' + actionText;

    fetch(`/custom-requests/${requestId}/${action}`, {
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
            const successMessages = {
                'accept': '{{ __("Request accepted successfully!") }}',
                'reject': '{{ __("Request rejected successfully!") }}',
                'complete': '{{ __("Request marked as completed successfully!") }}'
            };
            const successMsg = data.message || successMessages[action] || '{{ __("Action completed successfully!") }}';

            if (typeof launchToast !== 'undefined') {
                launchToast('success', '{{ __("Success") }}', successMsg);
            } else {
                alert(successMsg);
            }

            setTimeout(() => location.reload(), 1000);
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
        const errorMsg = '{{ __("An error occurred. Please try again.") }}';
        if (typeof launchToast !== 'undefined') {
            launchToast('danger', '{{ __("Error") }}', errorMsg);
        } else {
            alert(errorMsg);
        }
        button.disabled = false;
        button.innerHTML = originalText;
    });
}
</script>
@endsection
