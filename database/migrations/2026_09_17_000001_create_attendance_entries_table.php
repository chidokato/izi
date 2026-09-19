<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_imports', function (Blueprint $t) {
        $t->json('preview')->nullable();
        $t->timestamp('expires_at')->nullable();
        });
        Schema::create('attendance_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('employee_id')->constrained()->restrictOnDelete();
            $t->string('employee_name');
            $t->string('department_name');
            $t->date('work_date');
            $t->time('checkin')->nullable();
            $t->time('checkout')->nullable();
            $t->foreignId('attendance_import_id')->constrained()->restrictOnDelete();
            $t->timestamps();
            $t->unique(['employee_id', 'work_date']);
            $t->index('work_date');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_entries');
        Schema::table('attendance_imports', function (Blueprint $t) {
        $t->dropColumn(['preview', 'expires_at']);
        });
    }
};
