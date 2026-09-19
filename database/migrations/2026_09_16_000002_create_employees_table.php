<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->string('employee_code', 50)->unique();

            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();

            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('manager_id')->nullable();

            $table->string('position')->nullable();

            $table->date('join_date')->nullable();
            $table->date('leave_date')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
                'resigned'
            ])->default('active');

            $table->timestamps();

            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->foreign('manager_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();

            $table->index(['department_id', 'status']);
            $table->index(['manager_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
