<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendance_imports', function (Blueprint $table) {
            $table->id();

            $table->string('filename');
            $table->string('stored_path')->nullable();

            $table->string('file_hash', 64)->nullable()->index();

            $table->unsignedSmallInteger('year')->nullable();
            $table->unsignedTinyInteger('month')->nullable();

            $table->unsignedBigInteger('uploaded_by');

            $table->unsignedBigInteger('total_rows')->default(0);
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->unsignedBigInteger('success_rows')->default(0);
            $table->unsignedBigInteger('duplicate_rows')->default(0);
            $table->unsignedBigInteger('error_rows')->default(0);

            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed'
            ])->default('pending');

            $table->text('error_message')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('uploaded_by')
                ->references('id')
                ->on('users');

            $table->index(['year', 'month']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendance_imports');
    }
};
