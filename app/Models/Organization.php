<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
   protected $fillable = [
        'user_id',
        'org_name',
        'license_no',
        'address',
        'city',
        'description',
        'rating_avg',
        'rating_count',
    ];
}
