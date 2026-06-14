<?php

namespace App\Filament\Resources\Payslips\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PayslipInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Bulletin de paie')
                ->columns(4)
                ->schema([
                    TextEntry::make('reference')->label('Référence'),
                    TextEntry::make('company.name')->label('Société'),
                    TextEntry::make('employee.full_name')->label('Salarié'),
                    TextEntry::make('payrollPeriod.name')->label('Période de paie'),
                    TextEntry::make('status')->label('Statut')->badge()->color(fn (string $state): string => match ($state) {
                        'validated' => 'success',
                        'sent' => 'info',
                        'generated' => 'warning',
                        'closed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                    TextEntry::make('generated_at')->label('Généré le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('validated_at')->label('Validé le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('pdf_path')->label('PDF')->placeholder('Non généré'),
                ]),
            Section::make('Informations fiscales')
                ->columns(4)
                ->schema([
                    TextEntry::make('gross_total')->label('Salaire brut')->money('MAD'),
                    TextEntry::make('taxable_gross')->label('Brut imposable')->money('MAD'),
                    TextEntry::make('cnss_base')->label('Base CNSS')->money('MAD'),
                    TextEntry::make('amo_base')->label('Base AMO')->money('MAD'),
                    TextEntry::make('cnss_employee')->label('CNSS salarié')->money('MAD'),
                    TextEntry::make('amo_employee')->label('AMO salarié')->money('MAD'),
                    TextEntry::make('salary_after_contributions')->label('Salaire après cotisations')->money('MAD'),
                    TextEntry::make('professional_expenses')->label('Frais professionnels')->money('MAD'),
                    TextEntry::make('taxable_net_income')->label('Revenu net imposable')->money('MAD'),
                    TextEntry::make('ir_net')->label('IR')->money('MAD'),
                    TextEntry::make('exempt_allowances')->label('Indemnités exonérées')->money('MAD'),
                    TextEntry::make('net_to_pay')->label('Net à payer')->money('MAD')->weight('bold'),
                ]),
            Section::make('Cumuls annuels')
                ->columns(4)
                ->schema([
                    TextEntry::make('ytd_gross_salary')->label('Cumul brut')->money('MAD'),
                    TextEntry::make('ytd_taxable_income')->label('Cumul imposable')->money('MAD'),
                    TextEntry::make('ytd_ir')->label('Cumul IR')->money('MAD'),
                    TextEntry::make('ytd_net_pay')->label('Cumul net à payer')->money('MAD'),
                ]),
        ]);
    }
}
