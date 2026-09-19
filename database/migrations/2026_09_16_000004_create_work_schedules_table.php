<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();

            $table->string('code', 50)->unique();
            $table->string('name');

            $table->text('description')->nullable();

            $table->enum('status', [
                'active',
                'inactive'
            ])->default('active');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('work_schedules');
    }
};
