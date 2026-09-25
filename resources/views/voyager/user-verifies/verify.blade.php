@extends('voyager::master')

@section('page_title', 'Verify User Identity')

@section('page_header')
    <div class="container-fluid jf-dash-page-header">
        <div class="jf-dash-page-header__inner">
            <div class="jf-dash-page-header__brand">
                <div class="jf-dash-page-header__icon" aria-hidden="true">
                    <i class="voyager-check"></i>
                </div>
                <div class="jf-dash-page-header__text">
                    <h1 class="jf-dash-page-header__title">Verify User Identity</h1>
                </div>
            </div>
            <div class="jf-dash-page-header__actions">
                <a href="{{ url('/admin/user-verifies') }}" class="jf-dash-btn jf-dash-btn--blue">
                    <i class="voyager-angle-left"></i>
                    <span class="jf-pill-label">Back to List</span>
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="page-content container-fluid jf-dash-page jf-approvals-page jf-approvals-verify-page">
        @include('voyager::alerts')

        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--approvals-info">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--blue"><i class="voyager-person"></i></span>
                            User Information
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <dl class="jf-approvals-details">
                            <div class="jf-approvals-details__row">
                                <dt>Name</dt>
                                <dd>{{ $userVerify->user->name ?? 'N/A' }}</dd>
                            </div>
                            <div class="jf-approvals-details__row">
                                <dt>Email</dt>
                                <dd>{{ $userVerify->user->email ?? 'N/A' }}</dd>
                            </div>
                            <div class="jf-approvals-details__row">
                                <dt>Username</dt>
                                <dd>{{ $userVerify->user->username ?? 'N/A' }}</dd>
                            </div>
                            <div class="jf-approvals-details__row">
                                <dt>User ID</dt>
                                <dd>{{ $userVerify->user_id }}</dd>
                            </div>
                            <div class="jf-approvals-details__row">
                                <dt>Status</dt>
                                <dd>
                                    @if($userVerify->status == 'pending')
                                        <span class="jf-token-badge jf-token-badge--warning">Pending</span>
                                    @elseif($userVerify->status == 'verified')
                                        <span class="jf-token-badge jf-token-badge--success">Verified</span>
                                    @elseif($userVerify->status == 'rejected')
                                        <span class="jf-token-badge jf-token-badge--danger">Rejected</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="jf-approvals-details__row">
                                <dt>Submitted</dt>
                                <dd>{{ $userVerify->created_at->format('F d, Y g:i A') }}</dd>
                            </div>
                            @if($userVerify->rejectionReason)
                            <div class="jf-approvals-details__row">
                                <dt>Rejection Reason</dt>
                                <dd>{{ $userVerify->rejectionReason }}</dd>
                            </div>
                            @endif
                        </dl>

                        <a href="{{ route('profile', ['username' => $userVerify->user->username ?? '']) }}"
                           target="_blank"
                           class="jf-dash-btn jf-dash-btn--blue jf-approvals-profile-btn">
                            <i class="voyager-person"></i>
                            <span class="jf-pill-label">View User Profile</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--approvals-docs">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--purple"><i class="voyager-images"></i></span>
                            ID Documents
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        @php
                            $files = json_decode($userVerify->files, true);
                        @endphp

                        @if($files && count($files) > 0)
                            <div class="jf-approvals-docs">
                                @foreach($files as $fileId)
                                    @php
                                        $attachment = \App\Model\Attachment::find($fileId);
                                    @endphp
                                    @if($attachment)
                                        <button type="button" class="jf-approvals-doc" onclick="window.open('{{ $attachment->path }}', '_blank')">
                                            <img src="{{ $attachment->path }}" alt="ID Document" class="jf-approvals-doc__img">
                                            <span class="jf-approvals-doc__label">{{ $attachment->attachmentType ?? 'Document' }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="jf-dash-card__empty">No documents uploaded.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered jf-dash-card jf-dash-card--approvals-actions">
                    <div class="panel-heading jf-dash-card__head">
                        <h3 class="panel-title jf-dash-card__title">
                            <span class="jf-dash-card__title-icon jf-dash-card__title-icon--green"><i class="voyager-check"></i></span>
                            Verification Actions
                        </h3>
                    </div>
                    <div class="panel-body jf-dash-card__body">
                        <form action="{{ route('voyager.user-verifies.update-status', $userVerify->id) }}" method="POST" class="jf-approvals-form">
                            @csrf
                            @method('PUT')

                            <div class="jf-approvals-form__field">
                                <label for="status">Status</label>
                                <select name="status" id="status" class="form-control jf-approvals-form__select" required>
                                    <option value="pending" {{ $userVerify->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="verified" {{ $userVerify->status == 'verified' ? 'selected' : '' }}>Verified</option>
                                    <option value="rejected" {{ $userVerify->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                            </div>

                            <div class="jf-approvals-form__field" id="rejection-reason-group" style="display: none;">
                                <label for="rejectionReason">Rejection Reason</label>
                                <textarea name="rejectionReason"
                                          id="rejectionReason"
                                          class="form-control jf-approvals-form__textarea"
                                          rows="3"
                                          placeholder="Please provide a reason for rejection...">{{ $userVerify->rejectionReason }}</textarea>
                            </div>

                            <div class="jf-approvals-form__actions">
                                <button type="submit" class="jf-dash-btn jf-dash-btn--green">
                                    <i class="voyager-check"></i>
                                    <span class="jf-pill-label">Update Status</span>
                                </button>
                                <a href="{{ url('/admin/user-verifies') }}" class="jf-dash-btn jf-dash-btn--blue">
                                    <span class="jf-pill-label">Cancel</span>
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('javascript')
<script>
    $(document).ready(function() {
        $('#status').on('change', function() {
            if ($(this).val() === 'rejected') {
                $('#rejection-reason-group').slideDown();
                $('#rejectionReason').prop('required', true);
            } else {
                $('#rejection-reason-group').slideUp();
                $('#rejectionReason').prop('required', false);
            }
        });
        $('#status').trigger('change');
    });
</script>
@stop
