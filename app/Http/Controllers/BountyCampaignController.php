<?php

namespace App\Http\Controllers;

use App\Models\BountyCampaign;
use App\Models\BountyContribution;
use App\Providers\GenericHelperServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BountyCampaignController extends Controller
{
    /**
     * Public marketplace listing of active campaigns.
     */
    public function marketplace(Request $request)
    {
        $campaigns = BountyCampaign::withCount('contributions')
            ->whereIn('status', [BountyCampaign::STATUS_OPEN, BountyCampaign::STATUS_CLAIM_PENDING])
            ->orderByDesc('created_at')
            ->paginate(12);

        $stats = [
            'active' => BountyCampaign::whereIn('status', [BountyCampaign::STATUS_OPEN, BountyCampaign::STATUS_CLAIM_PENDING])->count(),
            'raised' => BountyContribution::where('status', BountyContribution::STATUS_HELD)->sum('amount'),
            'released' => BountyCampaign::where('status', BountyCampaign::STATUS_RELEASED)->count(),
        ];

        return view('bounty-campaigns.marketplace', compact('campaigns', 'stats'));
    }

    public function show($id)
    {
        $campaign = BountyCampaign::with(['contributions.contributor', 'creator', 'claimer'])->findOrFail($id);
        return view('bounty-campaigns.show', compact('campaign'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'target_name' => 'required|string|max:120',
            'target_handle' => 'nullable|string|max:120',
            'target_description' => 'nullable|string|max:2000',
            'goal_amount' => 'required|numeric|min:1',
            'deadline_days' => 'nullable|integer|min:1|max:365',
        ]);

        $campaign = BountyCampaign::create([
            'creator_id' => Auth::id(),
            'target_name' => $data['target_name'],
            'target_handle' => $data['target_handle'] ?? null,
            'target_description' => $data['target_description'] ?? null,
            'goal_amount' => $data['goal_amount'],
            'current_amount' => 0,
            'deadline' => now()->addDays($data['deadline_days'] ?? 30),
            'status' => BountyCampaign::STATUS_OPEN,
            'claim_status' => BountyCampaign::CLAIM_NONE,
        ]);

        return redirect()->route('bounty-campaigns.show', $campaign->id)
            ->with('success', __('Campaign created! The community can now contribute.'));
    }

    /**
     * Contribute funds — debits the wallet and holds the money in escrow.
     */
    public function contribute(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:1',
            'message' => 'nullable|string|max:500',
        ]);

        $campaign = BountyCampaign::findOrFail($id);

        if (!$campaign->isOpenForContributions()) {
            return back()->with('error', __('This campaign is not accepting contributions.'));
        }

        $user = Auth::user();
        if (!$user->wallet) {
            GenericHelperServiceProvider::createUserWallet($user);
            $user->refresh();
        }

        $amount = round(floatval($data['amount']), 2);
        if ($user->wallet->total < $amount) {
            return back()->with('error', __('Insufficient wallet balance. Please add funds first.'));
        }

        DB::transaction(function () use ($user, $campaign, $amount, $data) {
            // Debit the contributor; funds are held by the platform (escrow).
            $user->wallet->update(['total' => $user->wallet->total - $amount]);

            BountyContribution::create([
                'bounty_campaign_id' => $campaign->id,
                'contributor_id' => $user->id,
                'amount' => $amount,
                'status' => BountyContribution::STATUS_HELD,
                'message' => $data['message'] ?? null,
            ]);

            $campaign->increment('current_amount', $amount);
        });

        return back()->with('success', __('Thank you! Your contribution is held in escrow until the campaign is claimed.'));
    }

    /**
     * The target submits a claim that they are the person.
     */
    public function claim(Request $request, $id)
    {
        $data = $request->validate([
            'claim_message' => 'nullable|string|max:1000',
        ]);

        $campaign = BountyCampaign::findOrFail($id);

        if ($campaign->status !== BountyCampaign::STATUS_OPEN) {
            return back()->with('error', __('This campaign cannot be claimed right now.'));
        }

        $user = Auth::user();
        $isVerified = $user->verification && $user->verification->status === 'verified';
        if (!$user->email_verified_at || !$isVerified) {
            return back()->with('error', __('You must verify your identity (Settings → Verify) before claiming a campaign.'));
        }

        $campaign->update([
            'claimed_by_user_id' => $user->id,
            'claim_status' => BountyCampaign::CLAIM_PENDING,
            'claim_message' => $data['claim_message'] ?? null,
            'status' => BountyCampaign::STATUS_CLAIM_PENDING,
        ]);

        return back()->with('success', __('Your claim was submitted. A moderator will review it shortly.'));
    }

    /**
     * Moderator approves the claim and releases the escrow to the claimer.
     */
    public function approveClaim($id)
    {
        $campaign = BountyCampaign::with('claimer')->findOrFail($id);

        if ($campaign->claim_status !== BountyCampaign::CLAIM_PENDING || !$campaign->claimed_by_user_id) {
            return back()->with('error', __('There is no pending claim to approve.'));
        }

        $claimer = $campaign->claimer;
        if (!$claimer) {
            return back()->with('error', __('Claimer account not found.'));
        }

        DB::transaction(function () use ($campaign, $claimer) {
            if (!$claimer->wallet) {
                GenericHelperServiceProvider::createUserWallet($claimer);
                $claimer->refresh();
            }

            $payout = $campaign->contributions()->where('status', BountyContribution::STATUS_HELD)->sum('amount');

            $claimer->wallet->update(['total' => $claimer->wallet->total + $payout]);

            $campaign->contributions()->where('status', BountyContribution::STATUS_HELD)
                ->update(['status' => BountyContribution::STATUS_RELEASED]);

            $campaign->update([
                'claim_status' => BountyCampaign::CLAIM_APPROVED,
                'status' => BountyCampaign::STATUS_RELEASED,
                'funds_released' => true,
                'funds_released_at' => now(),
            ]);
        });

        return back()->with('success', __('Claim approved and funds released to the user.'));
    }

    /**
     * Moderator rejects the claim — campaign re-opens.
     */
    public function rejectClaim(Request $request, $id)
    {
        $campaign = BountyCampaign::findOrFail($id);

        if ($campaign->claim_status !== BountyCampaign::CLAIM_PENDING) {
            return back()->with('error', __('There is no pending claim to reject.'));
        }

        $campaign->update([
            'claim_status' => BountyCampaign::CLAIM_REJECTED,
            'status' => BountyCampaign::STATUS_OPEN,
            'claimed_by_user_id' => null,
            'moderator_notes' => $request->input('moderator_notes'),
        ]);

        return back()->with('success', __('Claim rejected. The campaign is open again.'));
    }

    /**
     * Moderator refunds every held contribution back to its contributor.
     */
    public function refund($id)
    {
        $campaign = BountyCampaign::findOrFail($id);

        if (in_array($campaign->status, [BountyCampaign::STATUS_RELEASED, BountyCampaign::STATUS_REFUNDED])) {
            return back()->with('error', __('This campaign has already been finalised.'));
        }

        DB::transaction(function () use ($campaign) {
            $held = $campaign->contributions()->where('status', BountyContribution::STATUS_HELD)->get();
            foreach ($held as $contribution) {
                $contributor = $contribution->contributor;
                if ($contributor) {
                    if (!$contributor->wallet) {
                        GenericHelperServiceProvider::createUserWallet($contributor);
                        $contributor->refresh();
                    }
                    $contributor->wallet->update(['total' => $contributor->wallet->total + $contribution->amount]);
                }
                $contribution->update(['status' => BountyContribution::STATUS_REFUNDED]);
            }

            $campaign->update([
                'status' => BountyCampaign::STATUS_REFUNDED,
                'claim_status' => BountyCampaign::CLAIM_NONE,
            ]);
        });

        return back()->with('success', __('All contributions have been refunded to contributors.'));
    }
}
