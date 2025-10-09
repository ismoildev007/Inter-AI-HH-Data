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
        'description',
        'file_path',
        'file_mime',
        'file_size',
        'parsed_text',
        'is_primary',
    ];
}
