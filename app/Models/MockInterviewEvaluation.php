<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockInterviewEvaluation extends Model
{
    protected $fillable = [
        'mock_interview_answer_id',
        'status',
        'score_total',
        'score_clarity',
        'score_depth',
        'score_practice',
        'score_completeness',
        'strengths',
        'improvements',
        'evaluator',
        'evaluator_meta',
    ];

    protected $casts = [
        'strengths' => 'array',
        'improvements' => 'array',
        'evaluator_meta' => 'array',
    ];

    public function mockInterview()
    {
        return $this->belongsTo(MockInterview::class);
    }

    public function answer()
    {
        return $this->belongsTo(MockInterviewAnswer::class, 'mock_interview_answer_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }
}
