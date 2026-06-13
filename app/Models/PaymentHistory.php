<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $fillable = [
        'user_id',
        'subscription_code',
        'payment_code',
        'payment_method',
        'amount',
        'payment_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
