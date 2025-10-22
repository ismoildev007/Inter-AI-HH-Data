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

    protected $casts = [
        'duration' => 'date',
        'fake_price' => 'decimal:2',
        'price' => 'decimal:2',
        'auto_response_limit' => 'integer',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
