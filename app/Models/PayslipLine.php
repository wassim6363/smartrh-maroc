<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayslipLine extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'base' => 'decimal:2',
        'base_amount' => 'decimal:2',
        'rate' => 'decimal:4',
        'amount' => 'decimal:2',
        'subject_to_cnss' => 'boolean',
        'subject_to_amo' => 'boolean',
        'subject_to_ir' => 'boolean',
        'is_tax_exempt' => 'boolean',
    ];

    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
