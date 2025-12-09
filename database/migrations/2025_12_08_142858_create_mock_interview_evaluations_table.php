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
        Schema::create('mock_interview_evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_interview_answer_id')->constrained('mock_interview_answers')->cascadeOnDelete();
            $table->string('status')->nullable();
            $table->unsignedSmallInteger('score_total')->nullable();
            $table->unsignedSmallInteger('score_clarity')->nullable();
            $table->unsignedSmallInteger('score_depth')->nullable();
            $table->unsignedSmallInteger('score_practice')->nullable();
            $table->unsignedTinyInteger('score_completeness')->nullable();
            $table->text('strengths')->nullable();
            $table->text('improvements')->nullable();
            $table->enum('evaluator', ['heuristic', 'ai', 'human'])->default('heuristic');
            $table->json('evaluator_meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_interview_evaluations');
    }
};
