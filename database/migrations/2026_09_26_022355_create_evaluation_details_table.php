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
        Schema::create('evaluation_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluation_id');
            $table->unsignedBigInteger('criteria_id');
            $table->integer('self_score')->default(0);
            $table->integer('manager_score')->default(0);
            $table->integer('hr_score')->default(0);
            $table->timestamps();

            // $table->foreign('evaluation_id')->references('id')->on('evaluations')->onDelete('cascade');
            // $table->foreign('criteria_id')->references('id')->on('evaluation_criteria')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evaluation_details');
    }
};
