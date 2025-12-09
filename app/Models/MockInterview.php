<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MockInterview extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'position',
        'language',
        'status',
        'started_at',
        'finished_at',
        'duration_seconds',
        'overall_score', 
        'overall_percentage',
        'strengths',
        'weaknesses',
        'work_on',
        'interview_type'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',

        'strengths' => 'array',
        'weaknesses' => 'array',
        'work_on' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function questions()
    {
        return $this->hasMany(MockInterviewQuestion::class)->orderBy('order');
    }
}
