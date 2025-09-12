<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;

class HhAccount extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'scope',
        'raw_json',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'raw_json' => 'array',
    ];

    /**
     * The user that owns this HH account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
