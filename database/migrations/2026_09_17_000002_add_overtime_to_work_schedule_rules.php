<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('work_schedule_rules', function (Blueprint $table) {
            $table->time('ot_start')->nullable();
            $table->time('ot_end')->nullable();
            $table->boolean('ot_next_day')->default(false);
        });
    }

    public function down()
    {
        Schema::table('work_schedule_rules', function (Blueprint $table) {
        $table->dropColumn(['ot_start', 'ot_end', 'ot_next_day']);
        });
    }
};
