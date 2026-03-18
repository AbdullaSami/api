<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'pack_card',
        'billing_period',
        'cv',
        'features'
    ];

    protected $casts = [
        'features' => 'array',
    ];

    public function cvCommissions()
    {
        return $this->hasMany(CvCommission::class);
    }
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
