<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

 class PostTag extends Model {
  protected $fillable = ['post_id','taggable_type','taggable_id','tagged_by_user_id'];
  public function taggable(){ return $this->morphTo(); }

  public function post()
{
    return $this->belongsTo(\App\Models\Post::class);
}
}

