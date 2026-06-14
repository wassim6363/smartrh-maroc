<?php

namespace App\Filament\Resources\EmployeeContracts\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeContractInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contrat employé')
                ->columns(4)
                ->schema([
                    TextEntry::make('reference')->label('Référence'),
                    TextEntry::make('company.name')->label('Société'),
                    TextEntry::make('employee.full_name')->label('Salarié'),
                    TextEntry::make('type')->label('Type de contrat')->badge(),
                    TextEntry::make('start_date')->label('Date début')->date('d/m/Y'),
                    TextEntry::make('end_date')->label('Date fin')->date('d/m/Y')->placeholder('-'),
                    TextEntry::make('salary')->label('Salaire')->money('MAD')->placeholder('-'),
                    TextEntry::make('job_title')->label('Poste')->placeholder('-'),
                    TextEntry::make('city')->label('Ville')->placeholder('-'),
                    TextEntry::make('status')->label('Statut')->badge(),
                    TextEntry::make('generated_at')->label('Généré le')->dateTime('d/m/Y H:i'),
                    TextEntry::make('signed_at')->label('Signé le')->dateTime('d/m/Y H:i')->placeholder('-'),
                    TextEntry::make('signed_pdf_path')->label('PDF signé')->placeholder('-')->columnSpan(2),
                ]),
            Section::make('Contenu')
                ->schema([
                    TextEntry::make('content_html')->label('Contrat')->html()->columnSpanFull(),
                ]),
        ]);
    }
}
