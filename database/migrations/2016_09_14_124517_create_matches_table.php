<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMatchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('competition_id');
            $table->string('uID');
            $table->string('group_name');
            $table->string('match_day');
            $table->string('match_type');
            $table->string('round_number');
            $table->string('round_type');
            $table->string('venue');
            $table->string('city');
            $table->string('team_1');
            $table->string('team_2');
            $table->string('home_team');
            $table->string('scheduled_on');
            $table->string('entry_type');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('matches');
    }
}
