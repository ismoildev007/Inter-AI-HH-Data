<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProfileView extends Model
{
    protected $fillable = [
        'user_id',
        'employer_id',
        'viewed_at',
        'source',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}
