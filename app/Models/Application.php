<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'opportunity_id',
        'volunteer_user_id',
        'status',
        'decided_by',
        'decided_at',
        'message',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function opportunity()
    {
        return $this->belongsTo(\App\Models\Opportunity::class);
    }

    public function volunteer()
    {
        return $this->belongsTo(\App\Models\User::class, 'volunteer_user_id');
    }

    public function conversation()
    {
        return $this->hasOne(\App\Models\Conversation::class);
    }
}