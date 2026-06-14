<?php

namespace App\Filament\Resources\Absences\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AbsencesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->sortable()->searchable(),
                TextColumn::make('employee.full_name')->label('Employee')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('date')->date()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('hours')->sortable(),
                TextColumn::make('duration_days')->label('Jours')->sortable(),
                IconColumn::make('payroll_impact')->label('Impact paie')->boolean(),
                TextColumn::make('deduction_amount')->label('Retenue')->money('MAD')->sortable(),
                TextColumn::make('reason')->limit(40)->toggleable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),
                SelectFilter::make('type')->options([
                    'justified' => 'Justified absence',
                    'unjustified' => 'Unjustified absence',
                    'late' => 'Late arrival',
                ]),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
