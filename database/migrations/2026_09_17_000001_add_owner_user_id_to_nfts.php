<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * NFTs are sold for platform credits (dollars) now, so ownership belongs to an account, not a
 * wallet address. Fans no longer need to connect a crypto wallet to own or buy one; the chain
 * record (owner_address) is kept alongside for when an on-chain wallet is involved.
 *
 * Backfill: an NFT whose owner_address matches a user's linked wallet belongs to that user;
 * everything else belongs to its creator, which is who owned it before any sale.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('nfts', 'owner_user_id')) {
            Schema::table('nfts', function (Blueprint $table) {
                $table->unsignedBigInteger('owner_user_id')->nullable()->after('user_id');
                $table->index('owner_user_id');
            });
        }

        DB::table('nfts')->whereNull('owner_user_id')->orderBy('id')->each(function ($nft) {
            $owner = null;
            if ($nft->owner_address) {
                $owner = DB::table('users')
                    ->whereRaw('LOWER(wallet_address) = ?', [strtolower($nft->owner_address)])
                    ->value('id');
            }
            DB::table('nfts')->where('id', $nft->id)->update(['owner_user_id' => $owner ?: $nft->user_id]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('nfts', 'owner_user_id')) {
            Schema::table('nfts', function (Blueprint $table) {
                $table->dropIndex(['owner_user_id']);
                $table->dropColumn('owner_user_id');
            });
        }
    }
};
