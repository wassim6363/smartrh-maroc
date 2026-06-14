<?php

namespace App\Filament\Resources\Companies\Tables;

use App\Models\Company;
use App\Services\Saas\SubscriptionLimitService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('ice')->label('ICE')->searchable(),
                TextColumn::make('city')->searchable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('current_plan')->label('Plan')->state(fn (Company $record): string => $record->activeSubscription?->plan?->name ?? 'Aucun')->badge(),
                TextColumn::make('subscription_status')->label('Abonnement')->state(fn (Company $record): string => $record->activeSubscription?->status ?? 'missing')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active', 'trialing' => 'success',
                        'past_due' => 'warning',
                        'cancelled', 'expired', 'missing' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('trial_ends_at')->label('Fin essai')->state(fn (Company $record): ?string => $record->activeSubscription?->trial_ends_at?->format('d/m/Y'))->placeholder('-'),
                TextColumn::make('latest_invoice')->label('Derniere facture')->state(fn (Company $record): string => $record->invoices()->latest('issued_at')->value('invoice_number') ?: '-'),
                TextColumn::make('employee_usage')->label('Salaries')->state(fn (Company $record): string => self::usageLabel($record, 'employees')),
                TextColumn::make('payslip_usage')->label('Bulletins/mois')->state(fn (Company $record): string => self::usageLabel($record, 'payslips')),
                TextColumn::make('contract_usage')->label('Contrats/mois')->state(fn (Company $record): string => self::usageLabel($record, 'contracts')),
                TextColumn::make('upgrade_message')->label('Alerte')->state(fn (Company $record): string => self::upgradeMessage($record))->badge()
                    ->color(fn (string $state): string => $state === 'OK' ? 'success' : 'warning'),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function usageLabel(Company $company, string $key): string
    {
        $summary = app(SubscriptionLimitService::class)->getLimitsSummary($company)[$key];

        return sprintf('%s / %s', $summary['used'], $summary['limit'] ?: 'illimite');
    }

    private static function upgradeMessage(Company $company): string
    {
        $summary = app(SubscriptionLimitService::class)->getLimitsSummary($company);

        foreach (['employees', 'payslips', 'contracts'] as $key) {
            $limit = $summary[$key]['limit'] ?? null;
            if ($limit && $summary[$key]['used'] >= $limit) {
                return 'Vous avez atteint la limite de votre abonnement.';
            }

            if ($limit && ($summary[$key]['used'] / max($limit, 1)) >= 0.8) {
                return 'Utilisation actuelle de votre abonnement: 80%+';
            }
        }

        return 'OK';
    }
}
