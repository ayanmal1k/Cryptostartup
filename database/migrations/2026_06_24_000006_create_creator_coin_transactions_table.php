<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Immutable ledger of every points movement (audit trail). `points` is the positive magnitude;
 * `type` determines whether it added or removed points, and `balance_after` snapshots the
 * holder's resulting balance. For purchases, credits_amount/platform_fee record the platform-
 * credit side of the trade and counterparty_user_id is the creator who got paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creator_coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_coin_id');
            $table->unsignedBigInteger('user_id'); // the holder this ledger row belongs to
            $table->enum('type', ['purchase', 'spend', 'grant', 'refund', 'adjust']);
            $table->decimal('points', 24, 8);        // positive magnitude
            $table->decimal('balance_after', 24, 8); // holder balance after this row
            $table->decimal('credits_amount', 18, 8)->nullable(); // platform credits moved (purchases)
            $table->decimal('platform_fee', 18, 8)->nullable();   // platform cut (purchases)
            $table->unsignedBigInteger('counterparty_user_id')->nullable(); // e.g. creator paid
            $table->string('reference_type')->nullable(); // optional: what points were spent on
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['creator_coin_id', 'user_id']);
            $table->index('type');
            $table->foreign('creator_coin_id')->references('id')->on('creator_coins')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creator_coin_transactions');
    }
};
