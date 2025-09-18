<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'vacancy_id',
        'resume_id',
        'hh_resume_id',
        'status',
        'match_score',
        'submitted_at',
        'external_id',
        'notes',
        'hh_status',
    ];

    protected $casts = [
        'match_score' => 'decimal:2',
        'submitted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vacancy()
    {
        return $this->belongsTo(Vacancy::class);
    }

    public function resume()
    {
        return $this->belongsTo(Resume::class);
    }

    public function creditTransactions()
    {
        return $this->hasMany(CreditTransaction::class, 'related_application_id');
    }
}
