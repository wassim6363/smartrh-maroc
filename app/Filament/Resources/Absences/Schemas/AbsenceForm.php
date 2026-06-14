<?php

namespace App\Filament\Resources\Absences\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AbsenceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
                Select::make('employee_id')->relationship('employee', 'employee_number')->searchable()->preload()->required(),
                DatePicker::make('date')->required(),
                Select::make('type')
                    ->options([
                        'justified' => 'Justified absence',
                        'unjustified' => 'Unjustified absence',
                        'late' => 'Late arrival',
                    ])
                    ->default('unjustified')
                    ->required(),
                TextInput::make('hours')->numeric()->default(0)->required(),
                TextInput::make('duration_days')->label('Durée jours')->numeric(),
                Toggle::make('justified')->label('Justifiee')->default(false),
                Toggle::make('payroll_impact')->label('Impact paie')->default(true),
                TextInput::make('deduction_amount')->label('Montant retenu')->numeric()->prefix('MAD'),
                Textarea::make('reason')->label('HR notes')->columnSpanFull(),
            ]);
    }
}
