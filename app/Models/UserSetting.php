<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'auto_apply_enabled',
        'auto_apply_limit',
        'auto_apply_count',
        'notifications_enabled',
        'resume_id',
        'language',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
