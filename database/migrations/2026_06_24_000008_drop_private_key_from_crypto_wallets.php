<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire the custodial private-key column. It only ever stored encrypt(random_bytes(32)) —
 * a value never used to sign anything (security theatre). The platform is non-custodial: users
 * hold their own keys via their connected wallet (users.wallet_address).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('crypto_wallets', 'private_key_encrypted')) {
            Schema::table('crypto_wallets', function (Blueprint $table) {
                $table->dropColumn('private_key_encrypted');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasColumn('crypto_wallets', 'private_key_encrypted')) {
            Schema::table('crypto_wallets', function (Blueprint $table) {
                $table->text('private_key_encrypted')->nullable();
            });
        }
    }
};
