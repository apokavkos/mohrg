<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Create Fit Groups table
        Schema::create('eic_fit_groups', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. Add group_id to saved fits
        Schema::table('eic_saved_fits', function (Blueprint $table) {
            $table->integer('group_id')->unsigned()->nullable()->after('user_id');
            $table->foreign('group_id')->references('id')->on('eic_fit_groups')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('eic_saved_fits', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
        Schema::dropIfExists('eic_fit_groups');
    }
};
