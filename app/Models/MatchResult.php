<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'resume_id',
        'vacancy_id',
        'score_percent',
        'explanations',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }
}
