<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotSession extends Model
{
    protected $fillable = [
        'user_id',
        'guest_token',
        'title',
    ];

    public function messages()
    {
        return $this->hasMany(ChatbotMessage::class);
    }
}