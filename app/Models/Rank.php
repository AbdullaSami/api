<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rank extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'package',
        'left_volume',
        'right_volume',
        'direct_referrals',
        'downline_requirements',
        'image',
    ];

    /**
     * Cast downline requirements to an array.
     */
    protected $casts = [
        'downline_requirements' => 'array',
    ];


    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }
}
