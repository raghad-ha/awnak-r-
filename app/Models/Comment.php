<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $fillable = ['post_id','user_id','parent_id','body'];

    public function post() { return $this->belongsTo(\App\Models\Post::class); }
    public function parent() { return $this->belongsTo(\App\Models\Comment::class, 'parent_id'); }
    public function replies() { return $this->hasMany(\App\Models\Comment::class, 'parent_id'); }
}