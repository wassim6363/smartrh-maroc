<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Absence extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'hours' => 'decimal:2',
        'duration_days' => 'decimal:2',
        'justified' => 'boolean',
        'payroll_impact' => 'boolean',
        'deduction_amount' => 'decimal:2',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
