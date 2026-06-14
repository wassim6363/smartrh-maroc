<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeniorityBonusRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'min_years' => 'integer',
        'max_years' => 'integer',
        'rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];
}
