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
        Schema::create('mock_interview_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_interview_id')->constrained('mock_interviews')->cascadeOnDelete();
            $table->unsignedSmallInteger('order')->default(1);
            $table->unsignedSmallInteger('difficulty')->nullable();
            $table->text('question_text');
            $table->string('question_audio_url')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_interview_questions');
    }
};
