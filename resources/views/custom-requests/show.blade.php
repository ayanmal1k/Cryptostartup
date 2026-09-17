@extends('layouts.user-no-nav')

@section('page_title', $customRequest->title)

@section('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@700;800&display=swap">
    <link rel="stylesheet" href="{{ asset('css/pages/discover.css') }}?v=20260917a">
    <link rel="stylesheet" href="{{ asset('css/pages/challenge-detail.css') }}?v=20260917a">
@endsection

@php
    $cr = $customRequest;
    $me = Auth::id();
    $isCreator = $me && (int) $cr->creator_id === (int) $me;
    $isRequester = $me && (int) $cr->requester_id === (int) $me;

    $goal = (float) $cr->goal_amount;
    $raised = (float) $cr->current_amount;
    $pct = $goal > 0 ? (int) min(100, floor($raised / $goal * 100)) : 0;
    $left = max(0, $goal - $raised);

    $backing = $cr->contributions->where('status', 'completed')->sortByDesc('created_at');
    $backers = $backing->pluck('contributor_id')->unique()->count();

    $videoUrl = $cr->delivery_video_url;
    $thumbUrl = $cr->delivery_thumbnail_url;

    // One status vocabulary for the whole page. Tone maps to the design reference:
    // green = done/success, gold = money is moving, red = a dead end.
    $status = [
        'pending'   => ['label' => __('Waiting on the creator'), 'tone' => 'muted'],
        'accepted'  => $cr->is_marketplace
            ? ['label' => __('Open for backing'), 'tone' => 'money']
            : ['label' => __('In progress'), 'tone' => 'money'],
        'completed' => ['label' => __('Challenge done'), 'tone' => 'win'],
        'rejected'  => ['label' => __('Declined'), 'tone' => 'hot'],
        'cancelled' => ['label' => __('Cancelled'), 'tone' => 'muted'],
    ][$cr->status] ?? ['label' => ucfirst($cr->status), 'tone' => 'muted'];

    $type = [
        'marketplace' => __('Community challenge'),
        'private'     => __('Private request'),
        'public'      => __('Public request'),
    ][$cr->type] ?? ($cr->is_marketplace ? __('Community challenge') : __('Request'));

    $deadlineNote = null;
    if ($cr->deadline && $cr->status === 'accepted') {
        $deadlineNote = $cr->deadline->isPast() ? 'overdue' : ($cr->deadline->diffInDays() <= 7 ? 'soon' : null);
    }

    $money = fn ($v, $d = 0) => '$' . number_format((float) $v, $d);
    $canChipIn = $cr->is_marketplace && $cr->status === 'accepted' && !$isCreator;
@endphp

@section('content')
<div class="dx cd">
<div class="cd-wrap">

    {{-- ============ Stage: the video, or where it will land ============ --}}
    <section class="cd-stage {{ $videoUrl ? 'has-video' : '' }}">
        @if($videoUrl)
            <video src="{{ $videoUrl }}" @if($thumbUrl) poster="{{ $thumbUrl }}" @endif
                   controls playsinline preload="metadata"></video>
        @else
            <div class="cd-stage__empty">
                <span class="cd-stage__icon" aria-hidden="true">
                    @if($cr->status === 'completed')
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5 10 17 19 7"/></svg>
                    @elseif(in_array($cr->status, ['rejected', 'cancelled']))
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    @endif
                </span>
                <p class="cd-stage__line">
                    @if($cr->status === 'completed')
                        {{ __('Done — no video was posted for this one.') }}
                    @elseif(in_array($cr->status, ['rejected', 'cancelled']))
                        {{ __('This challenge won\'t be happening.') }}
                    @else
                        {{ __('The video lands here when :name does it.', ['name' => $cr->creator->name ?? __('the creator')]) }}
                    @endif
                </p>
            </div>
        @endif
    </section>

    <div class="cd-grid">
    <div class="cd-col">

        {{-- ============ Title ============ --}}
        <header class="cd-head">
            <div class="cd-chips">
                <span class="cd-status cd-status--{{ $status['tone'] }}">
                    <span class="cd-status__dot" aria-hidden="true"></span>{{ $status['label'] }}
                </span>
                <span class="cd-type">{{ $type }}</span>
            </div>
            <h1 class="cd-title">{{ $cr->title }}</h1>
            <p class="cd-meta">
                <span>{{ __('Posted') }} {{ $cr->created_at->diffForHumans() }}</span>
                @if($cr->deadline)
                    <span class="cd-meta__due {{ $deadlineNote ? 'is-' . $deadlineNote : '' }}">
                        · {{ __('Due') }} {{ $cr->deadline->format('M j, Y') }}
                        @if($deadlineNote === 'overdue') ({{ __('overdue') }}) @elseif($deadlineNote === 'soon') ({{ __('soon') }}) @endif
                    </span>
                @endif
            </p>
        </header>

        {{-- ============ Who ============ --}}
        <div class="cd-people">
            @if($cr->creator)
                <a href="{{ route('profile', ['username' => $cr->creator->username]) }}" class="cd-person">
                    <img src="{{ $cr->creator->avatar }}" alt="" class="dx-avatar">
                    <span class="cd-person__text">
                        <span class="cd-person__role">{{ __('Creator') }}</span>
                        <span class="cd-person__name">{{ $cr->creator->name }}</span>
                    </span>
                </a>
            @endif
            @if($cr->requester)
                <a href="{{ route('profile', ['username' => $cr->requester->username]) }}" class="cd-person">
                    <img src="{{ $cr->requester->avatar }}" alt="" class="dx-avatar">
                    <span class="cd-person__text">
                        <span class="cd-person__role">{{ __('Dared by') }}</span>
                        <span class="cd-person__name">{{ $cr->requester->name }}</span>
                    </span>
                </a>
            @endif
        </div>

        {{-- ============ The dare ============ --}}
        @if(trim((string) $cr->description) !== '')
            <section class="cd-card">
                <h2 class="cd-card__title">{{ __('The dare') }}</h2>
                <p class="cd-story">{{ $cr->description }}</p>
            </section>
        @endif

        {{-- ============ Voting & fund release ============ --}}
        @if($cr->requires_voting && in_array($cr->status, ['accepted', 'completed']))
            @php $approval = (float) ($cr->approval_percentage ?? 0); @endphp
            <section class="cd-card">
                <div class="cd-card__head">
                    <h2 class="cd-card__title">{{ __('Backers decide the payout') }}</h2>
                    <span class="cd-card__aside">{{ number_format($approval, 0) }}% {{ __('approve') }}</span>
                </div>
                <div class="cd-vote-bar" role="progressbar" aria-valuenow="{{ $approval }}" aria-valuemin="0" aria-valuemax="100">
                    <span class="cd-vote-bar__yes" style="width: {{ min(100, $approval) }}%"></span>
                </div>
                <div class="cd-vote-counts">
                    <span><strong class="is-win">{{ $cr->approval_votes ?? 0 }}</strong> {{ __('approve') }}</span>
                    <span><strong class="is-hot">{{ $cr->rejection_votes ?? 0 }}</strong> {{ __('reject') }}</span>
                    <span class="cd-vote-counts__rule">{{ $cr->hasMajorityApproval() ? __('Majority reached — funds can be released.') : __('Needs over 50% to release funds.') }}</span>
                </div>

                @auth
                    @if($cr->canUserVote(Auth::id()) && !$cr->hasUserVoted(Auth::id()))
                        <div class="cd-actions cd-actions--split">
                            <button type="button" class="dx-btn cd-btn-win" onclick="castVote({{ $cr->id }}, 'approve')">{{ __('Approve release') }}</button>
                            <button type="button" class="dx-btn dx-btn--ghost" onclick="castVote({{ $cr->id }}, 'reject')">{{ __('Reject') }}</button>
                        </div>
                    @elseif($cr->hasUserVoted(Auth::id()))
                        @php $userVote = $cr->getUserVote(Auth::id()); @endphp
                        <p class="cd-note">
                            {{ __('You voted') }} <strong>{{ ucfirst($userVote->vote_type) }}</strong>
                            @if($userVote->comment) — “{{ $userVote->comment }}” @endif
                        </p>
                    @endif

                    @if($isCreator && $cr->canReleaseFunds() && !$cr->funds_released)
                        <div class="cd-actions">
                            <button type="button" class="dx-btn dx-btn--money" onclick="releaseFunds({{ $cr->id }})">{{ __('Release my funds') }}</button>
                        </div>
                    @endif
                @endauth

                @if($cr->votes && $cr->votes->count())
                    <ul class="cd-list">
                        @foreach($cr->votes as $vote)
                            <li class="cd-list__row">
                                <span class="cd-list__main">
                                    <strong>{{ $vote->voter->name ?? __('Unknown') }}</strong>
                                    @if($vote->is_requester) <span class="cd-tag">{{ __('Requester') }}</span> @endif
                                    @if($vote->is_contributor) <span class="cd-tag">{{ __('Backer') }}</span> @endif
                                    @if($vote->comment) <span class="cd-list__msg">“{{ $vote->comment }}”</span> @endif
                                </span>
                                <span class="cd-list__side {{ $vote->vote_type === 'approve' ? 'is-win' : ($vote->vote_type === 'reject' ? 'is-hot' : '') }}">
                                    {{ $vote->vote_type === 'approve' ? __('Approved') : ($vote->vote_type === 'reject' ? __('Rejected') : __('Abstained')) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        @endif

        {{-- ============ Backers ============ --}}
        @if($cr->is_marketplace)
            <section class="cd-card">
                <div class="cd-card__head">
                    <h2 class="cd-card__title">{{ __('Backers') }}</h2>
                    @if($backers)
                        <span class="cd-card__aside">{{ trans_choice('{1} :count fan|[2,*] :count fans', $backers, ['count' => $backers]) }}</span>
                    @endif
                </div>
                @if($backing->count())
                    <ul class="cd-list">
                        @foreach($backing->take(20) as $contribution)
                            <li class="cd-list__row">
                                @if($contribution->contributor)
                                    <img src="{{ $contribution->contributor->avatar }}" alt="" class="dx-avatar">
                                @endif
                                <span class="cd-list__main">
                                    <strong>{{ $contribution->contributor->name ?? __('A fan') }}</strong>
                                    <span class="cd-list__time">{{ $contribution->created_at->diffForHumans() }}</span>
                                    @if($contribution->message)
                                        <span class="cd-list__msg">“{{ Str::limit($contribution->message, 160) }}”</span>
                                    @endif
                                </span>
                                <span class="cd-list__side is-money">{{ $money($contribution->amount, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($backing->count() > 20)
                        <p class="cd-note">{{ __('And :count more backers.', ['count' => $backing->count() - 20]) }}</p>
                    @endif
                @else
                    <p class="cd-note">
                        {{ $canChipIn ? __('No backers yet. Be the first to put money on it.') : __('No one has backed this yet.') }}
                    </p>
                @endif
            </section>
        @endif
    </div>

    {{-- ============ Money + actions ============ --}}
    <aside class="cd-side">
        <section class="cd-card cd-money">
            @if($cr->is_marketplace && $goal > 0)
                <div class="cd-money__amounts">
                    <span class="cd-money__raised">{{ $money($raised) }}</span>
                    <span class="cd-money__goal">{{ __('raised of') }} {{ $money($goal) }}</span>
                </div>
                <div class="dx-progress cd-money__bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="dx-progress__fill" style="width: {{ $pct }}%"></div>
                </div>
                <dl class="cd-money__stats">
                    <div><dt>{{ __('Funded') }}</dt><dd>{{ $pct }}%</dd></div>
                    <div><dt>{{ __('Backers') }}</dt><dd>{{ number_format($backers) }}</dd></div>
                    <div><dt>{{ $left > 0 ? __('To go') : __('Goal') }}</dt><dd>{{ $left > 0 ? $money($left) : __('Hit') }}</dd></div>
                </dl>
            @else
                <div class="cd-money__amounts">
                    <span class="cd-money__raised">{{ $money($cr->price, 2) }}</span>
                    <span class="cd-money__goal">{{ __('fixed price') }}</span>
                </div>
                @if((float) $cr->upfront_payment > 0)
                    <p class="cd-note">{{ $money($cr->upfront_payment, 2) }} {{ __('paid up front') }}{{ $cr->payment_received ? ' · ' . __('received') : '' }}</p>
                @endif
            @endif

            {{-- Primary action for whoever is looking --}}
            <div class="cd-actions">
                @auth
                    @if($isRequester && (float) $cr->upfront_payment > 0 && !$cr->payment_received)
                        <button type="button" class="dx-btn dx-btn--money dx-btn--block" onclick="processUpfrontPayment({{ $cr->id }})">
                            {{ __('Pay :amount to start', ['amount' => $money($cr->upfront_payment, 2)]) }}
                        </button>
                    @endif

                    @if($isCreator)
                        @if($cr->status === 'pending')
                            <button type="button" class="dx-btn dx-btn--primary dx-btn--block" onclick="handleRequestAction('accept', {{ $cr->id }})">{{ __('Accept the challenge') }}</button>
                            <button type="button" class="dx-btn dx-btn--ghost dx-btn--block" onclick="handleRequestAction('reject', {{ $cr->id }})">{{ __('Decline') }}</button>
                        @elseif($cr->status === 'accepted')
                            <button type="button" class="dx-btn cd-btn-win dx-btn--block" onclick="handleRequestAction('complete', {{ $cr->id }})">{{ __('Mark as done') }}</button>
                        @endif
                        @if(in_array($cr->status, ['pending', 'accepted']))
                            <button type="button" class="cd-link-btn" onclick="cancelRequest({{ $cr->id }})">{{ __('Cancel this challenge') }}</button>
                        @endif
                    @elseif($canChipIn)
                        <button type="button" class="dx-btn dx-btn--money dx-btn--block contribute-btn" data-request-id="{{ $cr->id }}">
                            {{ $left > 0 ? __('Chip in') : __('Chip in anyway') }}
                        </button>
                        <p class="cd-note cd-note--center">{{ __('Every backer helps make it happen.') }}</p>
                    @elseif($isRequester && in_array($cr->status, ['pending', 'accepted']))
                        <p class="cd-note">{{ __('You dared this. :name will respond soon.', ['name' => $cr->creator->name ?? __('The creator')]) }}</p>
                        @if($cr->status === 'pending')
                            <button type="button" class="cd-link-btn" onclick="cancelRequest({{ $cr->id }})">{{ __('Withdraw the dare') }}</button>
                        @endif
                    @elseif($cr->status === 'completed')
                        <p class="cd-note cd-note--center">
                            {{ $backers ? trans_choice('{1} Made happen by :count fan.|[2,*] Made happen by :count fans.', $backers, ['count' => $backers]) : __('This challenge is done.') }}
                        </p>
                    @endif
                @else
                    @if($canChipIn)
                        <a href="{{ route('login') }}" class="dx-btn dx-btn--money dx-btn--block">{{ __('Log in to chip in') }}</a>
                    @endif
                @endauth
            </div>
        </section>

        <nav class="cd-card cd-more" aria-label="{{ __('More') }}">
            <a href="{{ route('custom-requests.marketplace') }}" class="cd-more__link">
                <span>{{ __('Watch more challenges') }}</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
            </a>
            @auth
                <a href="{{ route('custom-requests.my-requests') }}" class="cd-more__link">
                    <span>{{ __('My requests') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                </a>
                <button type="button" class="cd-more__link" onclick="CustomRequest.showCreateModal()">
                    <span>{{ __('Dare a creator') }}</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                </button>
                @if($cr->has_support_ticket)
                    <button type="button" class="cd-more__link" onclick="showSupportTicket({{ $cr->id }})">
                        <span>{{ __('View your support ticket') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                @else
                    <button type="button" class="cd-more__link" onclick="showCreateSupportTicketModal({{ $cr->id }})">
                        <span>{{ __('Report a problem') }}</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>
                    </button>
                @endif
            @endauth
        </nav>
    </aside>
    </div>

</div>
</div>

@auth
{{-- Contribution sheet. Keeps #contributeModal / #contributeForm / #request_id / #amount /
     #message, which the script below drives. --}}
<div class="modal fade cd-modal" id="contributeModal" tabindex="-1" aria-labelledby="contributeTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="contributeForm">
                <div class="cd-modal__head">
                    <div>
                        <span class="cd-modal__eyebrow">{{ __('Chip in') }}</span>
                        <h2 class="cd-modal__title" id="contributeTitle">{{ $cr->title }}</h2>
                    </div>
                    <button type="button" class="cd-modal__close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="{{ __('Close') }}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </button>
                </div>

                @if($goal > 0)
                    <div class="cd-modal__progress">
                        <div class="dx-progress"><div class="dx-progress__fill" style="width: {{ $pct }}%"></div></div>
                        <span>{{ $money($raised) }} {{ __('of') }} {{ $money($goal) }}{{ $left > 0 ? ' · ' . $money($left) . ' ' . __('to go') : '' }}</span>
                    </div>
                @endif

                <input type="hidden" id="request_id" name="request_id">

                <div class="cd-modal__amount">
                    <span aria-hidden="true">$</span>
                    <input type="number" id="amount" name="amount" step="0.01" min="0.01" required
                           inputmode="decimal" placeholder="0" aria-label="{{ __('Amount') }}">
                </div>
                <div class="cd-modal__quick" role="group" aria-label="{{ __('Quick amounts') }}">
                    @foreach([5, 10, 25, 50] as $q)
                        <button type="button" data-amount="{{ $q }}">${{ $q }}</button>
                    @endforeach
                    @if($left > 0 && !in_array((int) ceil($left), [5, 10, 25, 50]))
                        <button type="button" data-amount="{{ number_format($left, 2, '.', '') }}">{{ __('Finish it') }} · {{ $money($left) }}</button>
                    @endif
                </div>

                <label class="cd-modal__label" for="message">{{ __('Say something') }} <span>{{ __('optional · shown publicly') }}</span></label>
                <textarea id="message" name="message" rows="2" maxlength="500" placeholder="{{ __('Hype them up…') }}"></textarea>

                <div class="cd-modal__foot">
                    <button type="submit" class="dx-btn dx-btn--money dx-btn--block">{{ __('Put it in') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.cd-modal__quick [data-amount]').forEach(function (b) {
        b.addEventListener('click', function () {
            var input = document.getElementById('amount');
            input.value = b.dataset.amount;
            document.querySelectorAll('.cd-modal__quick [data-amount]').forEach(function (o) { o.classList.toggle('is-active', o === b); });
            input.focus();
        });
    });
});
</script>
@endauth

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

        // "Chip in" on the browse page links here with #contribute — open the form straight away.
        if (window.location.hash === '#contribute') {
            contributeBtn.click();
        }

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
