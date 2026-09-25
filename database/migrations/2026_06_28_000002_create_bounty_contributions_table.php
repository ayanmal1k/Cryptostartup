<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBountyContributionsTable extends Migration
{
    public function up()
    {
        Schema::create('bounty_contributions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bounty_campaign_id');
            $table->unsignedBigInteger('contributor_id');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('held'); // held, released, refunded
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['bounty_campaign_id', 'status']);
            $table->index('contributor_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('bounty_contributions');
    }
}
