<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinRequestFile extends Model
{
    public function joinRequest() {
    return $this->belongsTo(\App\Models\JoinRequest::class);
}
}
