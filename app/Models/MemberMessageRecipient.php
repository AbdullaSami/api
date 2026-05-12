<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemberMessageRecipient extends Model
{
    protected $fillable = [
        'message_id',
        'recipient_id',
        'is_read',
        'read_at',
        'deleted_by_recipient',
        'deleted_by_sender',
        'recipient_archived',
        'sender_archived',
        'starred',
        'important',
        'muted',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'deleted_by_recipient' => 'boolean',
        'deleted_by_sender' => 'boolean',
        'recipient_archived' => 'boolean',
        'sender_archived' => 'boolean',
        'starred' => 'boolean',
        'important' => 'boolean',
        'muted' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(MemberMessage::class, 'message_id');
    }

    public function recipient()
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
