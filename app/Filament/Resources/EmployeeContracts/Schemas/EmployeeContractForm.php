<?php

namespace App\Filament\Resources\EmployeeContracts\Schemas;

use App\Filament\Resources\EmployeeContracts\EmployeeContractResource;
use App\Models\ContractTemplate;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class EmployeeContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Générer un contrat')
                ->columns(3)
                ->schema([
                    Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload()->required(),
                    Select::make('employee_id')
                        ->label('Salarié')
                        ->options(fn ($get) => Employee::query()->where('company_id', $get('company_id'))->get()->mapWithKeys(fn (Employee $employee) => [$employee->id => "{$employee->employee_number} - {$employee->full_name}"]))
                        ->searchable()
                        ->required(),
                    Select::make('contract_template_id')
                        ->label('Modèle de contrat')
                        ->options(fn ($get) => ContractTemplate::query()
                            ->where(function ($query) use ($get) {
                                $query->where('company_id', $get('company_id'))->orWhereNull('company_id');
                            })
                            ->where('is_active', true)
                            ->pluck('title', 'id'))
                        ->searchable()
                        ->required(),
                    Select::make('type')->label('Type de contrat')->options(EmployeeContractResource::typeOptions())->required(),
                    TextInput::make('title')->label('Titre')->maxLength(255),
                    TextInput::make('reference')->label('Référence')->maxLength(255),
                    DatePicker::make('start_date')->label('Date début')->required(),
                    DatePicker::make('end_date')->label('Date fin'),
                    TextInput::make('salary')->label('Salaire')->numeric()->prefix('MAD'),
                    TextInput::make('job_title')->label('Poste')->maxLength(255),
                    TextInput::make('city')->label('Ville')->maxLength(255),
                    Select::make('status')->label('Statut')->options([
                        'draft' => 'Brouillon',
                        'generated' => 'Généré',
                        'signed' => 'Signé',
                        'archived' => 'Archivé',
                        'cancelled' => 'Annulé',
                    ])->default('draft'),
                ]),
            Section::make('Contenu généré')
                ->schema([
                    RichEditor::make('content_html')->label('Contenu HTML')->columnSpanFull(),
                ]),
        ]);
    }
}
