<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
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
        'parent_id',
        'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
    ];

    /**
     * Parent area in the hierarchy.
     */
    public function parent()
    {
        return $this->belongsTo(Area::class, 'parent_id');
    }

    /**
     * Child areas in the hierarchy.
     */
    public function children()
    {
        return $this->hasMany(Area::class, 'parent_id');
    }
}
