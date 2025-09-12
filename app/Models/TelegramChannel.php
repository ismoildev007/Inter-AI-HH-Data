<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramChannel extends Model
{
    protected $fillable = [
        'username',
        'channel_id',
        'title',
        'is_source',
        'is_target',
        'last_message_id',
        'raw_json',
    ];

    protected $casts = [
        'is_source' => 'bool',
        'is_target' => 'bool',
        'last_message_id' => 'integer',
        'raw_json' => 'array',
    ];
}

