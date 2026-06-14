<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollItemRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'subject_to_cnss' => 'boolean',
        'subject_to_amo' => 'boolean',
        'subject_to_ir' => 'boolean',
        'is_tax_exempt' => 'boolean',
        'is_non_taxable_allowance' => 'boolean',
        'affects_gross' => 'boolean',
        'affects_net' => 'boolean',
    ];

    public function payrollItem(): BelongsTo
    {
        return $this->belongsTo(PayrollItem::class);
    }
}
