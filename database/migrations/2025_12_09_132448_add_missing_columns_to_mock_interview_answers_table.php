<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mock_interview_answers', function (Blueprint $table) {
            if (Schema::hasColumn('mock_interview_answers', 'answer_audio_url')) {
                $table->dropColumn('answer_audio_url');
            }

            if (!Schema::hasColumn('mock_interview_answers', 'mock_interview_id')) {
                $table->foreignId('mock_interview_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('mock_interviews')
                    ->onDelete('cascade');
            }

            if (!Schema::hasColumn('mock_interview_answers', 'answer_audio')) {
                $table->string('answer_audio')
                    ->nullable()
                    ->after('answer_text');
            }

            if (!Schema::hasColumn('mock_interview_answers', 'duration_seconds')) {
                $table->integer('duration_seconds')
                    ->nullable()
                    ->after('answer_audio');
            }

            if (!Schema::hasColumn('mock_interview_answers', 'status')) {
                $table->enum('status', ['pending', 'completed'])
                    ->default('pending')
                    ->after('skipped');
            }

            if (!Schema::hasColumn('mock_interview_answers', 'recommendation')) {
                $table->longText('recommendation')
                    ->nullable()
                    ->after('answer_text');
            }
        });
    }

    public function down(): void
    {
        Schema::table('mock_interview_answers', function (Blueprint $table) {
            $table->dropColumn([
                'mock_interview_id',
                'answer_audio',
                'duration_seconds',
                'status',
                'recommendation',
            ]);
        });
    }
};
