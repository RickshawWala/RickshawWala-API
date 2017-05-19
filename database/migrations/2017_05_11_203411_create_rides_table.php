<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRidesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rides', function (Blueprint $table) {
            $table->increments('id');
            $table->string('status')->default('created'); // possible values: created, cancelled, accepted, payment_pending, payment_completed
            $table->string('client_user_id')->unsigned();
            $table->integer('driver_user_id')->unsigned()->nullable();
            $table->double('origin_latitude');
            $table->double('origin_longitude');
            $table->double('destination_latitude');
            $table->double('destination_longitude');
            $table->float('fare')->nullable();
            $table->foreign('client_user_id')
                  ->references('id')->on('users');
            $table->foreign('driver_user_id')
                  ->references('id')->on('users');
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
        Schema::dropIfExists('rides');
    }
}
