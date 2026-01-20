<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Referal extends Model
{
    protected $fillable = [
        'sponsor_id',
        'commission_type',
        'leg',
        'referral_id',
    ];

    // The sponsor member
    public function sponsorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sponsor_id', 'id');
    }

    // The referred member
    public function referredMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referral_id', 'id');
    }
}
