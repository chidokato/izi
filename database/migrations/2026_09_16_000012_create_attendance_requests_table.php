<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_requests', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->string('code', 50)->unique();

            $table->unsignedBigInteger('employee_id');

            $table->enum('type', [
                'paid_leave',
                'unpaid_leave',
                'business_trip',
                'attendance_adjustment',
                'work_from_home',
                'other'
            ]);

            $table->date('start_date');
            $table->date('end_date');

            $table->enum('start_session', [
                'full',
                'morning',
                'afternoon'
            ])->default('full');

            $table->enum('end_session', [
                'full',
                'morning',
                'afternoon'
            ])->default('full');

            $table->text('reason');

            $table->string('attachment')->nullable();


            $table->dateTime('requested_checkin')->nullable();
            $table->dateTime('requested_checkout')->nullable();

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'cancelled'
            ])->default('pending');

            $table->unsignedBigInteger('current_approval_step')
                ->default(1);

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();

            $table->timestamps();

            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->restrictOnDelete();

            $table->index([
                'employee_id',
                'start_date',
                'end_date'
            ], 'request_employee_date_index');

            $table->index([
                'status',
                'created_at'
            ], 'request_status_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_requests');
    }
};
