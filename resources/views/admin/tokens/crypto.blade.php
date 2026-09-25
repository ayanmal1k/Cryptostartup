@extends('layouts.app')

@section('title', 'Custom Requests Marketplace')

@section('content')
<div class="container py-5">

    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- Main Card -->
            <div class="card shadow-lg border-0">
                <div class="card-body p-5 text-center">

                    <div class="mb-4">
                        <i class="fas fa-hand-paper fa-3x text-primary"></i>
                    </div>

                    <h2 class="fw-bold mb-3">
                        Custom Requests Marketplace
                    </h2>

                    <p class="text-muted mb-4">
                        Create, browse, and participate in custom requests submitted by the community.
                        Support ideas, vote on proposals, and track progress transparently.
                    </p>

                    <div class="d-flex justify-content-center gap-3 flex-wrap">

                        <a href="{{ route('custom-requests.marketplace') }}"
                           class="btn btn-outline-primary btn-lg">
                            <i class="fas fa-store me-2"></i>
                            Browse Requests
                        </a>
                        @guest
                            <a href="{{ route('login') }}"
                               class="btn btn-primary btn-lg">
                                <i class="fas fa-sign-in-alt me-2"></i>
                                Login to Create Request
                            </a>
                        @endauth

                    </div>

                </div>
            </div>

            <!-- Info Section -->
            <div class="row text-center mt-5">
                <div class="col-md-4 mb-4">
                    <div class="p-4 border rounded h-100">
                        <i class="fas fa-lightbulb fa-2x text-warning mb-3"></i>
                        <h6 class="fw-bold">Submit Ideas</h6>
                        <p class="text-muted small mb-0">
                            Share your custom request and let others support it.
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="p-4 border rounded h-100">
                        <i class="fas fa-vote-yea fa-2x text-info mb-3"></i>
                        <h6 class="fw-bold">Vote & Support</h6>
                        <p class="text-muted small mb-0">
                            Vote on requests and contribute towards completion.
                        </p>
                    </div>
                </div>

                <div class="col-md-4 mb-4">
                    <div class="p-4 border rounded h-100">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <h6 class="fw-bold">Track Progress</h6>
                        <p class="text-muted small mb-0">
                            Follow request status from creation to completion.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>
@endsection
