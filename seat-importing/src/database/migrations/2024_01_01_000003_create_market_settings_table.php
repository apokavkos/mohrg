<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_settings', function (Blueprint $table) {
            $table->id();
            // NULL hub_id = global setting; a specific ID scopes it to one hub
            $table->unsignedBigInteger('hub_id')->nullable()->index();
            $table->string('key', 100)->index();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('hub_id')->references('id')->on('market_hubs')->onDelete('cascade');
            $table->unique(['hub_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_settings');
    }
};
