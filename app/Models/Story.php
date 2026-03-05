<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    protected $fillable = ['author_user_id','caption','expires_at'];

    protected $casts = ['expires_at' => 'datetime'];

    public function media() { return $this->hasMany(\App\Models\StoryMedia::class); }
}