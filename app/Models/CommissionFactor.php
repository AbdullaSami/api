<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionFactor extends Model
{
    protected $fillable = [
        'direct_rate',
        'binary_rate',
        'package_id',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
