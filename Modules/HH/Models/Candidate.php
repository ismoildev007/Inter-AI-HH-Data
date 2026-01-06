<?php

namespace Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Candidate extends Model
{
    use HasFactory;

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'age',
        'birth_date',
        'about',
        'salary_expectation',
        'experience', // in years
        'specialization',
        'skills', // json
        'contact_info', // json
    ];

    protected $casts = [
        'skills' => 'array',
        'contact_info' => 'array',
        'birth_date' => 'date',
    ];
}
