<?php

namespace App\Services\Saas;

use App\Models\Company;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use Illuminate\Validation\ValidationException;

class SubscriptionLimitService
{
    public function canAddEmployee(Company $company): bool
    {
        $subscription = $this->activeSubscription($company);
        if (! $subscription) {
            return ! Plan::query()->exists();
        }

        $limit = $this->limit($subscription, 'max_employees', 'employee_limit');

        return $limit === null || $company->employees()->count() < $limit;
    }

    public function canGeneratePayslip(Company $company): bool
    {
        $subscription = $this->activeSubscription($company);
        if (! $subscription) {
            return ! Plan::query()->exists();
        }

        $limit = $this->limit($subscription, 'max_payslips_per_month');

        return $limit === null || $this->usage($company, $subscription)->payslips_generated < $limit;
    }

    public function canGenerateContract(Company $company): bool
    {
        $subscription = $this->activeSubscription($company);
        if (! $subscription) {
            return ! Plan::query()->exists();
        }

        $limit = $this->limit($subscription, 'max_contracts_per_month');

        return $limit === null || $this->usage($company, $subscription)->contracts_generated < $limit;
    }

    public function canUseEmployeePortal(Company $company): bool
    {
        $subscription = $this->activeSubscription($company);

        return $subscription ? (bool) $subscription->plan?->employee_portal_enabled : ! Plan::query()->exists();
    }

    public function canUseDocumentRequests(Company $company): bool
    {
        $subscription = $this->activeSubscription($company);

        return $subscription ? (bool) $subscription->plan?->document_requests_enabled : ! Plan::query()->exists();
    }

    public function incrementPayslipUsage(Company $company): void
    {
        if ($subscription = $this->activeSubscription($company)) {
            $this->usage($company, $subscription)->increment('payslips_generated');
        }
    }

    public function incrementContractUsage(Company $company): void
    {
        if ($subscription = $this->activeSubscription($company)) {
            $this->usage($company, $subscription)->increment('contracts_generated');
        }
    }

    public function incrementDocumentUsage(Company $company): void
    {
        if ($subscription = $this->activeSubscription($company)) {
            $this->usage($company, $subscription)->increment('documents_generated');
        }
    }

    public function getCurrentUsage(Company $company): array
    {
        $subscription = $this->activeSubscription($company);
        $usage = $subscription ? $this->usage($company, $subscription) : null;

        return [
            'employees_count' => $company->employees()->count(),
            'payslips_generated' => $usage?->payslips_generated ?? 0,
            'contracts_generated' => $usage?->contracts_generated ?? 0,
            'documents_generated' => $usage?->documents_generated ?? 0,
        ];
    }

    public function getLimitsSummary(Company $company): array
    {
        $subscription = $this->activeSubscription($company);
        $plan = $subscription?->plan;
        $usage = $this->getCurrentUsage($company);

        return [
            'plan' => $plan?->name ?? 'Aucun plan',
            'status' => $subscription?->status ?? 'missing',
            'employees' => ['used' => $usage['employees_count'], 'limit' => $plan?->max_employees ?? $plan?->employee_limit],
            'payslips' => ['used' => $usage['payslips_generated'], 'limit' => $plan?->max_payslips_per_month],
            'contracts' => ['used' => $usage['contracts_generated'], 'limit' => $plan?->max_contracts_per_month],
            'features' => [
                'employee_portal_enabled' => (bool) ($plan?->employee_portal_enabled ?? false),
                'document_requests_enabled' => (bool) ($plan?->document_requests_enabled ?? false),
                'audit_logs_enabled' => (bool) ($plan?->audit_logs_enabled ?? false),
                'api_access_enabled' => (bool) ($plan?->api_access_enabled ?? false),
            ],
        ];
    }

    public function assertCanAddEmployee(Company $company): void
    {
        $this->assert($this->canAddEmployee($company), 'Limite salaries atteinte ou abonnement actif requis.');
    }

    public function assertCanGeneratePayslip(Company $company): void
    {
        $this->assert($this->canGeneratePayslip($company), 'Limite mensuelle de bulletins atteinte ou abonnement actif requis.');
    }

    public function assertCanGenerateContract(Company $company): void
    {
        $this->assert($this->canGenerateContract($company), 'Limite mensuelle de contrats atteinte ou abonnement actif requis.');
    }

    private function assert(bool $allowed, string $message): void
    {
        if (! $allowed) {
            throw ValidationException::withMessages(['subscription' => $message]);
        }
    }

    private function activeSubscription(Company $company): ?Subscription
    {
        return $company->subscriptions()
            ->with('plan')
            ->whereIn('status', ['trialing', 'active'])
            ->where(function ($query): void {
                $query->whereNull('starts_at')->orWhereDate('starts_at', '<=', now()->toDateString());
            })
            ->where(function ($query): void {
                $query->whereNull('ends_at')->orWhereDate('ends_at', '>=', now()->toDateString());
            })
            ->latest('starts_at')
            ->first();
    }

    private function usage(Company $company, Subscription $subscription): SubscriptionUsage
    {
        return SubscriptionUsage::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'subscription_id' => $subscription->id,
                'period_year' => (int) now()->year,
                'period_month' => (int) now()->month,
            ],
            ['employees_count' => $company->employees()->count()],
        );
    }

    private function limit(Subscription $subscription, string $column, ?string $legacyColumn = null): ?int
    {
        $limit = $subscription->plan?->{$column} ?? ($legacyColumn ? $subscription->plan?->{$legacyColumn} : null);

        if ($limit === null || (int) $limit <= 0) {
            return null;
        }

        return (int) $limit;
    }
}
