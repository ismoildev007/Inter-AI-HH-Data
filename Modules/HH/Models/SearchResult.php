<?php

namespace Modules\HH\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SearchResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'search_request_id',
        'candidate_id',
        'match_percentage',
    ];

    public function searchRequest()
    {
        return $this->belongsTo(SearchRequest::class);
    }

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
