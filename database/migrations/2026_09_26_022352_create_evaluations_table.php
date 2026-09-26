<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->integer('month');
            $table->integer('year');
            $table->string('status')->default('draft'); // draft, submitted, manager_reviewed, hr_approved
            $table->integer('self_total_score')->default(0);
            $table->integer('manager_total_score')->default(0);
            $table->integer('hr_total_score')->default(0);
            $table->integer('final_score')->default(0);
            $table->unsignedBigInteger('grade_id')->nullable();
            
            $table->text('self_note')->nullable();
            $table->text('manager_note')->nullable();
            $table->text('hr_note')->nullable();
            
            $table->timestamps();

            // Khóa ngoại
            // $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            // $table->foreign('grade_id')->references('id')->on('evaluation_grades')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluations');
    }
};
