<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('monthly_attendances', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->decimal('standard_days', 6, 2)->default(0);

            $table->decimal('actual_days', 6, 2)->default(0);

            $table->decimal('paid_leave_days', 6, 2)->default(0);
            $table->decimal('unpaid_leave_days', 6, 2)->default(0);

            $table->decimal('business_trip_days', 6, 2)->default(0);
            $table->decimal('work_from_home_days', 6, 2)->default(0);

            $table->unsignedInteger('late_count')->default(0);
            $table->unsignedInteger('late_minutes')->default(0);

            $table->unsignedInteger('early_count')->default(0);
            $table->unsignedInteger('early_minutes')->default(0);

            $table->decimal('absent_days', 6, 2)->default(0);

            $table->decimal('total_work', 6, 2)->default(0);

            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();

            $table->unique([
                'employee_id',
                'year',
                'month'
            ], 'monthly_employee_period_unique');

            $table->index([
                'year',
                'month'
            ], 'monthly_period_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('monthly_attendances');
    }
};
