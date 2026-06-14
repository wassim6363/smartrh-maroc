<?php

namespace App\Filament\Resources\Positions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PositionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->sortable()->searchable(),
                TextColumn::make('department.name')->sortable()->searchable(),
                TextColumn::make('title')->sortable()->searchable(),
                TextColumn::make('min_salary')->money('MAD')->sortable(),
                TextColumn::make('max_salary')->money('MAD')->sortable(),
                TextColumn::make('employees_count')->counts('employees')->label('Employees')->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),
                SelectFilter::make('department_id')->relationship('department', 'name')->label('Department'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
