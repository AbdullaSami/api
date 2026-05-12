<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\MemberMessageRecipient;

class MemberMessage extends Model
{
    protected $fillable = [
        'sender_id',
        'subject',
        'body',
        'parent_message_id',
        'is_system_message',
        'priority',
        'expires_at',
        'delivery_type',
        'tree_side',
        'include_sender',
    ];

    protected $casts = [
        'is_system_message' => 'boolean',
        'expires_at' => 'datetime',
        'include_sender' => 'boolean',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipients()
    {
        return $this->belongsToMany(MemberMessageRecipient::class, 'message_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }

    public function parent()
    {
        return $this->belongsTo(MemberMessage::class, 'parent_message_id');
    }

    public function replies()
    {
        return $this->hasMany(MemberMessage::class, 'parent_message_id');
    }
}
