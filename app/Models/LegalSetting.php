<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegalSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'year' => 'integer',
        'cnss_ceiling' => 'decimal:2',
        'cnss_employee_rate' => 'decimal:4',
        'cnss_short_term_employee_rate' => 'decimal:4',
        'cnss_long_term_employee_rate' => 'decimal:4',
        'amo_employee_rate' => 'decimal:4',
        'professional_expenses_rate' => 'decimal:4',
        'professional_expenses_ceiling' => 'decimal:2',
        'professional_expenses_base' => 'string',
        'professional_expense_rate' => 'decimal:4',
        'professional_expense_ceiling' => 'decimal:2',
        'family_deduction_amount' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'active' => 'boolean',
        'is_active' => 'boolean',
    ];
}
