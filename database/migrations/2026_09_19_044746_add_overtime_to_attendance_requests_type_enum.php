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
        Schema::table('attendance_requests', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });
    }

    public function down()
    {
        Schema::table('attendance_requests', function (Blueprint $table) {
            // Reverting to ENUM could be lossy if there are 'overtime' records, so keeping it string or ignoring is safer.
            // $table->enum('type', ['paid_leave', 'unpaid_leave', 'business_trip', 'attendance_adjustment', 'work_from_home', 'other'])->change();
        });
    }
};
