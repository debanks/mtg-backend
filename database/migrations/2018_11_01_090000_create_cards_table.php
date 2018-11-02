<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCardsTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('cards', function (Blueprint $table) {

            $table->increments('id');
            $table->string('set', 16);
            $table->string('set_name', 64);
            $table->string('name');
            $table->string('rarity', 32);
            $table->string('cost_text', 32);
            $table->string('colors', 32);
            $table->string('image');
            $table->integer('value')->default(0);
            $table->string('arena_class')->default('dud');
            $table->timestamp('created_at')->default(DB::raw('CURRENT_TIMESTAMP'))->index();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {

        Schema::dropIfExists('cards');
    }
}
