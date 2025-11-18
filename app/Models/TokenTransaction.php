<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TokenTransaction extends Model
{

    use SoftDeletes;
    protected $fillable = [
        'token_wallet_id',
        'transaction_type',
        'amount',
        'status',
        'sender_member_id',
        'receive_member_id'
    ];

        public function sender()
    {
        return $this->belongsTo(Member::class, 'sender_member_id', 'id');
    }
    public function receiver()
    {
        return $this->belongsTo(Member::class, 'receive_member_id', 'id');
    }
    public function tokenWallet()
    {
        return $this->belongsTo(TokenWallet::class, 'token_wallet_id', 'id');
    }
}
