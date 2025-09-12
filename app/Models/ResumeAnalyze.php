<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumeAnalyze extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'strengths',
        'weaknesses',
        'keywords',
        'language',
        'skills',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
