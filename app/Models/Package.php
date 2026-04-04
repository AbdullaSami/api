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
        'level',
        'pack_card',
        'pack_icon',
        'billing_period',
        'cv',
        'features'
    ];

    protected $casts = [
        'features' => 'array',
        'level' => 'integer',
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
