<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluationAction extends Model
{
    protected $fillable = [
        'volunteer_user_id',
        'action',
        'reason',
        'decided_by',
    ];
}