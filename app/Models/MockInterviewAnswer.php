<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockInterviewAnswer extends Model
{
    protected $fillable = [
        'mock_interview_question_id',
        'user_id',
        'answer_text',
        'answer_audio_url',
        'duration_seconds',
        'skipped',
        'stt_meta',
    ];

    protected $casts = [
        'skipped' => 'boolean',
        'stt_meta' => 'array',
    ];

    public function question()
    {
        return $this->belongsTo(MockInterviewQuestion::class, 'mock_interview_question_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
