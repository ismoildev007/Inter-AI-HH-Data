<?php

namespace Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'filters',
        'custom_requirements',
        'status',
    ];

    protected $casts = [
        'filters' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function results()
    {
        return $this->hasMany(SearchResult::class);
    }
}
