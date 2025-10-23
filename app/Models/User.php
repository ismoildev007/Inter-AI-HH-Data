<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    public const STATUS_WORKING = 'working';
    public const STATUS_NOT_WORKING = 'not_working';

    public const STATUSES = [
        self::STATUS_WORKING,
        self::STATUS_NOT_WORKING,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'birth_date',
        'avatar_path',
        'verify_code',
        'role_id',
        'chat_id',
        'language',
        'is_trial_active',
        'trial_start_date',
        'trial_end_date',
        'status',
        'admin_check_status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_trial_active' => 'boolean',
            'trial_start_date' => 'datetime',
            'trial_end_date' => 'datetime',
            'admin_check_status' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function preferences()
    {
        return $this->hasMany(UserPreference::class);
    }
    public function preference()
    {
        return $this->hasOne(UserPreference::class);
    }

    public function locations()
    {
        return $this->hasMany(UserLocation::class);
    }
    public function jobTypes()
    {
        return $this->hasMany(UserJobType::class);
    }
    public function credit()
    {
        return $this->hasOne(UserCredit::class);
    }
    public function profileViews()
    {
        return $this->hasMany(UserProfileView::class);
    }
    public function settings()
    {
        return $this->hasOne(UserSetting::class);
    }
    public function resumes(): HasMany
    {
        return $this->hasMany(Resume::class);
    }

    public function hhAccount()
    {
        return $this->hasOne(HhAccount::class);
    }

    public function matchResults()
    {
        return $this->hasManyThrough(MatchResult::class, Resume::class);
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
