<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HhSchedule extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'raw',
        'json',
    ];

    protected $casts = [
        'json' => 'array',
    ];
}
