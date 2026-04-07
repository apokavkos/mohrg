<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Drop and recreate saved fits to add label field
        Schema::dropIfExists('eic_saved_fit_items');
        Schema::dropIfExists('eic_saved_fits');

        Schema::create('eic_saved_fits', function (Blueprint $table) {
            $table->increments('id'); // The unique ID
            $table->integer('user_id')->unsigned();
            $table->string('name'); // Name of the fit
            $table->string('label')->nullable(); // New label field
            $table->text('fit_text');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('eic_saved_fit_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('fit_id')->unsigned();
            $table->integer('type_id')->unsigned();
            $table->integer('quantity');
            $table->timestamps();

            $table->foreign('fit_id')->references('id')->on('eic_saved_fits')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eic_saved_fit_items');
        Schema::dropIfExists('eic_saved_fits');
    }
};
