<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Opportunity extends Model
{
    protected $fillable = [
        'organization_id',
        'created_by',
        'title',
        'description',
        'city',
        'location_text',
        'start_date',
        'end_date',
        'capacity',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }

    public function approvals()
    {
        return $this->hasMany(\App\Models\OpportunityApproval::class);
    }
}