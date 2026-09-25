<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The engagement layer: an earned currency (sparks), a daily quest loop, drops
 * (loot boxes), a reward inventory, and an XP audit log.
 *
 * Every column and table is added defensively -- this runs against a live database
 * that has been migrated by hand before, so re-running must not explode.
 */
class CreateEngagementLayerTables extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'sparks')) {
                // Earned-only soft currency. Deliberately not purchasable: a loot box
                // bought with money is a gambling product in a number of jurisdictions
                // and the card networks treat it as one. Earned boxes are not.
                $table->unsignedInteger('sparks')->default(0);
            }
            if (!Schema::hasColumn('users', 'lifetime_sparks')) {
                $table->unsignedInteger('lifetime_sparks')->default(0);
            }
            if (!Schema::hasColumn('users', 'streak_freezes')) {
                $table->unsignedSmallInteger('streak_freezes')->default(0);
            }
            if (!Schema::hasColumn('users', 'streak_frozen_on')) {
                $table->date('streak_frozen_on')->nullable();
            }
            if (!Schema::hasColumn('users', 'drop_pity')) {
                // Rolls since the last rare-or-better. Guarantees the streak of bad luck
                // ends, which is what keeps a variable-ratio schedule from feeling rigged.
                $table->unsignedInteger('drop_pity')->default(0);
            }
            if (!Schema::hasColumn('users', 'quests_generated_on')) {
                $table->date('quests_generated_on')->nullable();
            }
        });

        if (!Schema::hasTable('xp_events')) {
            Schema::create('xp_events', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('action', 64);
                $table->integer('xp')->default(0);
                $table->integer('sparks')->default(0);
                $table->string('subject_type', 64)->nullable();
                $table->string('subject_id', 64)->nullable();
                $table->timestamps();

                // The daily-cap lookup is (user, action, created_at) -- it runs on every
                // awardable action, so it gets its own composite index.
                $table->index(['user_id', 'action', 'created_at'], 'xp_events_cap_idx');
                $table->index(['user_id', 'created_at']);
            });
        }

        if (!Schema::hasTable('user_quests')) {
            Schema::create('user_quests', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('quest_key', 64);
                $table->date('quest_date');
                $table->unsignedInteger('target')->default(1);
                $table->unsignedInteger('progress')->default(0);
                $table->dateTime('completed_at')->nullable();
                $table->dateTime('claimed_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'quest_key', 'quest_date'], 'user_quests_unique');
                $table->index(['user_id', 'quest_date']);
            });
        }

        if (!Schema::hasTable('user_drops')) {
            Schema::create('user_drops', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('tier', 24)->default('common');
                $table->string('source', 32)->default('quest');
                $table->string('reward_key', 64)->nullable();
                $table->string('reward_rarity', 24)->nullable();
                $table->text('reward_payload')->nullable();
                $table->dateTime('opened_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'opened_at']);
            });
        }

        if (!Schema::hasTable('user_rewards')) {
            Schema::create('user_rewards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id');
                $table->string('reward_key', 64);
                $table->unsignedInteger('quantity')->default(1);
                $table->dateTime('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'reward_key'], 'user_rewards_unique');
                $table->index('user_id');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('user_drops');
        Schema::dropIfExists('user_quests');
        Schema::dropIfExists('xp_events');

        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'sparks', 'lifetime_sparks', 'streak_freezes',
                'streak_frozen_on', 'drop_pity', 'quests_generated_on',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
