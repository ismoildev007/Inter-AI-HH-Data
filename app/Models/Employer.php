<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'source',
        'external_id',
        'name',
        'url',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];
}
