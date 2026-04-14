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
        'features',
        'is_published',
    ];

    protected $casts = [
        'features' => 'array',
        'level' => 'integer',
        'is_published' => 'boolean',
    ];

    public function commissionFactors()
    {
        return $this->hasMany(CommissionFactor::class);
    }
    
    public function cvCommissions()
    {
        return $this->hasMany(CvCommission::class);
    }
    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }
}
