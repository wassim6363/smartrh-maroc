<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentActivityWidget extends TableWidget
{
    protected static ?string $heading = 'Activité récente';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public function table(Table $table): Table
    {
        return $table
            ->query(AuditLog::query()->with(['company', 'user'])->latest())
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('company.name')->label('Société'),
                TextColumn::make('user.email')->label('Utilisateur'),
                TextColumn::make('event')->label('Action')->badge(),
            ])
            ->emptyStateHeading('Aucune activité récente')
            ->emptyStateDescription('Les actions importantes apparaîtront ici.')
            ->paginated([5, 10]);
    }
}
