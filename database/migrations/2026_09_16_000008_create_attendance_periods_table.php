<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_periods', function (Blueprint $table) {
            $table->id();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');

            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', [
                'open',
                'locked'
            ])->default('open');

            $table->unsignedBigInteger('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->timestamps();

            $table->foreign('locked_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->unique(['year', 'month']);
            $table->index(['status', 'start_date', 'end_date']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_periods');
    }
};
