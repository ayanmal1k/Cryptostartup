<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A fan's current points balance for a given creator coin. One row per (coin, holder).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_coin_balances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_coin_id');
            $table->unsignedBigInteger('user_id'); // the holder (fan)
            $table->decimal('balance', 24, 8)->default(0);
            $table->timestamps();

            $table->unique(['creator_coin_id', 'user_id']);
            $table->index('user_id');
            $table->foreign('creator_coin_id')->references('id')->on('creator_coins')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_coin_balances');
    }
};
