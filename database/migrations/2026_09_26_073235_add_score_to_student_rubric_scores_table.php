<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('student_rubric_scores', function (Blueprint $table) {
            $table->foreignId('rubric_level_id')->nullable()->change();
            $table->decimal('score', 5, 2)->nullable()->after('rubric_level_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_rubric_scores', function (Blueprint $table) {
            $table->dropColumn('score');
            $table->foreignId('rubric_level_id')->nullable(false)->change();
        });
    }
};
