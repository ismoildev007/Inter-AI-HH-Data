<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CareerTrackingPdf extends Model
{
    use HasFactory;

    protected $table = 'career_tracking_pdfs';

    protected $fillable = [
        'resume_id',
        'json',
        'pdf'
    ];

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }
}
