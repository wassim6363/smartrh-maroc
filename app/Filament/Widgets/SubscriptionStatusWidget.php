<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Services\Saas\SubscriptionLimitService;
use Filament\Widgets\Widget;

class SubscriptionStatusWidget extends Widget
{
    protected string $view = 'filament.widgets.subscription-status-widget';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = -4;

    protected function getViewData(): array
    {
        $user = auth()->user();
        $company = $user?->isSuperAdmin() ? Company::query()->first() : $user?->company;
        $subscription = $company?->activeSubscription()->with('plan')->first();
        $summary = $company ? app(SubscriptionLimitService::class)->getLimitsSummary($company) : null;

        return [
            'company' => $company,
            'subscription' => $subscription,
            'summary' => $summary,
            'trialExpiringSoon' => $subscription?->status === 'trialing'
                && $subscription->trial_ends_at
                && $subscription->trial_ends_at->between(now()->startOfDay(), now()->addDays(3)->endOfDay()),
            'nearLimit' => $summary ? collect(['employees', 'payslips', 'contracts'])->contains(function (string $key) use ($summary): bool {
                $limit = $summary[$key]['limit'] ?? null;

                return $limit && $summary[$key]['used'] < $limit && ($summary[$key]['used'] / max($limit, 1)) >= 0.8;
            }) : false,
            'limitReached' => $summary ? collect(['employees', 'payslips', 'contracts'])->contains(function (string $key) use ($summary): bool {
                $limit = $summary[$key]['limit'] ?? null;

                return $limit && $summary[$key]['used'] >= $limit;
            }) : false,
        ];
    }
}
