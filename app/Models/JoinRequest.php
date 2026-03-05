<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinRequest extends Model
{
    protected $fillable = [
        'requester_user_id',
        'requester_type',
        'status',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function files()
    {
        return $this->hasMany(\App\Models\JoinRequestFile::class);
    }
}