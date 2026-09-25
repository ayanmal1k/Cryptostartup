<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds the on-chain wallet address to users. Every user needs a wallet address
 * before they can own NFTs. With embedded wallets (thirdweb), this is populated
 * automatically on first login; external-wallet users set it on connect.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'wallet_address')) {
                $table->string('wallet_address')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'wallet_address')) {
                $table->dropIndex(['wallet_address']);
                $table->dropColumn('wallet_address');
            }
        });
    }
};
