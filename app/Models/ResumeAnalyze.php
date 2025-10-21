<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumeAnalyze extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'demo_resume_id',
        'strengths',
        'weaknesses',
        'keywords',
        'language',
        'skills',
        'title',
    ];

    protected $casts = [
        'skills'     => 'array',
        'strengths'  => 'array',
        'weaknesses' => 'array',
        'keywords'   => 'array',
    ];


    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
    public function demoResume()
    {
        return $this->belongsTo(DemoResume::class);
    }
}
