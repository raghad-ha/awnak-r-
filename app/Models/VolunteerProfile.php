<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VolunteerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'birthdate',
        'gender',
        'city',
        'bio',
        'availability',
    ];
}
