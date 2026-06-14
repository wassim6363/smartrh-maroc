<?php

namespace App\Filament\Widgets;

use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\Subscription;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommercialStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = -6;

    protected function getStats(): array
    {
        $totalDemoRequests = DemoRequest::query()->count();
        $newDemoRequests = DemoRequest::query()->where('status', 'new')->count();
        $converted = DemoRequest::query()->where('status', 'converted')->count();
        $conversionRate = $totalDemoRequests > 0 ? round(($converted / $totalDemoRequests) * 100, 1) : 0;
        $activeTrials = Subscription::query()->where('status', 'trialing')->count();
        $activeSubscriptions = Subscription::query()->whereIn('status', ['active', 'past_due'])->count();

        $mrr = Subscription::query()
            ->whereIn('status', ['active', 'past_due', 'trialing'])
            ->with('plan')
            ->get()
            ->sum(fn ($s) => (float) ($s->plan?->monthly_price ?? 0));

        $plans = Plan::query()->where('is_active', true)->get()->map(function ($plan) {
            $count = Subscription::query()->where('plan_id', $plan->id)->whereIn('status', ['active', 'past_due', 'trialing'])->count();

            return $plan->name . ': ' . $count;
        })->implode(' | ');

        return [
            Stat::make('Nouvelles demandes', $newDemoRequests)
                ->description('Demandes de démo en attente')
                ->icon(Heroicon::OutlinedMegaphone)
                ->color('warning'),
            Stat::make('Taux de conversion', $conversionRate . '%')
                ->description($converted . ' converties sur ' . $totalDemoRequests . ' demandes')
                ->icon(Heroicon::OutlinedArrowTrendingUp)
                ->color('success'),
            Stat::make('Essais actifs', $activeTrials)
                ->description('Abonnements en période d\'essai')
                ->icon(Heroicon::OutlinedClock)
                ->color('info'),
            Stat::make('Abonnements actifs', $activeSubscriptions)
                ->description('Clients payants actifs')
                ->icon(Heroicon::OutlinedCreditCard)
                ->color('success'),
            Stat::make('MRR estimé', number_format($mrr, 0, ',', ' ') . ' MAD')
                ->description('Revenu mensuel récurrent estimé')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('primary'),
            Stat::make('Sociétés par pack', $plans)
                ->description('Répartition des plans actifs')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('gray'),
        ];
    }
}