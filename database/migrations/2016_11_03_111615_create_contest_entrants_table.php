<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContestEntrantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contest_entrants', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('contest_id');
            $table->integer('user_id');
            $table->integer('points');
            $table->integer('rank');
            $table->integer('is_active');
            $table->integer('award_claimed');
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
        Schema::dropIfExists('contest_entrants');
    }
}
