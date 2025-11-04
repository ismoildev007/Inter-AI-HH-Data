<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vacancy extends Model
{
    public const STATUS_PUBLISH = 'publish';
    public const STATUS_ARCHIVE = 'archive';
    public const STATUS_QUEUED  = 'queued';
    public const STATUS_FAILED  = 'failed';
    public const STATUS_SKIPPED = 'skipped';
    // public const STATUS_QUEUED  = 'queued';
    // public const STATUS_FAILED  = 'failed';
    protected $fillable = [
        'source',
        'external_id',
        'employer_id',
        'title',
        'description',
        'category',
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
        'target_msg_id',
        'raw_hash',
        'normalized_hash',

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
