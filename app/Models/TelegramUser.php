<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'chat_id',
        'first_name',
        'username',
        'language_code',
        'last_name',
        'is_premium',
        'auth_date',
        'data_json',
    ];

    public function user()
    {
       return $this->belongsTo(User::class);
    }
}
