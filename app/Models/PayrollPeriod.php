<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'starts_at' => 'date',
        'ends_at' => 'date',
        'payment_date' => 'date',
    ];

    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }
}
