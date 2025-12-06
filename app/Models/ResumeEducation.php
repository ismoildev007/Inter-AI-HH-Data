<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumeEducation extends Model
{
    use HasFactory;

    protected $table = 'resume_educations';

    protected $fillable = [
        'resume_id',
        'degree',
        'institution',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'extra_info',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'bool',
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
