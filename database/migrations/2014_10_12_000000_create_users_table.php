<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role', 32)->default('user');
            $table->text('profile_photo_url')->nullable();
            $table->string('description','256')->nullable();
            $table->string('bo4_name', 256)->nullable();
            $table->string('server', 32)->nullable();
            $table->integer('squad_id')->index()->default(1);
            $table->tinyInteger('active')->default(1);
            $table->integer('primary_user_id')->unsigned()->nullable();
            $table->rememberToken();
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
        Schema::drop('users');
    }
}
