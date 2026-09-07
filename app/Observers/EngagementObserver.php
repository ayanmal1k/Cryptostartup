<?php

namespace App\Observers;

use App\Model\Transaction;
use App\Model\User;
use App\Services\EngagementService;
use Illuminate\Support\Facades\Auth;

/**
 * Turns real activity into XP and sparks.
 *
 * Registered against several models at once rather than split into one observer per
 * model: the logic is a single mapping from "a thing happened" to "award this action",
 * and spreading eight near-identical classes across the Observers directory would hide
 * that mapping rather than express it.
 *
 * Everything here is best-effort. An award failing must never roll back the post,
 * comment or payment that earned it, so every branch is inside a try/catch and the
 * observer never throws.
 */
class EngagementObserver
{
    public function created($model)
    {
        try {
            $class = class_basename($model);

            switch ($class) {
                case 'Post':
                    $this->awardOwner($model, 'post_create');
                    break;

                case 'Video':
                    $this->awardOwner($model, 'video_upload');
                    break;

                case 'PostComment':
                case 'VideoComment':
                    $this->awardOwner($model, 'comment_create');
                    break;

                case 'Reaction':
                    $this->awardOwner($model, 'reaction_give');
                    break;

                case 'Stream':
                    $this->awardOwner($model, 'stream_start');
                    break;

                case 'CustomRequest':
                    $this->awardOwner($model, 'custom_request');
                    break;

                case 'NFT':
                    $this->awardOwner($model, 'nft_mint');
                    break;

                case 'Transaction':
                    // A transaction can be created already-approved, so handle it here
                    // as well as on the status transition in updated().
                    if ($this->isApproved($model)) {
                        $this->awardTransaction($model);
                    }
                    break;
            }
        } catch (\Throwable $e) {
            // never break the write that triggered this
        }
    }

    public function updated($model)
    {
        try {
            if (class_basename($model) !== 'Transaction') {
                return;
            }

            // Only pay out on the transition into approved, not on every later save of an
            // already-approved row -- otherwise editing a transaction re-awards it.
            if ($this->isApproved($model) && $model->wasChanged('status')) {
                $this->awardTransaction($model);
            }
        } catch (\Throwable $e) {
            // never break the write that triggered this
        }
    }

    /** Award the action to the model's owning user. */
    protected function awardOwner($model, string $action): void
    {
        $userId = $model->user_id ?? null;
        if (!$userId) {
            return;
        }

        // Prefer the authenticated instance so the award is reflected in the response
        // the user is about to receive, rather than only in the database.
        $user = (Auth::check() && (string) Auth::id() === (string) $userId)
            ? Auth::user()
            : User::find($userId);

        if ($user instanceof User) {
            EngagementService::award($user, $action, $model);
        }
    }

    protected function isApproved($transaction): bool
    {
        return ($transaction->status ?? null) === Transaction::APPROVED_STATUS;
    }

    /**
     * Pay both sides of a money movement: the fan for spending, the creator for earning.
     * Both sides matter -- a creator who sees nothing happen when a tip lands has no
     * reason to engage with any of this.
     */
    protected function awardTransaction($transaction): void
    {
        $senderAction = $this->actionForTransactionType($transaction->type ?? null);

        if ($senderAction && !empty($transaction->sender_user_id)) {
            $sender = User::find($transaction->sender_user_id);
            if ($sender) {
                EngagementService::award($sender, $senderAction, $transaction);
            }
        }

        if (!empty($transaction->recipient_user_id)) {
            $recipient = User::find($transaction->recipient_user_id);
            if ($recipient) {
                $isTip = in_array(
                    $transaction->type ?? null,
                    [Transaction::TIP_TYPE, Transaction::CHAT_TIP_TYPE],
                    true
                );
                EngagementService::award(
                    $recipient,
                    $isTip ? 'tip_receive' : 'sale_receive',
                    $transaction
                );
            }
        }
    }

    /** Map a transaction type onto the action the payer earns. */
    protected function actionForTransactionType(?string $type): ?string
    {
        switch ($type) {
            case Transaction::TIP_TYPE:
            case Transaction::CHAT_TIP_TYPE:
                return 'tip_send';

            case Transaction::POST_UNLOCK:
            case Transaction::MESSAGE_UNLOCK:
            case Transaction::STREAM_ACCESS:
                return 'purchase';

            case Transaction::ONE_MONTH_SUBSCRIPTION:
            case Transaction::THREE_MONTHS_SUBSCRIPTION:
            case Transaction::SIX_MONTHS_SUBSCRIPTION:
            case Transaction::YEARLY_SUBSCRIPTION:
            case Transaction::SUBSCRIPTION_RENEWAL:
                return 'subscribe';

            default:
                // Deposits and withdrawals are not engagement -- moving your own money
                // around should not level you up.
                return null;
        }
    }
}
