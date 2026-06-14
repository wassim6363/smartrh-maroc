<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeePayrollItem extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'decimal:2',
        'taxable' => 'boolean',
        'subject_to_cnss' => 'boolean',
        'subject_to_amo' => 'boolean',
        'subject_to_ir' => 'boolean',
        'is_tax_exempt' => 'boolean',
        'recurring' => 'boolean',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'active' => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActiveForPeriod(Builder $query, PayrollPeriod $period): Builder
    {
        return $query
            ->where('active', true)
            ->where(function (Builder $query) use ($period) {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', $period->ends_at);
            })
            ->where(function (Builder $query) use ($period) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', $period->starts_at);
            });
    }
}
