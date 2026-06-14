<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractTemplate extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function employeeContracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }
}
