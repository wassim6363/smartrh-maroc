<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class PayrollSetting extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'default_working_hours' => 'decimal:2',
        'minimum_wage' => 'decimal:2',
        'include_cnss' => 'boolean',
        'include_amo' => 'boolean',
        'include_ir' => 'boolean',
        'settings' => 'array',
    ];
}
