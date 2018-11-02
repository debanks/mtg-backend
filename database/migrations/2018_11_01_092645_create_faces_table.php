<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateFacesTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {

        Schema::create('faces', function (Blueprint $table) {

            $table->increments('id');
            $table->integer('card_id')->unsigned()->index();
            $table->string('name');
            $table->string('power', 2)->nullable();
            $table->string('toughness', 2)->nullable();
            $table->integer('total_cost');
            $table->string('cost_text', 32);
            $table->string('colors', 32);
            $table->string('type');
            $table->text('text');
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

        Schema::dropIfExists('faces');
    }
}
