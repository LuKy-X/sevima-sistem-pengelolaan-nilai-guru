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
        Schema::create('assessment_columns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gradebook_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 30);
            $table->decimal('weight', 5, 2)->default(0.00);
            $table->decimal('max_score', 5, 2)->default(100.00);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->index(['gradebook_id', 'order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_columns');
    }
};
