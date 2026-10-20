<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds multi-chain provenance fields to nft_transactions so the on-chain history
 * (Transfer events) can be mirrored precisely. chain_id + contract_address scope the
 * record to a chain/contract; log_index disambiguates multiple events in one tx hash.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nft_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('nft_transactions', 'chain_id')) {
                $table->unsignedBigInteger('chain_id')->nullable()->after('token_id');
            }
            if (!Schema::hasColumn('nft_transactions', 'contract_address')) {
                $table->string('contract_address')->nullable()->after('chain_id');
            }
            if (!Schema::hasColumn('nft_transactions', 'log_index')) {
                $table->unsignedInteger('log_index')->nullable()->after('block_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('nft_transactions', function (Blueprint $table) {
            foreach (['chain_id', 'contract_address', 'log_index'] as $col) {
                if (Schema::hasColumn('nft_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
