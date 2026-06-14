<?php

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = AuditLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldExclamation;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?string $navigationLabel = 'Journal d’audit';

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(['Super Admin', 'Company Owner']) ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Société')->sortable(),
                TextColumn::make('user.email')->label('Utilisateur')->searchable(),
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('action')->label('Action')->searchable()->badge(),
                TextColumn::make('auditable_type')->label('Objet')->toggleable(),
                TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->options(fn (): array => AuditLog::query()
                        ->whereNotNull('action')
                        ->distinct()
                        ->orderBy('action')
                        ->pluck('action')
                        ->mapWithKeys(fn ($action): array => [(string) $action => (string) $action])
                        ->all())
                    ->label('Action'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListAuditLogs::route('/')];
    }
}
