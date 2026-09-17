<?php

namespace App\Http\Controllers;

use App\Models\CustomRequest;
use App\Models\CustomRequestContribution;
use App\Models\CustomRequestVote;
use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Helpers\PaymentHelper;
use App\Model\Transaction;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CustomRequestController extends Controller
{
    /**
     * Display marketplace of custom requests
     */
    public function marketplace(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $tab = (string) $request->query('tab', 'watch');
        $tab = $tab === 'funded' ? 'top' : $tab; // old links used ?tab=funded
        $tab = in_array($tab, ['watch', 'open', 'top'], true) ? $tab : 'watch';

        $openStatuses = [CustomRequest::STATUS_PENDING, CustomRequest::STATUS_ACCEPTED];

        // Rejected and cancelled requests are dead ends, so browsing never shows them.
        $base = fn () => CustomRequest::where('is_marketplace', true)
            ->whereNotIn('status', [CustomRequest::STATUS_CANCELLED, CustomRequest::STATUS_REJECTED])
            ->with('creator')
            ->withCount(['contributions as backers_count' => function ($q) {
                $q->select(DB::raw('count(distinct contributor_id)'));
            }]);

        $query = $base();
        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('title', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            });
        } elseif ($tab === 'open') {
            // Closest to the goal first: those are the ones one more backer tips over.
            $query->whereIn('status', $openStatuses)
                ->orderByRaw('CASE WHEN goal_amount > 0 THEN current_amount / goal_amount ELSE 0 END DESC');
        } elseif ($tab === 'top') {
            $query->orderByDesc('current_amount');
        } else {
            // Watch: delivered challenge videos, newest first.
            $query->whereNotNull('delivery_video_path')
                ->where('status', CustomRequest::STATUS_COMPLETED)
                ->orderByDesc('delivered_at');
        }
        $requests = $query->orderByDesc('created_at')->paginate(24)->withQueryString();

        $marketplace = fn () => CustomRequest::where('is_marketplace', true)
            ->whereNotIn('status', [CustomRequest::STATUS_CANCELLED, CustomRequest::STATUS_REJECTED]);

        // "Raised" sums the same current_amount every card shows, so the headline and the
        // cards can never disagree (the contributions ledger is empty for seeded rows).
        $stats = [
            'raised' => (float) $marketplace()->sum('current_amount'),
            'watch' => $marketplace()->whereNotNull('delivery_video_path')
                ->where('status', CustomRequest::STATUS_COMPLETED)->count(),
            'open' => $marketplace()->whereIn('status', $openStatuses)->count(),
            'backers' => CustomRequestContribution::distinct('contributor_id')->count('contributor_id'),
        ];

        return view('custom-requests.marketplace', compact(
            'requests', 'stats', 'search', 'tab'
        ));
    }

    /**
     * Display a single custom request
     */
    public function show($id)
    {
        $customRequest = CustomRequest::with(['creator', 'requester', 'contributions.contributor'])
            ->findOrFail($id);

        // Security check: Only allow viewing if user is the creator, requester, or it's a marketplace/public request
        if (Auth::check()) {
            $userId = Auth::id();
            $isCreator = $customRequest->creator_id == $userId;
            $isRequester = $customRequest->requester_id == $userId;
            $isPublic = $customRequest->type == 'public' || $customRequest->is_marketplace;
            
            if (!$isCreator && !$isRequester && !$isPublic) {
                abort(403, 'You do not have permission to view this request.');
            }
        } elseif ($customRequest->type != 'public' && !$customRequest->is_marketplace) {
            // Private requests require authentication
            return redirect()->route('login');
        }

        return view('custom-requests.show', compact('customRequest'));
    }

    /**
     * Store a new custom request
     */
    public function store(Request $request)
    {
        // Ensure we always return JSON
        if (!$request->expectsJson() && !$request->ajax()) {
            $request->headers->set('Accept', 'application/json');
        }
        // Handle creator lookup by username or ID
        $creatorId = $request->input('creator_id');
        $creatorUsername = $request->input('creator_username');
        
        // If creator_id is not provided, try to find by username
        if (!$creatorId && $creatorUsername) {
            $username = trim($creatorUsername);
            // Remove @ if user typed it
            $username = ltrim($username, '@');
            
            if (empty($username)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please enter a creator username and select from the search results that appear below.'
                ], 422);
            }
            
            // Search using the same logic as the search function
            // Search by username, name, and bio (matching getSearchUsers logic)
            $query = User::where('public_profile', 1)
                ->where('role_id', 2) // Only creators
                ->where(function ($q) use ($username) {
                    $q->where('username', 'like', '%' . $username . '%')
                      ->orWhere('bio', 'like', '%' . $username . '%')
                      ->orWhere('name', 'like', '%' . $username . '%');
                });
            
            // Exclude current user from search if logged in
            if (Auth::check()) {
                $query->where('id', '<>', Auth::id());
            }
            
            $creator = $query->first();
            
            // If still not found, try without role_id restriction (in case role_id is not set correctly)
            if (!$creator) {
                $query2 = User::where('public_profile', 1)
                    ->where(function ($q) use ($username) {
                        $q->where('username', 'like', '%' . $username . '%')
                          ->orWhere('bio', 'like', '%' . $username . '%')
                          ->orWhere('name', 'like', '%' . $username . '%');
                    });
                
                // Exclude current user from search if logged in
                if (Auth::check()) {
                    $query2->where('id', '<>', Auth::id());
                }
                
                $creator = $query2->first();
            }
            
            if ($creator) {
                // Ensure user is not trying to request from themselves
                if ($creator->id == Auth::id()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You cannot create a custom request for yourself. Please select a different creator.'
                    ], 422);
                }
                $creatorId = $creator->id;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Creator not found. Please check the username and select from the search results that appear below.'
                ], 422);
            }
        }

        // Calculate upfront payment (minimum $1)
        $requestType = $request->input('type');
        $upfrontPayment = 0;
        
        if ($requestType === 'marketplace') {
            // For marketplace, upfront payment is minimum $1 or a percentage of goal
            $goalAmount = $request->input('goal_amount', 0);
            $upfrontPayment = max(1.00, $goalAmount * 0.10); // 10% of goal or $1, whichever is higher
        } else {
            // For private/public, upfront payment is the full price (minimum $1)
            $price = $request->input('price', 0);
            $upfrontPayment = max(1.00, $price);
        }

        // Validate all fields including creator_id and upfront payment
        $validator = Validator::make(array_merge($request->all(), [
            'creator_id' => $creatorId,
            'upfront_payment' => $upfrontPayment
        ]), [
            'creator_id' => 'required|exists:users,id',
            'type' => 'required|in:private,public,marketplace',
            'title' => 'required|string|max:255',
            // Was unbounded, so the modal's character counter had nothing to count toward
            // and an arbitrarily long description could be stored.
            'description' => 'required|string|max:2000',
            'price' => 'nullable|numeric|min:1',
            'goal_amount' => 'nullable|numeric|min:1',
            'upfront_payment' => 'required|numeric|min:1',
            // The form sets min="today" but that is only a browser hint — enforce it server-side
            // so a past deadline can't be submitted directly.
            'deadline' => 'nullable|date|after_or_equal:today',
            'message_id' => 'nullable|exists:user_messages,id',
        ], [
            'price.min' => 'Price must be at least $1.00',
            'goal_amount.min' => 'Goal amount must be at least $1.00',
            'description.max' => 'Description cannot be longer than 2000 characters.',
            'deadline.after_or_equal' => 'The deadline cannot be in the past.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        if (!$creatorId) {
            return response()->json([
                'success' => false,
                'message' => 'Creator is required. Please enter a username and select from the search results.'
            ], 422);
        }
        
        // Final validation: ensure creator exists and is valid
        $creator = User::where('id', $creatorId)
            ->where('public_profile', 1)
            ->first();
            
        if (!$creator) {
            return response()->json([
                'success' => false,
                'message' => 'Selected creator is not valid. Please select a creator from the search results.'
            ], 422);
        }
        
        // Ensure user is not trying to request from themselves
        if ($creator->id == Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot create a custom request for yourself. Please select a different creator.'
            ], 422);
        }

        $data = $request->all();
        $data['creator_id'] = $creatorId;
        $data['requester_id'] = Auth::id();
        $data['upfront_payment'] = $upfrontPayment;
        
        // Enable voting for public and marketplace requests
        if (in_array($requestType, ['public', 'marketplace'])) {
            $data['requires_voting'] = true;
        }
        
        if ($data['type'] === 'marketplace') {
            $data['is_marketplace'] = true;
            $data['goal_amount'] = $data['goal_amount'] ?? 0;
            $data['current_amount'] = 0;
        } else {
            $data['is_marketplace'] = false;
            $data['price'] = $data['price'] ?? 0;
        }

        // Remove creator_username from data
        unset($data['creator_username']);

        // Create the request (payment will be processed separately)
        $customRequest = CustomRequest::create($data);

        // Return request with payment information
        return response()->json([
            'success' => true,
            'message' => 'Custom request created. Please complete the upfront payment to proceed.',
            'request' => $customRequest,
            'requires_payment' => true,
            'upfront_payment' => $upfrontPayment,
            'payment_url' => route('custom-requests.payment', $customRequest->id)
        ], 200);
    }

    /**
     * Contribute to a marketplace request
     */
    public function contribute(Request $request, $id)
    {
        $customRequest = CustomRequest::findOrFail($id);

        if (!$customRequest->is_marketplace) {
            return response()->json([
                'success' => false,
                'message' => 'This is not a marketplace request'
            ], 400);
        }

        if ($customRequest->status !== CustomRequest::STATUS_ACCEPTED) {
            return response()->json([
                'success' => false,
                'message' => 'This request is not accepting contributions'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $amount = $request->input('amount');

        // Create contribution record
        $contribution = CustomRequestContribution::create([
            'custom_request_id' => $customRequest->id,
            'contributor_id' => Auth::id(),
            'amount' => $amount,
            'message' => $request->input('message'),
            'status' => CustomRequestContribution::STATUS_PENDING,
        ]);

        // Update current amount (will be updated when payment is confirmed)
        $customRequest->current_amount += $amount;
        $customRequest->save();

        // TODO: Integrate with payment system
        // For now, we'll mark it as completed immediately
        // In production, you'd create a transaction and process payment
        $contribution->status = CustomRequestContribution::STATUS_COMPLETED;
        $contribution->save();

        return response()->json([
            'success' => true,
            'message' => 'Contribution added successfully',
            'contribution' => $contribution,
            'current_amount' => $customRequest->fresh()->current_amount,
            'progress' => $customRequest->fresh()->progress_percentage
        ]);
    }

    /**
     * Accept a custom request (creator accepts)
     */
    public function accept($id)
    {
        // Ensure we always return JSON
        if (!request()->expectsJson() && !request()->ajax()) {
            request()->headers->set('Accept', 'application/json');
        }

        $customRequest = CustomRequest::findOrFail($id);

        if ($customRequest->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only accept requests sent to you.'
            ], 403);
        }

        $customRequest->status = CustomRequest::STATUS_ACCEPTED;
        $customRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Request accepted successfully!'
        ], 200);
    }

    /**
     * Reject a custom request
     */
    public function reject($id)
    {
        // Ensure we always return JSON
        if (!request()->expectsJson() && !request()->ajax()) {
            request()->headers->set('Accept', 'application/json');
        }

        $customRequest = CustomRequest::findOrFail($id);

        if ($customRequest->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only reject requests sent to you.'
            ], 403);
        }

        $customRequest->status = CustomRequest::STATUS_REJECTED;
        $customRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Request rejected successfully!'
        ], 200);
    }

    /**
     * Mark request as completed
     */
    public function complete($id)
    {
        // Ensure we always return JSON
        if (!request()->expectsJson() && !request()->ajax()) {
            request()->headers->set('Accept', 'application/json');
        }

        $customRequest = CustomRequest::findOrFail($id);

        if ($customRequest->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only complete requests you accepted.'
            ], 403);
        }

        // Optional delivery video. Completing used to only flip the status, so the fulfilment
        // never existed as content anywhere — the browse page had nothing to show. When a
        // video is supplied it is stored alongside the request and becomes the public proof
        // of the challenge.
        if (request()->hasFile('delivery_video')) {
            $validator = Validator::make(request()->all(), [
                'delivery_video' => 'file|mimes:mp4,mov,webm,avi|max:20480',
                'delivery_thumbnail' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $file = request()->file('delivery_video');
            $name = time() . '_cr' . $customRequest->id . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('storage/custom-requests'), $name);
            $customRequest->delivery_video_path = 'custom-requests/' . $name;

            if (request()->hasFile('delivery_thumbnail')) {
                $thumb = request()->file('delivery_thumbnail');
                $thumbName = time() . '_cr' . $customRequest->id . '_thumb_' . Str::random(8) . '.' . $thumb->getClientOriginalExtension();
                $thumb->move(public_path('storage/custom-requests'), $thumbName);
                $customRequest->delivery_thumbnail_path = 'custom-requests/' . $thumbName;
            }

            $customRequest->delivered_at = now();
        }

        $customRequest->status = CustomRequest::STATUS_COMPLETED;
        $customRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Request marked as completed successfully!'
        ], 200);
    }

    /**
     * Cancel a custom request
     */
    public function cancel($id)
    {
        $customRequest = CustomRequest::findOrFail($id);

        if ($customRequest->requester_id !== Auth::id() && $customRequest->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $customRequest->status = CustomRequest::STATUS_CANCELLED;
        $customRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Request cancelled'
        ]);
    }

    /**
     * Get user's custom requests
     */
    public function myRequests(Request $request)
    {
        // Ensure user is authenticated
        if (!Auth::check()) {
            return redirect()->route('login');
        }
        
        $type = $request->get('type', 'all'); // 'created', 'received', 'all'
        $userId = Auth::id();
        
        $query = CustomRequest::with(['creator', 'requester', 'contributions']);

        // Always filter by logged-in user to prevent seeing other users' requests
        if ($type === 'created') {
            $query->where('requester_id', $userId);
        } elseif ($type === 'received') {
            $query->where('creator_id', $userId);
        } else {
            // Show all requests where user is either requester or creator
            $query->where(function($q) use ($userId) {
                $q->where('requester_id', $userId)
                  ->orWhere('creator_id', $userId);
            });
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('custom-requests.my-requests', compact('requests', 'type'));
    }

    /**
     * Process upfront payment for custom request
     */
    public function processPayment(Request $request, $id)
    {
        $customRequest = CustomRequest::findOrFail($id);

        // Verify user owns this request
        if ($customRequest->requester_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only pay for your own requests.'
            ], 403);
        }

        // Check if payment already received
        if ($customRequest->payment_received) {
            return response()->json([
                'success' => false,
                'message' => 'Payment has already been received for this request.'
            ], 400);
        }

        $paymentProvider = $request->input('provider', Transaction::CREDIT_PROVIDER);
        $amount = $customRequest->upfront_payment;

        // Validate minimum payment
        if ($amount < 1.00) {
            return response()->json([
                'success' => false,
                'message' => 'Minimum upfront payment is $1.00'
            ], 422);
        }

        try {
            // Create transaction
            $transaction = new Transaction();
            $transaction->sender_user_id = Auth::id();
            $transaction->recipient_user_id = $customRequest->creator_id;
            $transaction->type = 'custom-request-payment';
            $transaction->status = Transaction::INITIATED_STATUS;
            $transaction->amount = $amount;
            $transaction->currency = config('app.site.currency_code', 'USD');
            $transaction->payment_provider = $paymentProvider;
            $transaction->save();

            // Link transaction to custom request
            $customRequest->payment_transaction_id = $transaction->id;
            $customRequest->save();

            // Process payment based on provider
            if ($paymentProvider === Transaction::CREDIT_PROVIDER) {
                // Check wallet balance
                $user = Auth::user();
                $wallet = $user->wallet ?? null;
                
                if (!$wallet || $wallet->total_balance < $amount) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Insufficient wallet balance. Please deposit funds first.'
                    ], 400);
                }

                // Process credit payment immediately
                $paymentHelper = app(PaymentHelper::class);
                $transaction->status = Transaction::APPROVED_STATUS;
                $transaction->save();

                // Deduct from wallet and credit creator
                $paymentHelper->deductMoneyFromUserWalletForCreditTransaction($transaction, $wallet);
                $paymentHelper->creditReceiverForTransaction($transaction);

                // Mark payment as received
                $customRequest->payment_received = true;
                $customRequest->payment_received_at = now();
                $customRequest->status = CustomRequest::STATUS_ACCEPTED; // Auto-accept after payment
                $customRequest->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Payment processed successfully. Request has been accepted.',
                    'request' => $customRequest->fresh()
                ], 200);
            } else {
                // For other providers, redirect to payment gateway
                // This would integrate with PaymentHelper similar to other payments
                return response()->json([
                    'success' => true,
                    'message' => 'Redirecting to payment gateway...',
                    'redirect_url' => route('payment.initiatePayment', [
                        'transaction_id' => $transaction->id
                    ])
                ], 200);
            }
        } catch (\Exception $e) {
            Log::error('Custom request payment failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Please try again.'
            ], 500);
        }
    }

    /**
     * Vote on a custom request (for public/marketplace requests)
     */
    public function vote(Request $request, $id)
    {
        $customRequest = CustomRequest::findOrFail($id);

        // Check if voting is required
        if (!$customRequest->requires_voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting is not required for this request.'
            ], 400);
        }

        // Check if user can vote
        if (!$customRequest->canUserVote(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have voting rights. Only the requester and contributors can vote.'
            ], 403);
        }

        // Check if user already voted
        if ($customRequest->hasUserVoted(Auth::id())) {
            return response()->json([
                'success' => false,
                'message' => 'You have already voted on this request.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'vote_type' => 'required|in:approve,reject,abstain',
            'comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        // Check if user is requester
        $isRequester = $customRequest->requester_id === Auth::id();
        
        // Check if user is contributor
        $contribution = $customRequest->contributions()
            ->where('contributor_id', Auth::id())
            ->where('status', CustomRequestContribution::STATUS_COMPLETED)
            ->first();
        
        $isContributor = $contribution !== null;
        $contributionAmount = $contribution ? $contribution->amount : 0;

        // Create vote
        $vote = CustomRequestVote::create([
            'custom_request_id' => $customRequest->id,
            'voter_id' => Auth::id(),
            'vote_type' => $request->input('vote_type'),
            'comment' => $request->input('comment'),
            'is_requester' => $isRequester,
            'is_contributor' => $isContributor,
            'contribution_amount' => $contributionAmount,
        ]);

        // Update voting statistics
        $customRequest->updateVotingStats();

        return response()->json([
            'success' => true,
            'message' => 'Your vote has been recorded successfully.',
            'vote' => $vote,
            'voting_stats' => [
                'total_votes' => $customRequest->total_votes,
                'approval_votes' => $customRequest->approval_votes,
                'rejection_votes' => $customRequest->rejection_votes,
                'approval_percentage' => $customRequest->approval_percentage,
                'has_majority' => $customRequest->hasMajorityApproval(),
            ]
        ], 200);
    }

    /**
     * Release funds to creator (after majority approval)
     */
    public function releaseFunds(Request $request, $id)
    {
        $customRequest = CustomRequest::with(['votes', 'contributions'])->findOrFail($id);

        // Only creator can request fund release
        if ($customRequest->creator_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only the creator can request fund release.'
            ], 403);
        }

        // Check if funds can be released
        if (!$customRequest->canReleaseFunds()) {
            return response()->json([
                'success' => false,
                'message' => 'Funds cannot be released yet. ' . 
                    ($customRequest->requires_voting 
                        ? 'Majority approval is required.' 
                        : 'Request must be completed first.')
            ], 400);
        }

        // Check if funds already released
        if ($customRequest->funds_released) {
            return response()->json([
                'success' => false,
                'message' => 'Funds have already been released for this request.'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'release_notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        try {
            // Calculate total amount to release
            $totalAmount = $customRequest->is_marketplace 
                ? $customRequest->current_amount 
                : ($customRequest->price + $customRequest->upfront_payment);

            // Create transactions for all contributors (marketplace) or single payment
            if ($customRequest->is_marketplace) {
                // Release funds from contributions
                foreach ($customRequest->completedContributions as $contribution) {
                    if ($contribution->transaction_id) {
                        $transaction = Transaction::find($contribution->transaction_id);
                        if ($transaction && $transaction->status === Transaction::APPROVED_STATUS) {
                            // Funds are already held, just mark as released
                            // In a real system, you'd transfer from escrow to creator
                        }
                    }
                }
            } else {
                // For private/public, release the price amount
                // Upfront payment was already paid, now release the remaining price
                if ($customRequest->price > 0) {
                    // Create transaction to release remaining amount
                    $transaction = new Transaction();
                    $transaction->sender_user_id = $customRequest->requester_id;
                    $transaction->recipient_user_id = $customRequest->creator_id;
                    $transaction->type = 'custom-request-release';
                    $transaction->status = Transaction::APPROVED_STATUS;
                    $transaction->amount = $customRequest->price;
                    $transaction->currency = config('app.site.currency_code', 'USD');
                    $transaction->payment_provider = Transaction::CREDIT_PROVIDER;
                    $transaction->save();

                    // Credit creator (assuming wallet system)
                    $paymentHelper = app(PaymentHelper::class);
                    $paymentHelper->creditReceiverForTransaction($transaction);
                }
            }

            // Mark funds as released
            $customRequest->funds_released = true;
            $customRequest->funds_released_at = now();
            $customRequest->release_notes = $request->input('release_notes');
            $customRequest->status = CustomRequest::STATUS_COMPLETED;
            $customRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Funds have been released successfully to the creator.',
                'request' => $customRequest->fresh()
            ], 200);
        } catch (\Exception $e) {
            Log::error('Fund release failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to release funds. Please contact support.'
            ], 500);
        }
    }

    /**
     * Create a support ticket
     */
    public function createSupportTicket(Request $request, $id)
    {
        $customRequest = CustomRequest::findOrFail($id);

        // Verify user is involved in the request
        $userId = Auth::id();
        $isRequester = $customRequest->requester_id === $userId;
        $isCreator = $customRequest->creator_id === $userId;
        $isContributor = $customRequest->contributions()
            ->where('contributor_id', $userId)
            ->exists();

        if (!$isRequester && !$isCreator && !$isContributor) {
            return response()->json([
                'success' => false,
                'message' => 'You can only create support tickets for requests you are involved in.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'type' => 'required|in:general,dispute,payment,voting,technical',
            'priority' => 'required|in:low,normal,high,urgent',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $ticket = SupportTicket::create([
            'user_id' => $userId,
            'custom_request_id' => $customRequest->id,
            'type' => $request->input('type'),
            'priority' => $request->input('priority'),
            'status' => SupportTicket::STATUS_OPEN,
            'subject' => $request->input('subject'),
            'description' => $request->input('description'),
        ]);

        // Create initial message
        SupportTicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $userId,
            'message' => $request->input('description'),
            'is_internal' => false,
        ]);

        // Update custom request
        $customRequest->has_support_ticket = true;
        $customRequest->support_status = SupportTicket::STATUS_OPEN;
        $customRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Support ticket created successfully. Ticket #' . $ticket->ticket_number,
            'ticket' => $ticket
        ], 200);
    }

    /**
     * Get voting statistics for a request
     */
    public function getVotingStats($id)
    {
        $customRequest = CustomRequest::with(['votes.voter', 'contributions'])->findOrFail($id);

        if (!$customRequest->requires_voting) {
            return response()->json([
                'success' => false,
                'message' => 'Voting is not enabled for this request.'
            ], 400);
        }

        $userVote = null;
        if (Auth::check()) {
            $userVote = $customRequest->getUserVote(Auth::id());
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'total_votes' => $customRequest->total_votes,
                'approval_votes' => $customRequest->approval_votes,
                'rejection_votes' => $customRequest->rejection_votes,
                'approval_percentage' => $customRequest->approval_percentage,
                'has_majority_approval' => $customRequest->hasMajorityApproval(),
                'can_release_funds' => $customRequest->canReleaseFunds(),
                'user_can_vote' => Auth::check() ? $customRequest->canUserVote(Auth::id()) : false,
                'user_has_voted' => Auth::check() ? $customRequest->hasUserVoted(Auth::id()) : false,
                'user_vote' => $userVote,
                'votes' => $customRequest->votes->map(function($vote) {
                    return [
                        'id' => $vote->id,
                        'voter_name' => $vote->voter->name ?? 'Unknown',
                        'vote_type' => $vote->vote_type,
                        'comment' => $vote->comment,
                        'is_requester' => $vote->is_requester,
                        'is_contributor' => $vote->is_contributor,
                        'created_at' => $vote->created_at,
                    ];
                }),
            ]
        ], 200);
    }
}
