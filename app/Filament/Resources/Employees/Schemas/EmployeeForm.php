<?php

namespace App\Filament\Resources\Employees\Schemas;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Profil salarié')
                    ->description('Synthèse rapide du collaborateur avant modification de sa fiche.')
                    ->icon('heroicon-o-identification')
                    ->extraAttributes(['class' => 'sr-employee-profile-section'])
                    ->hidden(fn (?Employee $record): bool => ! $record)
                    ->schema([
                        Placeholder::make('employee_profile_summary')
                            ->hiddenLabel()
                            ->content(function (?Employee $record): HtmlString {
                                if (! $record) {
                                    return new HtmlString('');
                                }

                                $initials = strtoupper(substr((string) $record->first_name, 0, 1) . substr((string) $record->last_name, 0, 1));
                                $position = $record->position?->title ?: $record->job_title ?: 'Poste à compléter';
                                $department = $record->department?->name ?: $record->department ?: 'Département à compléter';
                                $salary = $record->base_salary !== null ? number_format((float) $record->base_salary, 2, ',', ' ') . ' MAD' : 'Salaire à compléter';
                                $hireDate = $record->hire_date?->format('d/m/Y') ?: 'Date à compléter';

                                return new HtmlString(
                                    '<div class="sr-employee-profile-card">'
                                    . '<div class="sr-employee-avatar">' . e($initials ?: 'SR') . '</div>'
                                    . '<div class="sr-employee-main">'
                                    . '<div class="sr-employee-name">' . e($record->full_name) . '</div>'
                                    . '<div class="sr-employee-meta">' . e($record->employee_number ?: 'Matricule à compléter') . ' · ' . e($position) . ' · ' . e($department) . '</div>'
                                    . '</div>'
                                    . '<div class="sr-employee-kpi"><span>Statut</span><strong>' . e($record->status ?: 'active') . '</strong></div>'
                                    . '<div class="sr-employee-kpi"><span>Salaire</span><strong>' . e($salary) . '</strong></div>'
                                    . '<div class="sr-employee-kpi"><span>Embauche</span><strong>' . e($hireDate) . '</strong></div>'
                                    . '</div>'
                                );
                            }),
                    ])
                    ->columnSpanFull(),
                Tabs::make('Fiche salarié')
                    ->persistTabInQueryString()
                    ->tabs([
                        Tab::make('Informations personnelles')
                            ->schema([
                                Section::make('Informations personnelles')
                                    ->description('État civil et identification')
                                    ->icon('heroicon-o-user')
                                    ->columns(3)
                                    ->contained(false)
                                    ->schema([
                                        Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload()->required(),
                                        TextInput::make('employee_number')->label('Matricule')->required()->maxLength(255),
                                        Select::make('status')
                                            ->label('Statut')
                                            ->options(['active' => 'Actif', 'inactive' => 'Inactif', 'terminated' => 'Sorti'])
                                            ->default('active')
                                            ->required(),
                                        TextInput::make('first_name')->label('Prénom')->required()->maxLength(255),
                                        TextInput::make('last_name')->label('Nom')->required()->maxLength(255),
                                        TextInput::make('cin')->label('CIN')->maxLength(255),
                                        DatePicker::make('birth_date')->label('Date de naissance'),
                                    ]),
                                Section::make('Coordonnées')
                                    ->description('Adresse et moyens de contact')
                                    ->icon('heroicon-o-envelope')
                                    ->columns(3)
                                    ->contained(false)
                                    ->schema([
                                        TextInput::make('email')->label('Email')->email()->maxLength(255),
                                        TextInput::make('phone')->label('Téléphone')->tel()->maxLength(255),
                                        Textarea::make('address')->label('Adresse')->columnSpanFull(),
                                    ]),
                            ]),
                        Tab::make('Informations professionnelles')
                            ->schema([
                                Section::make('Informations professionnelles')
                                    ->description('Poste, contrat et affiliation CNSS')
                                    ->icon('heroicon-o-briefcase')
                                    ->columns(3)
                                    ->contained(false)
                                    ->schema([
                                        Select::make('department_id')
                                            ->label('Département')
                                            ->options(fn ($get) => Department::query()->where('company_id', $get('company_id'))->pluck('name', 'id'))
                                            ->searchable()
                                            ->preload(),
                                        Select::make('position_id')
                                            ->label('Poste')
                                            ->options(fn ($get) => Position::query()->where('company_id', $get('company_id'))->pluck('title', 'id'))
                                            ->searchable()
                                            ->preload(),
                                        TextInput::make('job_title')->label('Intitulé du poste')->maxLength(255),
                                        TextInput::make('department')->label('Département libre')->maxLength(255),
                                        Select::make('contract_type')
                                            ->label('Type de contrat')
                                            ->options(['cdi' => 'CDI', 'cdd' => 'CDD', 'stage' => 'Stage', 'interim' => 'Intérim'])
                                            ->default('cdi')
                                            ->required(),
                                        DatePicker::make('hire_date')->label('Date d’embauche')->required(),
                                        DatePicker::make('probation_ends_at')->label('Fin période d’essai'),
                                        DatePicker::make('termination_date')->label('Date de sortie'),
                                        TextInput::make('cnss_number')->label('Numéro CNSS')->maxLength(255),
                                    ]),
                            ]),
                        Tab::make('Informations salariales')
                            ->schema([
                                Section::make('Informations salariales')
                                    ->description('Salaire, primes et personnes à charge')
                                    ->icon('heroicon-o-banknotes')
                                    ->columns(3)
                                    ->contained(false)
                                    ->schema([
                                        Select::make('family_situation')
                                            ->label('Situation familiale')
                                            ->options([
                                                'single' => 'Célibataire',
                                                'married' => 'Marié(e)',
                                                'divorced' => 'Divorcé(e)',
                                                'widowed' => 'Veuf / veuve',
                                            ]),
                                        TextInput::make('dependents_count')->label('Personnes à charge')->numeric()->default(0)->required(),
                                        TextInput::make('children_count')->label('Enfants à charge')->numeric()->default(0),
                                        TextInput::make('base_salary')->label('Salaire de base')->numeric()->prefix('MAD')->required(),
                                        TextInput::make('seniority_bonus')->label('Prime ancienneté')->numeric()->prefix('MAD')->default(0),
                                        TextInput::make('transport_bonus')->label('Indemnité transport')->numeric()->prefix('MAD')->default(0),
                                        TextInput::make('meal_bonus')->label('Prime panier')->numeric()->prefix('MAD')->default(0),
                                        TextInput::make('performance_bonus')->label('Prime rendement')->numeric()->prefix('MAD')->default(0),
                                        TextInput::make('overtime_amount')->label('Heures supplémentaires')->numeric()->prefix('MAD')->default(0),
                                        TextInput::make('working_hours_per_month')->label('Heures mensuelles')->numeric()->default(191)->required(),
                                    ]),
                            ]),
                        Tab::make('Informations bancaires')
                            ->schema([
                                Section::make('Informations bancaires')
                                    ->description('Comptes bancaires et RIB du salarié')
                                    ->icon('heroicon-o-building-library')
                                    ->contained(false)
                                    ->schema([
                                        Placeholder::make('bank_accounts_note')
                                            ->label('Comptes bancaires')
                                            ->content('Les RIB et comptes bancaires sont conservés dans la table employee_bank_accounts et peuvent être exposés ensuite via un relation manager dédié.'),
                                    ]),
                            ]),
                        Tab::make('Documents')
                            ->schema([
                                Section::make('Documents')
                                    ->description('Documents RH et contrats générés')
                                    ->icon('heroicon-o-document-text')
                                    ->contained(false)
                                    ->schema([
                                        Placeholder::make('documents_note')
                                            ->label('Documents salarié')
                                            ->content('Les documents salarié et documents générés restent disponibles dans leurs modules dédiés.'),
                                    ]),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
