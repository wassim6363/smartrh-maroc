<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class WorkflowAlertsWidget extends TableWidget
{
    protected static ?string $heading = 'Congés en attente';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 14;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                LeaveRequest::query()
                    ->with(['company', 'employee', 'leaveType'])
                    ->where('status', 'pending')
                    ->latest(),
            )
            ->columns([
                TextColumn::make('company.name')->label('Société')->searchable(),
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('leaveType.name')->label('Type'),
                TextColumn::make('starts_at')->label('Début')->date('d/m/Y')->sortable(),
                TextColumn::make('ends_at')->label('Fin')->date('d/m/Y')->sortable(),
                TextColumn::make('days')->label('Jours')->sortable(),
            ])
            ->emptyStateHeading('Aucune demande en attente')
            ->emptyStateDescription('Les congés à valider apparaîtront ici.')
            ->paginated([5, 10, 25]);
    }
}
