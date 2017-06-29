<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCheckinTable extends Migration
{
  /**
   * Run the migrations.
   *
   * @return void
   */
  public function up()
  {
      Schema::create('checkins', function (Blueprint $table) {
        $table->increments('id');
        $table->timestamps();

        $table->integer('user_id')->unsigned();
        $table->foreign('user_id')->references('id')->on('users')
          ->onUpdate('cascade')->onDelete('cascade');
      });
  }

  /**
   * Reverse the migrations.
   *
   * @return void
   */
  public function down()
  {
      Schema::dropIfExists('checkins');
  }
}
