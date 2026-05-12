<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MessageAttachment extends Model
{
    protected $fillable = [
        'message_id',
        'file',
        'original_name',
        'mime_type',
        'size',
    ];

    public function message()
    {
        return $this->belongsTo(MemberMessage::class, 'message_id');
    }
}
