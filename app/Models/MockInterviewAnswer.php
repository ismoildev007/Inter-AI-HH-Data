<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockInterviewAnswer extends Model
{
    protected $fillable = [
        'mock_interview_id',
        'mock_interview_question_id',
        'user_id',

        'answer_text',
        'answer_audio',
        'duration_seconds',

        'skipped',
        'stt_meta',

        'status',
        'recommendation',
    ];

    protected $casts = [
        'skipped' => 'boolean',
        'stt_meta' => 'array',
    ];

    public function interview()
    {
        return $this->belongsTo(MockInterview::class, 'mock_interview_id');
    }

    public function question()
    {
        return $this->belongsTo(MockInterviewQuestion::class, 'mock_interview_question_id');
    }

    public function evaluation()
    {
        return $this->hasOne(MockInterviewEvaluation::class, 'mock_interview_answer_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
