<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JoinRequest extends Model
{
    public function files() {
    return $this->hasMany(\App\Models\JoinRequestFile::class);
}
}
