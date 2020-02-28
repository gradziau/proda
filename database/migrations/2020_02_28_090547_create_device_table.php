<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDeviceTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('status');
            $table->string('organisation_id');
            $table->string('client_id');
            $table->string('one_time_activation_code');
            $table->string('key_status');
            $table->string('key_expiry')->nullable()->default(null);
            $table->string('private_key');
            $table->string('public_key_modulus');
            $table->json('json_web_key');
            $table->string('json_algorithm');
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
        Schema::dropIfExists('devices');
    }
}
