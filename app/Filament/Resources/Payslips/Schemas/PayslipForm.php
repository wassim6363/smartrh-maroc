<?php

namespace App\Filament\Resources\Payslips\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayslipForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Bulletin de paie')
                    ->columns(3)
                    ->schema([
                        Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload()->required(),
                        Select::make('payroll_period_id')->label('Période de paie')->relationship('payrollPeriod', 'name')->searchable()->preload()->required(),
                        Select::make('employee_id')->label('Salarié')->relationship('employee', 'employee_number')->searchable()->preload()->required(),
                        TextInput::make('reference')->label('Référence')->required()->maxLength(255),
                        Select::make('status')
                            ->label('Statut')
                            ->options(['draft' => 'Brouillon', 'generated' => 'Généré', 'validated' => 'Validé', 'sent' => 'Envoyé', 'closed' => 'Clôturé', 'cancelled' => 'Annulé'])
                            ->default('draft')
                            ->required(),
                    ]),
                Section::make('Informations fiscales')
                    ->columns(3)
                    ->schema([
                        TextInput::make('gross_total')->label('Salaire brut')->numeric()->prefix('MAD')->required(),
                        TextInput::make('taxable_gross')->label('Brut imposable')->numeric()->prefix('MAD')->required(),
                        TextInput::make('cnss_base')->label('Base CNSS')->numeric()->prefix('MAD'),
                        TextInput::make('amo_base')->label('Base AMO')->numeric()->prefix('MAD'),
                        TextInput::make('cnss_employee')->label('CNSS salarié')->numeric()->prefix('MAD'),
                        TextInput::make('amo_employee')->label('AMO salarié')->numeric()->prefix('MAD'),
                        TextInput::make('professional_expenses')->label('Frais professionnels')->numeric()->prefix('MAD'),
                        TextInput::make('taxable_net_income')->label('Revenu net imposable')->numeric()->prefix('MAD'),
                        TextInput::make('ir_net')->label('IR')->numeric()->prefix('MAD'),
                        TextInput::make('exempt_allowances')->label('Indemnités exonérées')->numeric()->prefix('MAD'),
                        TextInput::make('total_deductions')->label('Total retenues')->numeric()->prefix('MAD'),
                        TextInput::make('net_to_pay')->label('Net à payer')->numeric()->prefix('MAD')->required(),
                    ]),
            ]);
    }
}
