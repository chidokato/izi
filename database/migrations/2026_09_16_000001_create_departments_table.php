<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('parent_id')->nullable();

            $table->string('code', 50)->nullable()->unique();
            $table->string('name');

            $table->unsignedInteger('sort_order')->default(0);

            $table->enum('status', ['active', 'inactive'])
                ->default('active');

            $table->timestamps();

            $table->foreign('parent_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();

            $table->index(['parent_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('departments');
    }
};
