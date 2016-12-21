<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePlayerLineupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('player_lineups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('competition_id');
            $table->integer('match_id');
            $table->integer('team_id');
            $table->integer('player_id');
            $table->string('position');
            $table->string('shirt_number');
            $table->string('status');
            $table->float('points')->default(0);
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
        Schema::dropIfExists('player_lineups');
    }
}
