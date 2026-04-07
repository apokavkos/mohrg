<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add reference_url to eic_saved_fits
        Schema::table('eic_saved_fits', function (Blueprint $table) {
            $table->string('reference_url')->nullable()->after('label');
        });
    }

    public function down()
    {
        Schema::table('eic_saved_fits', function (Blueprint $table) {
            $table->dropColumn('reference_url');
        });
    }
};
