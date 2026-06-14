<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'starts_at' => 'date',
        'trial_ends_at' => 'date',
        'ends_at' => 'date',
        'current_period_start' => 'date',
        'current_period_end' => 'date',
        'amount' => 'decimal:2',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }
}
