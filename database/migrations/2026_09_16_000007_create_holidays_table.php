<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();

            $table->date('holiday_date')->unique();

            $table->string('name');

            $table->enum('type', [
                'holiday',
                'company_off',
                'working_day'
            ])->default('holiday');

            $table->decimal('work_value', 4, 2)
                ->default(0);

            $table->text('note')->nullable();

            $table->timestamps();

        });
    }

    public function down()
    {
        Schema::dropIfExists('holidays');
    }
};
