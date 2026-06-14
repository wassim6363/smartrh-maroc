<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class CnssRate extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'employee_rate' => 'decimal:4',
        'employer_rate' => 'decimal:4',
        'salary_ceiling' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
