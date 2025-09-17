<?php

namespace Modules\TelegramChannel\Entities;

use Illuminate\Database\Eloquent\Model;

class TelegramVacancy extends Model
{
    protected $table = 'telegram_vacancies';

    protected $fillable = [
        'description',
        'status',
    ];
}
