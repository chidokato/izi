<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_schedule_rules', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('work_schedule_id');

            $table->unsignedTinyInteger('day_of_week');

            $table->boolean('is_working_day')->default(true);

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();

            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();

            $table->unsignedInteger('required_minutes')->default(0);

            $table->decimal('work_value', 4, 2)->default(1);

            $table->unsignedInteger('late_tolerance_minutes')->default(0);

            $table->timestamps();

            $table->foreign('work_schedule_id')
                ->references('id')
                ->on('work_schedules')
                ->restrictOnDelete();

            $table->unique([
                'work_schedule_id',
                'day_of_week'
            ], 'schedule_day_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_schedule_rules');
    }
};
