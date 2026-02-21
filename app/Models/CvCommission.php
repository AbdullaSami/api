<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvCommission extends Model
{
    protected $fillable = [
        'member_id',
        'package_id',
        'amount',
        'side',
    ];

    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
