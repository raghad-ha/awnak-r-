<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinRequestFile extends Model
{
    protected $fillable = [
        'join_request_id',
        'file_path',
        'file_type',
    ];

    public function joinRequest()
    {
        return $this->belongsTo(\App\Models\JoinRequest::class);
    }
}