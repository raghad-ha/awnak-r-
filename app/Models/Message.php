<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_user_id',
        'body',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(\App\Models\Conversation::class);
    }

    public function sender()
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_user_id');
    }
}