<?php

namespace App\Services\Saas;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionManagementService
{
    public function startTrial(Company $company, Plan $plan): Subscription
    {
        return DB::transaction(function () use ($company, $plan): Subscription {
            $company->subscriptions()
                ->whereIn('status', ['trialing', 'active', 'past_due'])
                ->update(['status' => 'cancelled', 'ends_at' => now()->toDateString()]);

            $subscription = Subscription::query()->create([
                'company_id' => $company->id,
                'plan_id' => $plan->id,
                'status' => 'trialing',
                'starts_at' => now()->toDateString(),
                'trial_ends_at' => now()->addDays(14)->toDateString(),
                'ends_at' => now()->addDays(14)->toDateString(),
                'billing_cycle' => 'monthly',
                'current_period_start' => now()->toDateString(),
                'current_period_end' => now()->addDays(14)->toDateString(),
                'amount' => 0,
            ]);

            $this->audit('subscription_trial_started', $subscription, ['plan' => $plan->name]);

            return $subscription->refresh()->load('plan');
        });
    }

    public function changePlan(Company $company, Plan $newPlan): Subscription
    {
        $this->assertCanUsePlan($company, $newPlan);

        return DB::transaction(function () use ($company, $newPlan): Subscription {
            $subscription = $this->activeSubscription($company);
            if (! $subscription) {
                return $this->startTrial($company, $newPlan);
            }

            $oldPlan = $subscription->plan?->name;
            $subscription->forceFill([
                'plan_id' => $newPlan->id,
                'amount' => $newPlan->monthly_price,
                'billing_cycle' => $subscription->billing_cycle ?: 'monthly',
            ])->save();

            $this->audit('subscription_plan_changed', $subscription, [
                'old_plan' => $oldPlan,
                'new_plan' => $newPlan->name,
            ]);

            return $subscription->refresh()->load('plan');
        });
    }

    public function cancelSubscription(Company $company): void
    {
        if (! $subscription = $this->activeSubscription($company)) {
            return;
        }

        $subscription->forceFill([
            'status' => 'cancelled',
            'ends_at' => now()->toDateString(),
        ])->save();

        $this->audit('subscription_cancelled', $subscription);
    }

    public function renewSubscription(Company $company): Subscription
    {
        $subscription = $this->activeSubscription($company) ?: $company->subscriptions()->with('plan')->latest('starts_at')->firstOrFail();
        $start = now()->startOfMonth();
        $end = $subscription->billing_cycle === 'yearly'
            ? $start->copy()->addYear()->subDay()
            : $start->copy()->endOfMonth();

        $subscription->forceFill([
            'status' => 'active',
            'starts_at' => $subscription->starts_at ?: now()->toDateString(),
            'ends_at' => $end->toDateString(),
            'current_period_start' => $start->toDateString(),
            'current_period_end' => $end->toDateString(),
            'amount' => $subscription->billing_cycle === 'yearly'
                ? ($subscription->plan?->yearly_price ?? $subscription->plan?->monthly_price * 12)
                : $subscription->plan?->monthly_price,
        ])->save();

        $this->audit('subscription_renewed', $subscription);

        return $subscription->refresh()->load('plan');
    }

    public function generateInvoice(Subscription $subscription): Invoice
    {
        $subscription->loadMissing(['company', 'plan']);
        $amount = $subscription->billing_cycle === 'yearly'
            ? ($subscription->plan?->yearly_price ?? ($subscription->plan?->monthly_price ?? 0) * 12)
            : ($subscription->plan?->monthly_price ?? $subscription->amount);

        $invoice = Invoice::query()->create([
            'company_id' => $subscription->company_id,
            'subscription_id' => $subscription->id,
            'invoice_number' => $this->invoiceNumber($subscription->company),
            'amount' => $amount,
            'currency' => 'MAD',
            'status' => 'pending',
            'billing_period_start' => $subscription->current_period_start ?? now()->startOfMonth()->toDateString(),
            'billing_period_end' => $subscription->current_period_end ?? now()->endOfMonth()->toDateString(),
            'issued_at' => now()->toDateString(),
            'due_at' => now()->addDays(10)->toDateString(),
        ]);

        app(InvoicePdfService::class)->generate($invoice);
        $this->audit('invoice_generated', $invoice, ['invoice_number' => $invoice->invoice_number]);

        return $invoice->refresh()->load(['company', 'subscription.plan']);
    }

    private function assertCanUsePlan(Company $company, Plan $plan): void
    {
        $summary = app(SubscriptionLimitService::class)->getLimitsSummary($company);

        $limits = [
            'employees' => $plan->max_employees,
            'payslips' => $plan->max_payslips_per_month,
            'contracts' => $plan->max_contracts_per_month,
        ];

        foreach ($limits as $key => $limit) {
            if ($limit && $summary[$key]['used'] > $limit) {
                throw ValidationException::withMessages([
                    'plan' => 'Impossible de passer a ce pack: utilisation actuelle superieure aux limites.',
                ]);
            }
        }
    }

    private function activeSubscription(Company $company): ?Subscription
    {
        return $company->subscriptions()
            ->with('plan')
            ->whereIn('status', ['trialing', 'active', 'past_due'])
            ->latest('starts_at')
            ->first();
    }

    private function invoiceNumber(Company $company): string
    {
        return sprintf('INV-%s-%03d', now()->format('Ym'), $company->invoices()->count() + 1);
    }

    private function audit(string $event, Subscription|Invoice $model, array $metadata = []): void
    {
        app(AuditLogger::class)->log($event, $model, [], [], $metadata);
    }
}
