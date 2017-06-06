<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('applications', function (Blueprint $table) {
          $table->increments('id');
          $table->integer('user_id')->unsigned();

          $table->foreign('user_id')->references('id')->on('users')
            ->onDelete('cascade')->onUpdate('cascade');

          $table->string('sampleQuestion');
          $table->string('status');
          $table->timestamp('emailSent')->nullable();
          $table->string('last_email_status');
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
      Schema::dropIfExists('applications');
    }
}
