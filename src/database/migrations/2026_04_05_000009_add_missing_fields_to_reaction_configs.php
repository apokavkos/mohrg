<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('eic_reaction_configs', function (Blueprint $table) {
            $table->string('input_method')->default('sell')->after('solar_system_id');
            $table->string('output_method')->default('sell')->after('input_method');
            $table->integer('skill_level')->default(5)->after('output_method');
            $table->string('system_name')->default('Jita')->after('skill_level');
            $table->integer('runs')->default(1)->after('system_name');
        });
    }

    public function down()
    {
        Schema::table('eic_reaction_configs', function (Blueprint $table) {
            $table->dropColumn(['input_method', 'output_method', 'skill_level', 'system_name', 'runs']);
        });
    }
};
