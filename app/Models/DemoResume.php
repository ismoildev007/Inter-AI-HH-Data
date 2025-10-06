<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemoResume extends Model
{
    use HasFactory;

    protected $table = 'demo_resumes';

    protected $fillable = [
        'chat_id',
        'title',
        'file',
    ];
}
