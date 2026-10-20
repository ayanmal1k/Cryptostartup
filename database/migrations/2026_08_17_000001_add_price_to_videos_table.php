<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds pay-per-view pricing to videos.
 *
 * `is_private` already exists (from the status migration) and covers "only me"; this adds the
 * paid tier so a creator can price a video from the upload form. price = 0 means free.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (!Schema::hasColumn('videos', 'price')) {
                $table->decimal('price', 10, 2)->default(0)->after('is_private');
            }
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            if (Schema::hasColumn('videos', 'price')) {
                $table->dropColumn('price');
            }
        });
    }
};
