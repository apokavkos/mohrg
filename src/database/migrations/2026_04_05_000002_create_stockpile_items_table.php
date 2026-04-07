<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eic_stockpile_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stockpile_id')->unsigned();
            $table->integer('type_id');
            $table->bigInteger('quantity');
            $table->timestamps();

            $table->foreign('stockpile_id')->references('id')->on('eic_stockpiles')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eic_stockpile_items');
    }
};
