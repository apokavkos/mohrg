<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Market Hubs (Configurable Staging Hubs)
        Schema::create('eic_market_hubs', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('hub_id')->unsigned()->unique(); // Region or Structure ID
            $table->string('name');
            $table->string('type'); // 'region' or 'structure'
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        // 2. Market Snapshots (Current stock levels per hub)
        Schema::create('eic_market_snapshots', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('hub_id')->unsigned();
            $table->integer('type_id')->unsigned();
            $table->bigInteger('quantity')->default(0);
            $table->decimal('lowest_sell', 20, 2)->default(0);
            $table->timestamps();

            $table->index(['hub_id', 'type_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('eic_market_snapshots');
        Schema::dropIfExists('eic_market_hubs');
    }
};
