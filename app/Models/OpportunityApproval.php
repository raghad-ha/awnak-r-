<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpportunityApproval extends Model
{
    protected $fillable = [
        'opportunity_id',
        'status',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function opportunity()
    {
        return $this->belongsTo(\App\Models\Opportunity::class);
    }
}