<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HhEmployment extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'external_id',
        'name',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];
}
