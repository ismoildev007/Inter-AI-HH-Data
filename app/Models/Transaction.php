<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'subscription_id',
        'payment_status',
        'transaction_id',
        'payment_method',
        'state',
        'amount',
        'create_time',
        'perform_time',
        'cancel_time',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'create_time' => 'datetime',
        'perform_time' => 'datetime',
        'cancel_time' => 'datetime',
    ];

    public static function getTransactionsByTimeRange($from, $to)
    {
        return self::whereIn('state', [1, 2, -1, -2])
            ->whereBetween('create_time', [$from, $to])
            ->orderBy('create_time', 'asc')
            ->get();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
