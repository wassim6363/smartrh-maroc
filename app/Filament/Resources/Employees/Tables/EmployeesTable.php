<?php

namespace App\Filament\Resources\Employees\Tables;

use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Services\Documents\ContractGeneratorService;
use App\Services\Documents\HrDocumentGenerator;
use App\Services\Imports\EmployeeCsvImporter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class EmployeesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->sortable()->searchable(),
                TextColumn::make('employee_number')->label('No.')->sortable()->searchable(),
                TextColumn::make('full_name')->label('Salarié')->searchable(['first_name', 'last_name'])->sortable(['last_name']),
                TextColumn::make('department_label')->label('Département'),
                TextColumn::make('position_label')->label('Poste'),
                TextColumn::make('family_situation')->label('Situation familiale')->badge()->toggleable(),
                TextColumn::make('dependents_count')->label('Personnes à charge')->sortable()->toggleable(),
                TextColumn::make('base_salary')->label('Salaire de base')->money('MAD')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('hire_date')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société'),
                SelectFilter::make('department_id')->options(fn () => \App\Models\Department::query()->pluck('name', 'id'))->label('Département'),
                SelectFilter::make('status')->label('Statut')->options(['active' => 'Actif', 'inactive' => 'Inactif', 'terminated' => 'Sorti']),
            ])
            ->headerActions([
                Action::make('importEmployees')
                    ->label('Importer employés')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        Select::make('company_id')
                            ->options(fn () => auth()->user()?->isSuperAdmin()
                                ? Company::query()->pluck('name', 'id')
                                : Company::query()->whereKey(auth()->user()?->currentCompanyId())->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        FileUpload::make('file')->label('Fichier CSV')->disk('local')->directory('imports')->acceptedFileTypes(['text/csv', 'text/plain'])->required(),
                    ])
                    ->action(function (array $data, EmployeeCsvImporter $importer): void {
                        $result = $importer->import(Storage::disk('local')->path($data['file']), (int) $data['company_id']);
                        Notification::make()
                            ->title($result['imported'] . ' importés, ' . $result['skipped'] . ' ignorés, ' . count($result['errors']) . ' erreurs')
                            ->body(implode("\n", array_slice($result['errors'], 0, 5)))
                            ->success()
                            ->send();
                    }),
                Action::make('template')
                    ->label('Modèle CSV')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (): string => route('employees.import-template'))
                    ->openUrlInNewTab(),
                Action::make('exportExcel')
                    ->label('Exporter Excel')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (): string => route('exports.employees.xlsx'))
                    ->openUrlInNewTab(),
                Action::make('exportCsv')
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (): string => route('exports.employees'))
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                Action::make('generatePayslip')
                    ->label('Générer fiche de paie')
                    ->icon('heroicon-o-calculator')
                    ->url(fn (Employee $record): string => route('filament.admin.pages.payroll-calculation-preview', ['employee_id' => $record->id])),
                Action::make('generateHrDocument')
                    ->label('Générer document RH')
                    ->icon('heroicon-o-document-plus')
                    ->form([
                        Select::make('contract_template_id')
                            ->label('Modèle')
                            ->options(fn (Employee $record): array => ContractTemplate::query()
                                ->where('company_id', $record->company_id)
                                ->where('is_active', true)
                                ->orderBy('title')
                                ->pluck('title', 'id')
                                ->all())
                            ->searchable()
                            ->required(),
                        TextInput::make('reference')->label('Référence')->maxLength(100),
                        DatePicker::make('last_working_day')->label('Dernier jour travaillé'),
                        TextInput::make('gross_amount')->label('Montant brut')->numeric()->prefix('MAD'),
                        TextInput::make('deductions_amount')->label('Retenues')->numeric()->prefix('MAD'),
                        TextInput::make('net_amount')->label('Net à payer')->numeric()->prefix('MAD'),
                        TextInput::make('payment_method')->label('Mode de paiement')->maxLength(120),
                        TextInput::make('reason_for_departure')->label('Motif du départ')->maxLength(255),
                    ])
                    ->action(fn (Employee $record, array $data) => self::generateTemplateDocument($record, $data)),
                Action::make('attestation')
                    ->label('Attestation')
                    ->icon('heroicon-o-document-text')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'ATTESTATION_TRAVAIL')),
                Action::make('certificat')
                    ->label('Certificat')
                    ->icon('heroicon-o-document-check')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'CERTIFICAT_TRAVAIL')),
                Action::make('contractCdi')
                    ->label('CDI')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'CDI')),
                Action::make('contractCdd')
                    ->label('CDD')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'CDD')),
                Action::make('contractStage')
                    ->label('Stage')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'STAGE')),
                Action::make('solde')
                    ->label('Solde')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'SOLDE_TOUT_COMPTE')),
                Action::make('avenant')
                    ->label('Avenant')
                    ->action(fn (Employee $record) => self::generateQuickDocument($record, 'AVENANT')),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucun salarié')
            ->emptyStateDescription('Ajoutez vos salariés ou importez-les depuis un fichier CSV.')
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    private static function generateQuickDocument(Employee $employee, string $type)
    {
        try {
            $document = app(HrDocumentGenerator::class)->generate(
                employee: $employee,
                type: $type,
                user: auth()->user(),
                variables: [],
            );

            Notification::make()
                ->title('Document généré avec succès')
                ->body('Le document a été généré et peut être téléchargé.')
                ->success()
                ->send();

            return redirect()->to(route('documents.download', $document));
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erreur génération document')
                ->body('Le document n’a pas pu être généré. Merci de réessayer ou de contacter le support.')
                ->danger()
                ->send();

            return null;
        }
    }

    private static function generateTemplateDocument(Employee $employee, array $data)
    {
        try {
            $template = ContractTemplate::query()
                ->where('company_id', $employee->company_id)
                ->findOrFail((int) $data['contract_template_id']);

            $contract = app(ContractGeneratorService::class)->generate([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'contract_template_id' => $template->id,
                'type' => $template->type,
                'reference' => $data['reference'] ?: null,
                'document_reference' => $data['reference'] ?: null,
                'title' => $template->title ?: $template->name,
                'start_date' => $employee->hire_date?->toDateString() ?: now()->toDateString(),
                'salary' => $employee->base_salary,
                'job_title' => $employee->position_label ?: $employee->job_title,
                'city' => $employee->company?->city ?: 'Casablanca',
                'last_working_day' => $data['last_working_day'] ?? null,
                'gross_amount' => $data['gross_amount'] ?? null,
                'deductions_amount' => $data['deductions_amount'] ?? null,
                'net_amount' => $data['net_amount'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'reason_for_departure' => $data['reason_for_departure'] ?? null,
            ]);

            Notification::make()
                ->title('Document généré avec succès')
                ->body('Le document a été généré et peut être téléchargé.')
                ->success()
                ->send();

            return redirect()->to(route('contracts.download', $contract));
        } catch (\Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Erreur génération document')
                ->body('Le document n’a pas pu être généré. Merci de réessayer ou de contacter le support.')
                ->danger()
                ->send();

            return null;
        }
    }
}
