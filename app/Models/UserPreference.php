<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPreference extends Model
{
    protected $fillable = [
        'user_id',
        'industry_id',
        'experience_level',
        'desired_salary_from',
        'desired_salary_to',
        'currency',
        'work_mode',
        'notes',
        'cover_letter',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }
}
