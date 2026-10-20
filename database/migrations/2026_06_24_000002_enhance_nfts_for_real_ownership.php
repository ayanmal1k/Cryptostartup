<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes the nfts table capable of representing REAL on-chain ownership.
 *
 * - owner_address: the wallet that currently owns the token on-chain (source of truth,
 *   mirrored from the chain). Distinct from user_id, which is the linked platform account.
 * - chain_id / contract_address: which chain + contract the token lives on (multi-chain ready).
 * - mint_tx_hash / metadata_uri: the real mint transaction and the IPFS metadata pointer.
 * - token_id becomes nullable (a pending mint has no token id until the tx confirms) and its
 *   single-column unique constraint is replaced by a (chain_id, contract_address, token_id)
 *   composite unique, since token ids are only unique within a contract.
 * - status becomes a varchar to support the real lifecycle:
 *   pending_mint -> minted -> listed -> sold -> transferred (and mint_failed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfts', function (Blueprint $table) {
            if (!Schema::hasColumn('nfts', 'owner_address')) {
                $table->string('owner_address')->nullable()->after('user_id')->index();
            }
            if (!Schema::hasColumn('nfts', 'chain_id')) {
                $table->unsignedBigInteger('chain_id')->nullable()->after('contract_address');
            }
            if (!Schema::hasColumn('nfts', 'metadata_uri')) {
                $table->string('metadata_uri')->nullable()->after('token_uri');
            }
            if (!Schema::hasColumn('nfts', 'mint_tx_hash')) {
                $table->string('mint_tx_hash')->nullable()->after('metadata_uri');
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // Drop the single-column unique on token_id (a pending mint has no id, and ids
            // are only unique per-contract anyway). Keep the plain index for lookups.
            if (!empty(DB::select("SHOW INDEX FROM nfts WHERE Key_name = 'nfts_token_id_unique'"))) {
                DB::statement('ALTER TABLE nfts DROP INDEX nfts_token_id_unique');
            }
            DB::statement('ALTER TABLE nfts MODIFY token_id VARCHAR(255) NULL');
            DB::statement("ALTER TABLE nfts MODIFY status VARCHAR(32) NOT NULL DEFAULT 'pending_mint'");

            if (empty(DB::select("SHOW INDEX FROM nfts WHERE Key_name = 'nfts_chain_contract_token_unique'"))) {
                DB::statement('ALTER TABLE nfts ADD UNIQUE nfts_chain_contract_token_unique (chain_id, contract_address, token_id)');
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            if (!empty(DB::select("SHOW INDEX FROM nfts WHERE Key_name = 'nfts_chain_contract_token_unique'"))) {
                DB::statement('ALTER TABLE nfts DROP INDEX nfts_chain_contract_token_unique');
            }
        }

        Schema::table('nfts', function (Blueprint $table) {
            foreach (['owner_address', 'chain_id', 'metadata_uri', 'mint_tx_hash'] as $col) {
                if (Schema::hasColumn('nfts', $col)) {
                    if ($col === 'owner_address') {
                        $table->dropIndex(['owner_address']);
                    }
                    $table->dropColumn($col);
                }
            }
        });
    }
};
