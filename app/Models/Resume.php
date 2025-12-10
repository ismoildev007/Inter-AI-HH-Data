<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'description',
        'file_path',
        'file_mime',
        'file_size',
        'parsed_text',
        'is_primary',
        'first_name',
        'last_name',
        'contact_email',
        'phone',
        'city',
        'country',
        'gender',
        'birth_year',
        'profile_photo_path',
        'linkedin_url',
        'github_url',
        'portfolio_url',
        'desired_position',
        'desired_salary',
        'citizenship',
        'employment_types',
        'work_schedules',
        'ready_to_relocate',
        'ready_for_trips',
        'professional_summary',
        'languages',
        'certificates',
        'translations',
    ];

    protected $casts = [
        'is_primary' => 'bool',
        'birth_year' => 'integer',
        'employment_types' => 'array',
        'work_schedules' => 'array',
        'ready_to_relocate' => 'bool',
        'ready_for_trips' => 'bool',
        'languages' => 'array',
        'certificates' => 'array',
        'translations' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function analysis()
    {
        return $this->hasOne(ResumeAnalyze::class);
    }

    public function matchResults()
    {
        return $this->hasMany(MatchResult::class);
    }

    public function experiences()
    {
        return $this->hasMany(ResumeExperience::class);
    }

    public function educations()
    {
        return $this->hasMany(ResumeEducation::class);
    }

    public function skills()
    {
        return $this->hasMany(ResumeSkill::class);
    }

    public function careerTrackingPdf()
    {
        return $this->hasOne(CareerTrackingPdf::class);
    }
}
