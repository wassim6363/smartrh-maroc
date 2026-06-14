<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class ProfessionalExpenseRate extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:4',
        'monthly_ceiling' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];
}
