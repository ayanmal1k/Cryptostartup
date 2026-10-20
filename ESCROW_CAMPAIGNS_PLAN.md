# Bounty / Escrow Campaigns — build plan

> Community pools money to get a person who **isn't on the platform yet** (e.g. "Kim
> Kardashian") to join. Funds are held in **escrow** until the target joins, claims, and a
> **moderator** approves. If it never happens, contributors are **refunded**.

Built on top of the existing **Custom Requests** patterns (campaign + contributions + wallet
money movement + Voyager moderation).

## v1 decisions (locked)
- **Escrow = platform wallet.** Contributing debits the contributor's wallet (`wallet.total`)
  and creates a `bounty_contributions` row with status `held`. The money is held by the
  platform (not credited to anyone) until release. No third-party escrow API in v1.
- **Release.** When the target signs up + is ID-verified and submits a claim, a **moderator**
  (`role_id === 1`) approves → the pooled amount is credited to the claimer's wallet, the
  held contributions become `released`, campaign → `released`.
- **Refund.** A moderator can refund anytime (deadline passed, bad target, rejected claim) →
  every `held` contribution is credited back to its contributor, campaign → `refunded`.
- **Claim gating.** Claimer must be a registered user with `email_verified_at` + an approved
  identity verification (Settings → Verify). Moderator is the final check against impersonation.

## Data model
- `bounty_campaigns`: creator_id, target_name, target_handle, target_description, target_avatar,
  goal_amount, current_amount, deadline, status (open / claim_pending / released / refunded /
  expired / cancelled), claimed_by_user_id, claim_status (none / pending / approved / rejected),
  claim_message, funds_released, funds_released_at, moderator_notes, softDeletes.
- `bounty_contributions`: bounty_campaign_id, contributor_id, amount, status (held / released /
  refunded), message.

## Lifecycle
open → (contributions held) → target submits claim → `claim_pending` → moderator approve →
`released` (funds to claimer)  |  moderator reject → back to `open`  |  moderator refund →
`refunded` (funds back to contributors).

## Files
- `database/migrations/..._create_bounty_campaigns_table.php`
- `database/migrations/..._create_bounty_contributions_table.php`
- `app/Models/BountyCampaign.php`, `app/Models/BountyContribution.php`
- `app/Http/Controllers/BountyCampaignController.php`
- `routes/web.php` — `bounty-campaigns.*` group
- `resources/views/bounty-campaigns/marketplace.blade.php`, `show.blade.php`

## Go-live checklist (NOT done yet — needs you)
1. Run `php artisan migrate` on the server (agent can't touch prod DB).
2. Test contribute → claim → approve/refund with a couple of test accounts.
3. Add a "Bounties" link to the side menu + header.
4. Decide moderation-before-public-listing & the legal/consent framing (see risks).

## Risks to handle before promoting heavily
- Using a **real named person** on an adult platform without consent (publicity/likeness rights,
  defamation, refund obligations). Mitigations baked in: guaranteed refunds, target must opt in +
  verify to claim, moderator oversight, neutral "invite" framing. Consider requiring moderator
  approval before a campaign is publicly listed, and a takedown path on request.
