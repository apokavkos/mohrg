<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('eic_stockpiles', function (Blueprint $table) {
            $table->bigInteger('location_id')->unsigned()->nullable();
        });
    }

    public function down()
    {
        Schema::table('eic_stockpiles', function (Blueprint $table) {
            $table->dropColumn('location_id');
        });
    }
};
