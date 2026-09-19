<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');

            $table->dateTime('attendance_time');

            $table->string('device_id', 100)
                ->default('');

            $table->unsignedBigInteger('attendance_import_id')
                ->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();

            $table->foreign('attendance_import_id')
                ->references('id')
                ->on('attendance_imports')
                ->nullOnDelete();



            $table->index('attendance_time');


            $table->unique([
                'employee_id',
                'attendance_time',
                'device_id'
            ], 'attendance_log_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_logs');
    }
};
