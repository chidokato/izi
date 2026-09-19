<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('action', 100);

            $table->string('model_type', 150)->nullable();
            $table->unsignedBigInteger('model_id')->nullable();

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->index('created_at');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index([
                'model_type',
                'model_id'
            ], 'audit_model_index');

            $table->index([
                'user_id',
                'created_at'
            ], 'audit_user_date_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
};
