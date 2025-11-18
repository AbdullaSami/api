<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TokenWallet extends Model
{
    protected $fillable = [
        'member_id',
        'token_balance',
    ];


    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    public function transaction()
    {
        return $this->hasMany(TokenTransaction::class, 'token_wallet_id', 'id');
    }
}
