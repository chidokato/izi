<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evaluation_details', function (Blueprint $table) {
            $table->boolean('is_self_selected')->default(false)->after('self_score');
            $table->boolean('is_manager_selected')->default(false)->after('manager_score');
            $table->boolean('is_hr_selected')->default(false)->after('hr_score');
        });

        // Cập nhật dữ liệu cũ: nếu điểm > 0 thì đánh dấu là đã chọn
        DB::table('evaluation_details')->where('self_score', '>', 0)->update(['is_self_selected' => true]);
        DB::table('evaluation_details')->where('manager_score', '>', 0)->update(['is_manager_selected' => true]);
        DB::table('evaluation_details')->where('hr_score', '>', 0)->update(['is_hr_selected' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evaluation_details', function (Blueprint $table) {
            $table->dropColumn(['is_self_selected', 'is_manager_selected', 'is_hr_selected']);
        });
    }
};
