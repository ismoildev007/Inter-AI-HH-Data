<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResumeExperience extends Model
{
    use HasFactory;

    protected $table = 'resume_experiences';

    protected $fillable = [
        'resume_id',
        'position',
        'company',
        'location',
        'start_date',
        'end_date',
        'is_current',
        'description',
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
