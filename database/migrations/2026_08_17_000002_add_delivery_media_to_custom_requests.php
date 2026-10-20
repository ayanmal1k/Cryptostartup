<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a completed custom request carry the video the creator actually delivered.
 *
 * Until now complete() only flipped `status` to 'completed' — the fulfilment media was never
 * stored anywhere, so the marketplace had nothing to show but text rows. The browse page is
 * meant to be a wall of challenge videos, which needs somewhere to keep them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('custom_requests', 'delivery_video_path')) {
                $table->string('delivery_video_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('custom_requests', 'delivery_thumbnail_path')) {
                $table->string('delivery_thumbnail_path')->nullable()->after('delivery_video_path');
            }
            if (!Schema::hasColumn('custom_requests', 'delivered_at')) {
                $table->dateTime('delivered_at')->nullable()->after('delivery_thumbnail_path');
            }
            if (!Schema::hasColumn('custom_requests', 'views_count')) {
                $table->unsignedBigInteger('views_count')->default(0)->after('delivered_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_requests', function (Blueprint $table) {
            foreach (['delivery_video_path', 'delivery_thumbnail_path', 'delivered_at', 'views_count'] as $col) {
                if (Schema::hasColumn('custom_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
