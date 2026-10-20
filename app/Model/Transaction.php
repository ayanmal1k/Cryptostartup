<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    public const PENDING_STATUS = 'pending';
    public const CANCELED_STATUS = 'canceled';
    public const APPROVED_STATUS = 'approved';
    public const DECLINED_STATUS = 'declined';
    public const REFUNDED_STATUS = 'refunded';
    public const INITIATED_STATUS = 'initiated';
    public const PARTIALLY_PAID_STATUS = 'partially-paid';
    public const TIP_TYPE = 'tip';
    public const CHAT_TIP_TYPE = 'chat-tip';
    public const POST_UNLOCK = 'post-unlock';
    public const MESSAGE_UNLOCK = 'message-unlock';
    public const DEPOSIT_TYPE = 'deposit';
    public const WITHDRAWAL_TYPE = 'withdrawal';
    public const ONE_MONTH_SUBSCRIPTION = 'one-month-subscription';
    public const THREE_MONTHS_SUBSCRIPTION = 'three-months-subscription';
    public const SIX_MONTHS_SUBSCRIPTION = 'six-months-subscription';
    public const YEARLY_SUBSCRIPTION = 'yearly-subscription';
    public const SUBSCRIPTION_RENEWAL = 'subscription-renewal';
    public const STREAM_ACCESS = 'stream-access';
    public const PAYPAL_PROVIDER = 'paypal';
    public const STRIPE_PROVIDER = 'stripe';
    public const MANUAL_PROVIDER = 'manual';
    public const CREDIT_PROVIDER = 'credit';
    public const COINBASE_PROVIDER = 'coinbase';
    public const CCBILL_PROVIDER = 'ccbill';
    public const NOWPAYMENTS_PROVIDER = 'nowpayments';
    public const PAYSTACK_PROVIDER = 'paystack';
    public const OXXO_PROVIDER = 'oxxo';
    public const MERCADO_PROVIDER = 'mercado';
    public const PAYRAM_PROVIDER = 'payram';
    public const COINBASE_API_BASE_PATH = 'https://api.commerce.coinbase.com';
    public const NOWPAYMENTS_API_BASE_PATH = 'https://api.nowpayments.io/v1/';
    public const ALLOWED_PAYMENT_PROVIDERS = [
        self::NOWPAYMENTS_PROVIDER,
        self::COINBASE_PROVIDER,
        self::PAYPAL_PROVIDER,
        self::STRIPE_PROVIDER,
        self::CCBILL_PROVIDER,
        self::PAYSTACK_PROVIDER,
        self::OXXO_PROVIDER,
        self::MERCADO_PROVIDER,
        self::PAYRAM_PROVIDER,
    ];
    public const PENDING_PAYMENT_PROCESSORS = [
        self::COINBASE_PROVIDER,
        self::NOWPAYMENTS_PROVIDER,
        self::CCBILL_PROVIDER,
        self::OXXO_PROVIDER,
        self::PAYRAM_PROVIDER,
    ];
    public const CCBILL_FLEX_FORM_BASE_PATH = 'https://api.ccbill.com/wap-frontflex/flexforms/';
    public const CCBILL_CANCEL_SUBSCRIPTION_BASE_PATH = 'https://datalink.ccbill.com/utils/subscriptionManagement.cgi';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sender_user_id', 'recipient_user_id', 'subscription_id', 'stripe_transaction_id', 'paypal_payer_id', 'post_id',
        'paypal_transaction_id', 'status', 'type', 'amount', 'payment_provider', 'paypal_transaction_token', 'currency', 'taxes',
        'coinbase_charge_id', 'coinbase_transaction_token', 'ccbill_payment_token', 'ccbill_transaction_id', 'nowpayments_payment_id',
        'nowpayments_order_id', 'stream_id', 'ccbill_subscription_id', 'user_message_id', 'paystack_transaction_token',
        'payram_payment_id', 'payram_order_id',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [

    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [

    ];

    public function getDecodedTaxesAttribute()
    {
        return json_decode($this->taxes);
    }

    /*
     * Relationships
     */

    public function receiver()
    {
        return $this->belongsTo('App\User', 'recipient_user_id');
    }

    public function sender()
    {
        return $this->belongsTo('App\User', 'sender_user_id');
    }

    public function subscription()
    {
        return $this->belongsTo('App\Model\Subscription', 'subscription_id');
    }

    public function post()
    {
        return $this->belongsTo('App\Model\Post', 'post_id');
    }

    public function invoice()
    {
        return $this->belongsTo('App\Model\Invoice', 'invoice_id');
    }

    public function stream()
    {
        return $this->belongsTo('App\Model\Stream', 'stream_id');
    }
}
