<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBountyCampaignsTable extends Migration
{
    public function up()
    {
        Schema::create('bounty_campaigns', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('creator_id'); // initiator (a platform user)
            $table->string('target_name');             // e.g. "Kim Kardashian"
            $table->string('target_handle')->nullable();
            $table->text('target_description')->nullable();
            $table->string('target_avatar')->nullable();
            $table->decimal('goal_amount', 12, 2)->default(0);
            $table->decimal('current_amount', 12, 2)->default(0);
            $table->dateTime('deadline')->nullable();
            // open, claim_pending, released, refunded, expired, cancelled
            $table->string('status')->default('open');
            $table->unsignedBigInteger('claimed_by_user_id')->nullable();
            // none, pending, approved, rejected
            $table->string('claim_status')->default('none');
            $table->text('claim_message')->nullable();
            $table->boolean('funds_released')->default(false);
            $table->dateTime('funds_released_at')->nullable();
            $table->text('moderator_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('claim_status');
            $table->index('creator_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bounty_campaigns');
    }
}
