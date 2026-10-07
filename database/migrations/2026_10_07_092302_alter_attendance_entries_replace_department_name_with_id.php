<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('attendance_entries', function (Blueprint $t) {
            $t->dropColumn('department_name');
            $t->foreignId('department_id')->nullable()->after('employee_name')->constrained('departments')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('attendance_entries', function (Blueprint $t) {
            $t->dropForeign(['department_id']);
            $t->dropColumn('department_id');
            $t->string('department_name')->nullable();
        });
    }
};
