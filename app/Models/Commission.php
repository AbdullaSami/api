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
        'withdrawn',
        'payout_batch_id',
        'withdrawn_at'
    ];

    protected $casts = [
        'withdrawn' => 'boolean',
        'withdrawn_at' => 'datetime',
    ];





    public function sponsor(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'sponsor_id', 'id');
    }


    public function referral(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'referral_id', 'id');
    }

    public function payoutBatch(): BelongsTo
    {
        return $this->belongsTo(CommissionPayoutBatch::class, 'payout_batch_id', 'id');
    }
}
