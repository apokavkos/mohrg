<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_item_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hub_id')->index();
            $table->unsignedInteger('type_id')->index();
            $table->string('type_name', 200)->nullable();
            // Local hub prices (from imported CSV)
            $table->decimal('local_sell_price', 20, 2)->default(0);
            $table->decimal('local_buy_price', 20, 2)->default(0);
            // Jita reference prices (source market)
            $table->decimal('jita_sell_price', 20, 2)->default(0);
            $table->decimal('jita_buy_price', 20, 2)->default(0);
            // Inventory/volume
            $table->unsignedBigInteger('current_stock')->default(0);
            $table->decimal('weekly_volume', 14, 4)->default(0);
            // volume_m3 is the EVE packaged item volume from invTypes.volume
            $table->decimal('volume_m3', 14, 4)->default(0);
            // Derived metrics (pre-computed for query performance)
            $table->decimal('import_cost', 20, 2)->default(0);   // volume_m3 × isk_per_m3
            $table->decimal('markup_pct', 10, 4)->default(0);    // % above Jita after import cost
            $table->decimal('weekly_profit', 20, 2)->default(0); // ISK profit per week
            $table->date('data_date')->nullable()->index();
            $table->timestamps();

            $table->foreign('hub_id')->references('id')->on('market_hubs')->onDelete('cascade');
            // Only one record per hub/item/day; reimporting the same day overwrites via upsert
            $table->unique(['hub_id', 'type_id', 'data_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_item_data');
    }
};
