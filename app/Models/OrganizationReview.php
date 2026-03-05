<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrganizationReview extends Model
{
    protected $fillable = [
        'application_id',
        'organization_id',
        'volunteer_user_id',
        'commitment',
        'task_clarity',
        'work_env',
        'time_respect',
        'comment',
    ];

    public function application() { return $this->belongsTo(\App\Models\Application::class); }
}