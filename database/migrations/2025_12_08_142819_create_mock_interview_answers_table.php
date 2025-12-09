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
        Schema::create('mock_interview_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mock_interview_question_id')->constrained('mock_interview_questions')->nullOnDelete();
            $table->foreignId('user_id')->constrained()->nullOnDelete();
            $table->text('answer_text')->nullable();
            $table->string('answer_audio_url')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->boolean('skipped')->default(false);
            $table->json('stt_meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mock_interview_answers');
    }
};
