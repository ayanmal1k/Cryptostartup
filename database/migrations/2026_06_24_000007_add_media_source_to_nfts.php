<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links an NFT back to the existing piece of content it was minted from (a video or an image
 * attachment), so we can show a "Minted" badge on that content and prevent minting the same
 * item twice. source_id is a string because it must hold both bigint video ids and uuid
 * attachment ids. media_type is image|video; royalty_bps is the ERC-2981 resale royalty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfts', function (Blueprint $table) {
            if (!Schema::hasColumn('nfts', 'source_type')) {
                $table->string('source_type')->nullable()->after('collection_name');
            }
            if (!Schema::hasColumn('nfts', 'source_id')) {
                $table->string('source_id')->nullable()->after('source_type');
            }
            if (!Schema::hasColumn('nfts', 'media_type')) {
                $table->string('media_type')->nullable()->after('source_id'); // image | video
            }
            if (!Schema::hasColumn('nfts', 'royalty_bps')) {
                $table->unsignedInteger('royalty_bps')->nullable()->after('media_type'); // 1000 = 10%
            }
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            // One NFT per source item (1-of-1). NULLs allowed many times (non-media mints).
            if (empty(DB::select("SHOW INDEX FROM nfts WHERE Key_name = 'nfts_source_unique'"))) {
                DB::statement('ALTER TABLE nfts ADD UNIQUE nfts_source_unique (source_type, source_id)');
            }
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            if (!empty(DB::select("SHOW INDEX FROM nfts WHERE Key_name = 'nfts_source_unique'"))) {
                DB::statement('ALTER TABLE nfts DROP INDEX nfts_source_unique');
            }
        }
        Schema::table('nfts', function (Blueprint $table) {
            foreach (['source_type', 'source_id', 'media_type', 'royalty_bps'] as $col) {
                if (Schema::hasColumn('nfts', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
