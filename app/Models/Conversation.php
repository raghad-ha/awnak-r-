<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = [
        'application_id',
        'organization_id',
        'volunteer_user_id',
    ];

    public function application()
    {
        return $this->belongsTo(\App\Models\Application::class);
    }

    public function messages()
    {
        return $this->hasMany(\App\Models\Message::class);
    }
}