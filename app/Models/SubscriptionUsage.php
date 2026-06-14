<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionUsage extends Model
{
    use BelongsToCompany;

    protected $table = 'subscription_usage';

    protected $guarded = [];

    protected $casts = [
        'period_year' => 'integer',
        'period_month' => 'integer',
        'employees_count' => 'integer',
        'payslips_generated' => 'integer',
        'contracts_generated' => 'integer',
        'documents_generated' => 'integer',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }
}
