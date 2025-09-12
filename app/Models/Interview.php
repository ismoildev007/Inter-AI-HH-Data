<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;

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
}
