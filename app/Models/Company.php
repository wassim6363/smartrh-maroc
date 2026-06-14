<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Company extends Model
{
    protected $guarded = [];

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function payrollPeriods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }

    public function payrollSettings(): HasMany
    {
        return $this->hasMany(PayrollSetting::class);
    }

    public function payrollItems(): HasMany
    {
        return $this->hasMany(PayrollItem::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)
            ->whereIn('status', ['trialing', 'active'])
            ->where(function ($query) {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            })
            ->latestOfMany('starts_at');
    }

    public function subscriptionUsage(): HasMany
    {
        return $this->hasMany(SubscriptionUsage::class);
    }

    public function getCurrentPlanAttribute(): ?Plan
    {
        return $this->activeSubscription?->plan;
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function contractTemplates(): HasMany
    {
        return $this->hasMany(ContractTemplate::class);
    }

    public function employeeContracts(): HasMany
    {
        return $this->hasMany(EmployeeContract::class);
    }

    public function generatedDocuments(): HasMany
    {
        return $this->hasMany(GeneratedDocument::class);
    }

    public function employeeDocumentRequests(): HasMany
    {
        return $this->hasMany(EmployeeDocumentRequest::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }
}
