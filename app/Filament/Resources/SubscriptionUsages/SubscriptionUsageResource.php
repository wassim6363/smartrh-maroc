<?php

namespace App\Filament\Resources\SubscriptionUsages;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\SubscriptionUsages\Pages\ListSubscriptionUsages;
use App\Models\SubscriptionUsage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionUsageResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = SubscriptionUsage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static string|\UnitEnum|null $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Utilisation';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Societe')->searchable()->sortable(),
                TextColumn::make('subscription.plan.name')->label('Plan')->sortable(),
                TextColumn::make('period_year')->label('Annee')->sortable(),
                TextColumn::make('period_month')->label('Mois')->sortable(),
                TextColumn::make('employees_count')->label('Salaries')->sortable(),
                TextColumn::make('payslips_generated')->label('Bulletins generes')->sortable(),
                TextColumn::make('contracts_generated')->label('Contrats generes')->sortable(),
                TextColumn::make('documents_generated')->label('Documents generes')->sortable(),
                TextColumn::make('updated_at')->label('Derniere mise a jour')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('company')->relationship('company', 'name')->label('Societe'),
                SelectFilter::make('period_month')->label('Mois')->options(array_combine(range(1, 12), range(1, 12))),
            ])
            ->recordActions([
                Action::make('resetUsage')
                    ->label('Réinitialiser le mois')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (SubscriptionUsage $record) => $record->update([
                        'payslips_generated' => 0,
                        'contracts_generated' => 0,
                        'documents_generated' => 0,
                    ])),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptionUsages::route('/'),
        ];
    }
}
