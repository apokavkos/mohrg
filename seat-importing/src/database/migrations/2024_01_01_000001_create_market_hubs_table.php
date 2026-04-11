<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_hubs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->unsignedBigInteger('solar_system_id')->nullable()->index();
            $table->unsignedBigInteger('structure_id')->nullable()->index();
            $table->unsignedBigInteger('region_id')->nullable()->index();
            // ISK per m³ freight cost for this specific hub
            $table->decimal('isk_per_m3', 14, 2)->default(1000.00);
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_hubs');
    }
};
