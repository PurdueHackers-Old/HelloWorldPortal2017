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


          //Create questions list
          $table->string('class_year');
          $table->string('grad_year');
          $table->string('major');
          $table->string('referral');
          $table->integer('hackathon_count');
          $table->string('shirt_size');
          $table->string('dietary_restrictions')->nullable();
          $table->string('website')->nullable();
          $table->text('longanswer_1');
          $table->text('longanswer_2');

          $table->string('status_internal');
          $table->string('status_public');
          $table->timestamp('published_timestamp')->nullable();
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
