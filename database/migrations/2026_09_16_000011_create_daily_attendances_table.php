<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('daily_attendances', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('employee_id');

            $table->date('work_date');

            $table->dateTime('first_checkin')->nullable();
            $table->dateTime('last_checkout')->nullable();

            $table->unsignedInteger('work_minutes')->default(0);

            $table->unsignedInteger('late_minutes')->default(0);
            $table->unsignedInteger('early_minutes')->default(0);

            $table->decimal('work_value', 4, 2)->default(0);

            $table->enum('status', [
                'present',
                'absent',
                'late',
                'early_leave',
                'missing_checkin',
                'missing_checkout',
                'leave',
                'business_trip',
                'work_from_home',
                'holiday',
                'off'
            ])->default('absent');

            $table->boolean('has_request')->default(false);

            $table->text('note')->nullable();

            $table->timestamp('calculated_at')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();

            $table->unique([
                'employee_id',
                'work_date'
            ], 'daily_employee_date_unique');

            $table->index([
                'work_date',
                'status'
            ], 'daily_date_status_index');

        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_attendances');
    }
};
