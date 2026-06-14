<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class IrBracket extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'min_amount' => 'decimal:2',
        'max_amount' => 'decimal:2',
        'year' => 'integer',
        'rate' => 'decimal:4',
        'deduction' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
    ];
}
