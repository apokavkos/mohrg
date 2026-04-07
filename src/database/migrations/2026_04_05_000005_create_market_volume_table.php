<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eic_market_volume', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type_id')->unsigned()->unique();
            $table->bigInteger('weekly_volume')->default(0);
            $table->decimal('avg_price', 20, 2)->default(0);
            $table->timestamp('updated_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eic_market_volume');
    }
};
