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
        if (\Illuminate\Support\Facades\Schema::hasTable('posts')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->longText('what_to_learn')->nullable()->after('content');
                $table->longText('course_includes')->nullable()->after('what_to_learn');
                $table->longText('course_requirements')->nullable()->after('course_includes');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['what_to_learn', 'course_includes', 'course_requirements']);
        });
    }
};
