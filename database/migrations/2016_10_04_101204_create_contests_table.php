<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateContestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contests', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('competition_id');
            $table->string('type');
            $table->integer('entrants');
            $table->integer('entry_fee');
            $table->integer('award_id');
            $table->timestamp('start_at');
            $table->timestamp('est_end_at');
            $table->timestamp('end_at');
            $table->integer('is_public');
            $table->string('status')->default('Created');
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
        Schema::dropIfExists('contests');
    }
}
