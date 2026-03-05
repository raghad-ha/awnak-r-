<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryMedia extends Model
{
    protected $fillable = ['story_id','type','path','thumbnail_path'];

    public function story() { return $this->belongsTo(\App\Models\Story::class); }
}