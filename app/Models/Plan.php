<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $guarded = [];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'max_companies' => 'integer',
        'max_employees' => 'integer',
        'max_payslips_per_month' => 'integer',
        'max_contracts_per_month' => 'integer',
        'employee_portal_enabled' => 'boolean',
        'document_requests_enabled' => 'boolean',
        'audit_logs_enabled' => 'boolean',
        'api_access_enabled' => 'boolean',
        'features' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
