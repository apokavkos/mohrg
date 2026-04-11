<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('market_import_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hub_id')->nullable()->index();
            $table->string('source', 50)->default('fuzzwork_csv');
            $table->string('filename', 500)->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedBigInteger('rows_processed')->default(0);
            $table->unsignedBigInteger('rows_failed')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // hub_id set null on hub delete so we keep the log history
            $table->foreign('hub_id')->references('id')->on('market_hubs')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('market_import_logs');
    }
};
