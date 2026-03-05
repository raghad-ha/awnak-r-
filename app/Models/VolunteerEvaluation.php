<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerEvaluation extends Model
{
    protected $fillable = [
        'application_id',
        'organization_id',
        'volunteer_user_id',
        'score',
        'report',
        'created_by',
    ];

    public function application() { return $this->belongsTo(\App\Models\Application::class); }
}