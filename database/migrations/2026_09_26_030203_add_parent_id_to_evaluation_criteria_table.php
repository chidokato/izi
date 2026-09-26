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
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->after('id');
            $table->integer('max_score')->nullable()->change();
            
            // $table->foreign('parent_id')->references('id')->on('evaluation_criteria')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('evaluation_criteria', function (Blueprint $table) {
            $table->dropColumn('parent_id');
        });
    }
};
