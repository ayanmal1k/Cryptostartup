<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserAchievementsTable extends Migration
{
    public function up()
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('achievement_key');
            $table->dateTime('unlocked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_key']);
            $table->index('user_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_achievements');
    }
}
