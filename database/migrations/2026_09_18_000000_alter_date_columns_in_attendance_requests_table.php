<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->dateTime('start_date')->change();
            $table->dateTime('end_date')->change();
            $table->dropColumn(['start_session', 'end_session']);
        });
    }

    public function down()
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('end_date')->change();
            $table->enum('start_session', ['full', 'morning', 'afternoon'])->default('full');
            $table->enum('end_session', ['full', 'morning', 'afternoon'])->default('full');
        });
    }
};
