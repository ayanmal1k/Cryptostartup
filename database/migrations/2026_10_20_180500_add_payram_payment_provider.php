<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddPayramPaymentProvider extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add PayRam tracking columns to transactions table
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (!Schema::hasColumn('transactions', 'payram_payment_id')) {
                    $table->string('payram_payment_id')->after('nowpayments_order_id')->nullable();
                }
                if (!Schema::hasColumn('transactions', 'payram_order_id')) {
                    $table->string('payram_order_id')->after('payram_payment_id')->nullable();
                }
            });
        }

        // Insert PayRam admin settings into the Voyager settings table
        if (Schema::hasTable('settings')) {
            $settings = [
                [
                    'key' => 'payments.payram_api_url',
                    'display_name' => 'PayRam API URL',
                    'value' => NULL,
                    'details' => NULL,
                    'type' => 'text',
                    'order' => 80,
                    'group' => 'Payments',
                ],
                [
                    'key' => 'payments.payram_api_key',
                    'display_name' => 'PayRam API Key',
                    'value' => NULL,
                    'details' => NULL,
                    'type' => 'text',
                    'order' => 81,
                    'group' => 'Payments',
                ],
                [
                    'key' => 'payments.payram_webhook_secret',
                    'display_name' => 'PayRam Webhook Secret',
                    'value' => NULL,
                    'details' => NULL,
                    'type' => 'text',
                    'order' => 82,
                    'group' => 'Payments',
                ],
                [
                    'key' => 'payments.payram_checkout_disabled',
                    'display_name' => 'PayRam Checkout Disabled',
                    'value' => '0',
                    'details' => NULL,
                    'type' => 'checkbox',
                    'order' => 83,
                    'group' => 'Payments',
                ],
            ];

            foreach ($settings as $setting) {
                if (!DB::table('settings')->where('key', $setting['key'])->exists()) {
                    DB::table('settings')->insert($setting);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('transactions')) {
            Schema::table('transactions', function (Blueprint $table) {
                if (Schema::hasColumn('transactions', 'payram_payment_id')) {
                    $table->dropColumn('payram_payment_id');
                }
                if (Schema::hasColumn('transactions', 'payram_order_id')) {
                    $table->dropColumn('payram_order_id');
                }
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('key', 'like', 'payments.payram_%')->delete();
        }
    }
}
