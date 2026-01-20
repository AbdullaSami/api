<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CommissionPayoutBatch extends Model
{
    protected $fillable = [
        'window_start',
        'window_end',
        'status',
        'total_commissions',
        'total_amount',
        'meta',
        'finished_at',
    ];

    protected $casts = [
        'window_start' => 'datetime',
        'window_end' => 'datetime',
        'finished_at' => 'datetime',
        'meta' => 'array',
    ];

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'payout_batch_id', 'id');
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'payout_batch_id', 'id');
    }
}
