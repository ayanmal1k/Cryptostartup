<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rewards hub, round two: a free daily spin, two free skill games, and a perk shop.
 *
 * reward_plays   one row per spin or game play (started and finished), so daily limits
 *                are enforced in the database and a finished play cannot pay out twice.
 * perk_purchases what was bought with money in the shop. Only fixed-price, no-chance
 *                items are sold (streak freezes, cosmetic frames) — spins, games and drops
 *                are never for sale, which keeps paid play out of gambling territory.
 * users.equipped_frame  the cosmetic avatar frame a user has chosen to show.
 *
 * Defensive like the earlier engagement migration: safe to re-run.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reward_plays')) {
            Schema::create('reward_plays', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('game', 24);          // spin | tap | memory
                $table->date('play_date');
                $table->string('token', 64)->nullable();
                $table->string('result_key', 64)->nullable();
                $table->integer('score')->nullable();
                $table->integer('xp')->default(0);
                $table->integer('sparks')->default(0);
                $table->dateTime('started_at')->nullable();
                $table->dateTime('finished_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'game', 'play_date']);
            });
        }

        if (!Schema::hasTable('perk_purchases')) {
            Schema::create('perk_purchases', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('perk_key', 64);
                $table->decimal('price', 10, 2);
                $table->timestamps();

                $table->index('user_id');
            });
        }

        if (!Schema::hasColumn('users', 'equipped_frame')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('equipped_frame', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reward_plays');
        Schema::dropIfExists('perk_purchases');
        if (Schema::hasColumn('users', 'equipped_frame')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('equipped_frame');
            });
        }
    }
};
