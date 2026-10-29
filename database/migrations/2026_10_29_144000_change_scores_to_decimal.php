<?php

use Illuminate\Database\Migrations\Migration;
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
        DB::statement("ALTER TABLE evaluation_criteria MODIFY max_score DECIMAL(8, 2) NULL");
        DB::statement("ALTER TABLE evaluation_details MODIFY self_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_details MODIFY manager_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_details MODIFY hr_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY self_total_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY manager_total_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY hr_total_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY final_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_grades MODIFY min_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_grades MODIFY max_score DECIMAL(8, 2) NOT NULL DEFAULT 0");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE evaluation_criteria MODIFY max_score INT NULL");
        DB::statement("ALTER TABLE evaluation_details MODIFY self_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_details MODIFY manager_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_details MODIFY hr_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY self_total_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY manager_total_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY hr_total_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluations MODIFY final_score INT NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE evaluation_grades MODIFY min_score INT NOT NULL");
        DB::statement("ALTER TABLE evaluation_grades MODIFY max_score INT NOT NULL");
    }
};
