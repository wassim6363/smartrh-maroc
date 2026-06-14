<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayrollItem extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function rule(): HasOne
    {
        return $this->hasOne(PayrollItemRule::class);
    }

    public function payslipLines(): HasMany
    {
        return $this->hasMany(PayslipLine::class);
    }
}
