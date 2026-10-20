<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creator Coins = non-cashable per-creator loyalty points (NOT a tradeable security).
 *
 * Deliberately has NO price-discovery / market-cap / supply-speculation fields. A coin only
 * has a fixed purchase price in platform credits set by the creator. Fans buy points with
 * platform credits and spend them on that creator's perks; points can never be converted back
 * to money or transferred between fans. The creator earns withdrawable platform credits on each
 * purchase (minus platform fee) — that is the only money path, and it already exists.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_coins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_user_id');
            $table->string('name');
            $table->string('symbol', 16);
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            // Cost of ONE point, in platform credits. Fixed by the creator (within configured bounds).
            $table->decimal('price_per_point', 18, 8)->default(1);
            // Platform's cut of each purchase, in percent.
            $table->decimal('platform_fee_percentage', 5, 2)->default(0);
            // Running total of points ever issued (display/analytics only).
            $table->decimal('points_issued', 24, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('creator_user_id'); // one coin per creator
            $table->foreign('creator_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_coins');
    }
};
