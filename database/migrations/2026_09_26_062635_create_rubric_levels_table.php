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
        Schema::create('rubric_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_criterion_id')->constrained('rubric_criteria')->cascadeOnDelete();
            $table->unsignedTinyInteger('level_number');
            $table->string('name', 100);
            $table->decimal('score', 5, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['rubric_criterion_id', 'level_number'], 'criterion_level_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubric_levels');
    }
};
