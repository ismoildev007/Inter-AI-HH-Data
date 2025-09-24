<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InterviewPreparation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'interview_id',
        'question',
    ];

    public function interview()
    {
        return $this->belongsTo(Interview::class);
    }
}
