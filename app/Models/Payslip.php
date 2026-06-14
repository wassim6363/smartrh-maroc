<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payslip extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'gross_total' => 'decimal:2',
        'taxable_gross' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'base_salary' => 'decimal:2',
        'total_taxable_primes' => 'decimal:2',
        'total_taxable_indemnities' => 'decimal:2',
        'total_non_taxable_indemnities' => 'decimal:2',
        'total_overtime' => 'decimal:2',
        'total_absences' => 'decimal:2',
        'cnss_base' => 'decimal:2',
        'cnss_employee' => 'decimal:2',
        'amo_base' => 'decimal:2',
        'amo_employee' => 'decimal:2',
        'salary_after_contributions' => 'decimal:2',
        'taxable_before_professional_expenses' => 'decimal:2',
        'professional_expenses' => 'decimal:2',
        'taxable_net_income' => 'decimal:2',
        'ir_brut' => 'decimal:2',
        'taxable_income' => 'decimal:2',
        'ir_gross' => 'decimal:2',
        'ir_net' => 'decimal:2',
        'exempt_allowances' => 'decimal:2',
        'net_deductions' => 'decimal:2',
        'total_advances' => 'decimal:2',
        'total_other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_to_pay' => 'decimal:2',
        'net_pay' => 'decimal:2',
        'ytd_gross_salary' => 'decimal:2',
        'ytd_taxable_income' => 'decimal:2',
        'ytd_ir' => 'decimal:2',
        'ytd_cnss' => 'decimal:2',
        'ytd_amo' => 'decimal:2',
        'ytd_net_pay' => 'decimal:2',
        'ytd_total_deductions' => 'decimal:2',
        'taxable_salary' => 'decimal:2',
        'total_employee_deductions' => 'decimal:2',
        'total_employer_contributions' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'generated_at' => 'datetime',
        'validated_at' => 'datetime',
        'sent_at' => 'datetime',
        'closed_at' => 'datetime',
        'calculation_snapshot' => 'array',
    ];

    public function payrollPeriod(): BelongsTo
    {
        return $this->belongsTo(PayrollPeriod::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(PayslipLine::class)->orderBy('sort_order');
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
