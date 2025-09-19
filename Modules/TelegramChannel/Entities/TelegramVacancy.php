<?php

namespace Modules\TelegramChannel\Entities;

use Illuminate\Database\Eloquent\Model;

class TelegramVacancy extends Model
{
    protected $table = 'telegram_vacancies';

    protected $fillable = [
        'description',

        'title',
        'company',
        'source',
        'source_message_id',
        'contact',
        'target_message_id',

        'company',

        'status',
        'title',
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
}
