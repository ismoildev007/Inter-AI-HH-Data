<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSetting extends Model
{
    protected $fillable = [
        'user_id',
        'auto_apply_enabled',
        'auto_apply_limit',
        'notifications_enabled',
        'language',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
