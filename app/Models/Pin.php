<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pin extends Model
{
    protected $fillable = ['pinable_type', 'pinable_id', 'pin_hash', 'failed_attempts', 'locked_until'];

    public function pinable()
    {
        return $this->morphTo();
    }
}
