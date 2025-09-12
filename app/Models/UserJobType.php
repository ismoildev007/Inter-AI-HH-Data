<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserJobType extends Model
{
    protected $fillable = [
        'user_id',
        'job_type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
