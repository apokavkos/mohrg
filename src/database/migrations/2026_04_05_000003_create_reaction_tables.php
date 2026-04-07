<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::dropIfExists('eic_reaction_cache');
        Schema::dropIfExists('eic_cost_index_cache');
        Schema::dropIfExists('eic_market_price_cache');
        Schema::dropIfExists('eic_reaction_configs');

        // 1. Structure Configurations
        Schema::create('eic_reaction_configs', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('name');
            $table->string('structure_type')->default('Tatara');
            $table->string('reactor_type')->default('Standup Composite Reactor I');
            $table->string('rig_1')->nullable();
            $table->string('rig_2')->nullable();
            $table->string('rig_3')->nullable();
            $table->string('space_type')->default('nullsec');
            $table->bigInteger('solar_system_id')->unsigned()->default(30000142);
            $table->integer('fuel_block_type_id')->unsigned()->nullable();
            $table->decimal('facility_tax', 5, 2)->default(0.00);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        // 2. Market Price Cache
        Schema::create('eic_market_price_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('type_id')->unsigned();
            $table->bigInteger('region_id')->unsigned();
            $table->decimal('buy_price', 20, 2)->default(0);
            $table->decimal('sell_price', 20, 2)->default(0);
            $table->decimal('adjusted_price', 20, 2)->default(0);
            $table->timestamp('updated_at');

            $table->index(['type_id', 'region_id']);
        });

        // 3. System Cost Index Cache
        Schema::create('eic_cost_index_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->bigInteger('solar_system_id')->unsigned();
            $table->string('activity')->default('reaction');
            $table->decimal('cost_index', 10, 8)->default(0);
            $table->timestamp('updated_at');

            $table->index(['solar_system_id', 'activity']);
        });

        // 4. Reaction Results Cache
        Schema::create('eic_reaction_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('reaction_type_id')->unsigned();
            $table->integer('config_id')->unsigned();
            $table->decimal('input_cost', 20, 2);
            $table->decimal('fuel_cost', 20, 2);
            $table->decimal('tax_cost', 20, 2);
            $table->decimal('output_value', 20, 2);
            $table->decimal('profit', 20, 2);
            $table->decimal('profit_percent', 8, 2);
            $table->timestamp('calculated_at');
            $table->timestamps();

            $table->foreign('config_id')->references('id')->on('eic_reaction_configs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('eic_reaction_cache');
        Schema::dropIfExists('eic_cost_index_cache');
        Schema::dropIfExists('eic_market_price_cache');
        Schema::dropIfExists('eic_reaction_configs');
    }
};
