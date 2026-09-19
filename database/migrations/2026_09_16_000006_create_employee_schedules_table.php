<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('work_schedule_id');

            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();

            $table->foreign('work_schedule_id')
                ->references('id')
                ->on('work_schedules')
                ->restrictOnDelete();

            $table->unique(['employee_id', 'effective_from'], 'employee_schedule_start_unique');

            $table->index([
                'employee_id',
                'effective_from',
                'effective_to'
            ], 'employee_schedule_date_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_schedules');
    }
};
