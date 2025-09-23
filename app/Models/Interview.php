<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Interview extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'application_id',
        'scheduled_id',
        'status',
        'external_ref',
    ];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }

    public function preparations()
    {
        return $this->hasMany(InterviewPreparation::class);
    }
}
