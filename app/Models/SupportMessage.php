<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_chat_id',
        'message_text',
        'telegram_message_id',   // bot yuborgan / olgan xabar id
        'status',               // “pending”, “answered”
    ];
}
