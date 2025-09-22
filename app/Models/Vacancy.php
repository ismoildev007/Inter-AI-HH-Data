<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    public const STATUS_PUBLISH = 'publish';
    public const STATUS_ARCHIVE = 'archive';
    protected $fillable = [
        'source',
        'external_id',
        'employer_id',
        'title',
        'description',
        'area_id',
        'schedule_id',
        'employment_id',
        'salary_from',
        'salary_to',
        'salary_currency',
        'salary_gross',
        'published_at',
        'expires_at',
        'status',
        'apply_url',
        'views_count',
        'responses_count',
        'raw_data',
        'company',
        'contact',
        'language',
        'signature',
        'source_id',
        'source_message_id',
        'target_message_id',

    ];

    protected $casts = [
        'contact' => 'array',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
    public function area()
    {
        return $this->belongsTo(Area::class);
    }
    public function schedule()
    {
        return $this->belongsTo(HhSchedule::class);
    }
    public function employment()
    {
        return $this->belongsTo(HhEmployment::class);
    }
}
