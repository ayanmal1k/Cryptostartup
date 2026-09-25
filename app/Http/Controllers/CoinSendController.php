<?php

namespace App\Http\Controllers;

use App\Models\Cryptocurrency;
use App\Models\CryptoTransaction;
use App\Models\CryptoWallet;
use App\Support\TokenMoney;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Send coins to another member, in dollars.
 *
 * The sender types "$20 to @name"; the coin units are worked out here at today's price
 * (App\Support\TokenMoney) and moved between the two wallets. Neither side ever sees a
 * unit count or a ticker: the sender reads "You sent $20.00 of JustCoin to @name" and the
 * recipient's activity reads "Received JustCoin from @sender +$20.00".
 */
class CoinSendController extends Controller
{
    public function store(Request $request, $id)
    {
        $request->validate([
            'recipient' => 'required|string|max:255',
            'amount_usd' => 'required_unless:send_all,1|nullable|numeric|min:1',
            'send_all' => 'nullable|in:0,1',
        ], [
            'recipient.required' => __('Enter the username of the person you are sending to.'),
            'amount_usd.required_unless' => __('Enter how many dollars you want to send.'),
            'amount_usd.min' => __('The smallest amount you can send is $1.'),
        ]);

        $sender = Auth::user();
        $crypto = Cryptocurrency::findOrFail($id);
        $price = (float) $crypto->current_price;

        if (!$crypto->transferable) {
            return $this->fail(__(':coin can’t be sent right now.', ['coin' => $crypto->name]));
        }

        // People type "@name" as often as "name".
        $username = ltrim(trim((string) $request->input('recipient')), '@');
        $recipient = User::where('username', $username)->first();
        if (!$recipient) {
            return $this->fail(__('We couldn’t find anyone called @:name.', ['name' => $username]));
        }
        if ($recipient->id === $sender->id) {
            return $this->fail(__('You can’t send coins to yourself.'));
        }

        try {
            $sentUsd = DB::transaction(function () use ($request, $sender, $recipient, $crypto, $price) {
                // Lock the sender's row so two sends at once can't both spend the same balance.
                $from = CryptoWallet::where('user_id', $sender->id)
                    ->where('cryptocurrency_id', $crypto->id)
                    ->lockForUpdate()
                    ->first();

                $heldUsd = $from ? TokenMoney::toUsd((float) $from->balance, $price) : 0.0;
                if ($heldUsd <= 0) {
                    throw new \DomainException(__('You don’t have any :coin to send.', ['coin' => $crypto->name]));
                }

                if ($request->input('send_all') === '1') {
                    // Exactly what they hold, so no dust is stranded by rounding.
                    $units = (float) $from->balance;
                } else {
                    $usd = round((float) $request->input('amount_usd'), 2);
                    if ($usd > $heldUsd) {
                        throw new \DomainException(__('You only have :amount of :coin.', [
                            'amount' => TokenMoney::format($heldUsd),
                            'coin' => $crypto->name,
                        ]));
                    }
                    $units = min((float) $from->balance, TokenMoney::toUnits($usd, $price));
                }

                if ($units <= 0) {
                    throw new \DomainException(__('That amount is too small to send.'));
                }

                $to = CryptoWallet::where('user_id', $recipient->id)
                    ->where('cryptocurrency_id', $crypto->id)
                    ->lockForUpdate()
                    ->first()
                    ?? CryptoWallet::create([
                        'user_id' => $recipient->id,
                        'cryptocurrency_id' => $crypto->id,
                        'balance' => 0,
                    ]);

                $from->balance = (float) $from->balance - $units;
                $from->save();
                $to->balance = (float) $to->balance + $units;
                $to->save();

                $usdValue = TokenMoney::toUsd($units, $price);

                // Sender is the seller and recipient the buyer, as the activity feed expects.
                CryptoTransaction::create([
                    'cryptocurrency_id' => $crypto->id,
                    'seller_user_id' => $sender->id,
                    'buyer_user_id' => $recipient->id,
                    'type' => CryptoTransaction::TYPE_TRANSFER,
                    'amount' => $units,
                    'price_per_token' => $price,
                    'total_price' => $usdValue,
                    'fee_amount' => 0,
                    'status' => CryptoTransaction::STATUS_COMPLETED,
                ]);

                return $usdValue;
            });
        } catch (\DomainException $e) {
            return $this->fail($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return $this->fail($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('Coin send failed: ' . $e->getMessage(), [
                'sender_id' => $sender->id,
                'recipient_id' => $recipient->id,
                'cryptocurrency_id' => $crypto->id,
            ]);

            return $this->fail(__('That send didn’t go through. Nothing left your wallet — please try again.'));
        }

        return redirect()->to(route('wallet.hub') . '#tokens')
            ->with('send_success', __('You sent :amount of :coin to @:name.', [
                'amount' => TokenMoney::format($sentUsd),
                'coin' => $crypto->name,
                'name' => $recipient->username,
            ]));
    }

    private function fail(string $message)
    {
        return redirect()->to(route('wallet.hub') . '#tokens')
            ->withInput()
            ->with('send_error', $message);
    }
}
