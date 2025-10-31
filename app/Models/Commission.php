<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model
{
    protected $fillable = [
        'sponsor_id',
        'commission_value',
        'commission_type',
        'referral_id',
        'withdrawn'
    ];





    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sponsor_id', 'id');
    }


    public function referral(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referral_id', 'id');
    }
}
