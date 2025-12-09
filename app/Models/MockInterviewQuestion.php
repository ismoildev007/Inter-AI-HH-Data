<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockInterviewQuestion extends Model
{
    protected $fillable = [
        'mock_interview_id',
        'order',
        'difficulty',
        'question_text',
        'question_audio_url',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function mockInterview()
    {
        return $this->belongsTo(MockInterview::class);
    }

    public function answers()
    {
        return $this->hasMany(MockInterviewAnswer::class)->orderBy('created_at', 'desc');
    }
}
