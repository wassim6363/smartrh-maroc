<?php

namespace App\Filament\Widgets;

use App\Models\Absence;
use App\Models\Contract;
use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\LeaveRequest;
use App\Models\Payslip;
use App\Models\Subscription;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SmartRhStatsOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Bienvenue sur SmartRH Maroc';

    protected ?string $description = 'Pilotez vos RH et votre paie depuis un seul espace moderne.';

    protected static ?int $sort = -8;

    protected function getStats(): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $monthlyPayroll = Payslip::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd])
            ->sum('net_pay');

        return [
            Stat::make('Total salariés', Employee::query()->count())
                ->description('Effectif total enregistré')
                ->icon(Heroicon::OutlinedUsers)
                ->color('primary'),
            Stat::make('Salariés actifs', Employee::query()->where('status', 'active')->count())
                ->description('Contrats actifs dans la base')
                ->icon(Heroicon::OutlinedIdentification)
                ->color('success'),
            Stat::make('Congés en attente', LeaveRequest::query()->where('status', 'pending')->count())
                ->description('Demandes à valider')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('warning'),
            Stat::make('Absences du mois', Absence::query()->whereBetween('date', [$monthStart, $monthEnd])->count())
                ->description('Absences et retards déclarés')
                ->icon(Heroicon::OutlinedClock)
                ->color('danger'),
            Stat::make('Bulletins générés', Payslip::query()->whereBetween('created_at', [$monthStart, $monthEnd])->count())
                ->description('Bulletins créés ce mois')
                ->icon(Heroicon::OutlinedDocumentCurrencyDollar)
                ->color('info'),
            Stat::make('Masse salariale', number_format((float) $monthlyPayroll, 2, ',', ' ') . ' MAD')
                ->description('Net à payer cumulé')
                ->icon(Heroicon::OutlinedBanknotes)
                ->color('success'),
            Stat::make('Points d’attention', $this->alertsCount())
                ->description('Dossiers à vérifier avant paie')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('danger'),
            Stat::make('Abonnement', $this->subscriptionLabel())
                ->description('Statut du plan SaaS')
                ->icon(Heroicon::OutlinedCreditCard)
                ->color('primary'),
        ];
    }

    private function alertsCount(): int
    {
        return Employee::query()->whereNull('cnss_number')->count()
            + Employee::query()->whereDoesntHave('bankAccounts')->count()
            + Contract::query()->whereNotNull('end_date')->whereBetween('end_date', [now(), now()->addDays(45)])->count()
            + EmployeeDocument::query()->whereNotNull('expires_at')->whereDate('expires_at', '<', now())->count()
            + LeaveRequest::query()->where('status', 'pending')->count();
    }

    private function subscriptionLabel(): string
    {
        $subscription = Subscription::query()->with('plan')->latest('starts_at')->first();

        if (! $subscription) {
            return 'À configurer';
        }

        return ($subscription->plan?->name ?: 'Plan') . ' · ' . ucfirst($subscription->status);
    }
}
