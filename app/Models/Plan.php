<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'fake_price',
        'price',
        'auto_response_limit',
        'duration',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
