<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('evaluation_criteria', 'target_types')) {
            Schema::table('evaluation_criteria', function (Blueprint $table) {
                $table->json('target_types')->nullable()->after('max_score');
            });
        }

        if (!Schema::hasColumn('employees', 'evaluation_type')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->string('evaluation_type', 50)->nullable()->default('office')->after('position');
            });
        }

        if (!Schema::hasColumn('evaluations', 'evaluation_type')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->string('evaluation_type', 50)->nullable()->after('employee_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evaluation_criteria', 'target_types')) {
            Schema::table('evaluation_criteria', function (Blueprint $table) {
                $table->dropColumn('target_types');
            });
        }

        if (Schema::hasColumn('employees', 'evaluation_type')) {
            Schema::table('employees', function (Blueprint $table) {
                $table->dropColumn('evaluation_type');
            });
        }

        if (Schema::hasColumn('evaluations', 'evaluation_type')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->dropColumn('evaluation_type');
            });
        }
    }
};
