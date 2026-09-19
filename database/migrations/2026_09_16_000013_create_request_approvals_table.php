<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('request_approvals', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('request_id');

            $table->unsignedBigInteger('approver_id');

            $table->unsignedInteger('step');

            $table->enum('status', [
                'pending',
                'approved',
                'rejected',
                'skipped'
            ])->default('pending');

            $table->text('comment')->nullable();

            $table->timestamp('acted_at')->nullable();

            $table->timestamps();

            $table->foreign('request_id')
                ->references('id')
                ->on('attendance_requests')
                ->restrictOnDelete();

            $table->foreign('approver_id')
                ->references('id')
                ->on('users')
                ->restrictOnDelete();

            $table->unique([
                'request_id',
                'step'
            ], 'request_approval_step_unique');

            $table->index([
                'approver_id',
                'status'
            ], 'approval_approver_status_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('request_approvals');
    }
};
