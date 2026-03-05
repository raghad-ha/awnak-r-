<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = ['author_user_id','body','visibility'];

    public function media() { return $this->hasMany(\App\Models\PostMedia::class); }
    public function comments() { return $this->hasMany(\App\Models\Comment::class); }
    public function likes() { return $this->hasMany(\App\Models\PostLike::class); }
    public function tags() { return $this->belongsToMany(\App\Models\Organization::class, 'post_tags'); }
}