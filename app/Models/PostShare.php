<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostShare extends Model
{
    protected $table = 'post_shares';
    protected $fillable = ['original_post_id','shared_by_user_id','comment'];

    public function originalPost() { return $this->belongsTo(\App\Models\Post::class, 'original_post_id'); }
}