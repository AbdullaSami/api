<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CommissionFactor extends Model
{
    protected $fillable = [
        'direct_rate',
        'binary_rate'
    ];
}
