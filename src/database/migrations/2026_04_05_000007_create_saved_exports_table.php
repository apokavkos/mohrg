<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Saved Exports (Shopping Lists)
        Schema::create('eic_saved_exports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('label');
            $table->text('export_text'); // The multibuy format text
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. Saved Export Items (For deduplication logic)
        Schema::create('eic_saved_export_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('export_id')->unsigned();
            $table->integer('type_id')->unsigned();
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('export_id')->references('id')->on('eic_saved_exports')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eic_saved_export_items');
        Schema::dropIfExists('eic_saved_exports');
    }
};
