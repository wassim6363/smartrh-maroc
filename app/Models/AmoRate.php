<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class AmoRate extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
