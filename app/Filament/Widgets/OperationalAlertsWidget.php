<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class OperationalAlertsWidget extends TableWidget
{
    protected static ?string $heading = 'Points d’attention RH';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 12;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->where('status', 'active')
                    ->where(function (Builder $query) {
                        $query
                            ->whereNull('cnss_number')
                            ->orWhere('cnss_number', '')
                            ->orDoesntHave('bankAccounts')
                            ->orWhere(function (Builder $query) {
                                $query
                                    ->whereNotNull('probation_ends_at')
                                    ->whereBetween('probation_ends_at', [now(), now()->addDays(30)]);
                            });
                    }),
            )
            ->columns([
                TextColumn::make('company.name')->label('Société')->searchable(),
                TextColumn::make('full_name')->label('Salarié')->searchable(['first_name', 'last_name']),
                TextColumn::make('employee_number')->label('Matricule')->searchable(),
                TextColumn::make('cnss_number')->label('CNSS')->placeholder('Manquant'),
                TextColumn::make('bank_accounts_count')->counts('bankAccounts')->label('RIB'),
                TextColumn::make('probation_ends_at')->date('d/m/Y')->label('Fin période essai'),
            ])
            ->emptyStateHeading('Aucune alerte RH')
            ->emptyStateDescription('Les dossiers salariés essentiels sont complets pour le moment.')
            ->paginated([5, 10, 25]);
    }
}
