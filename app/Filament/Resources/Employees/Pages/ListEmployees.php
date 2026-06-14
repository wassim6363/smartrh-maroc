<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Imports\EmployeesImport;
use App\Models\Company;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected ?string $subheading = 'Gérez les fiches salariés, contrats, informations CNSS et éléments de paie.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadTemplate')
                ->label('Télécharger le modèle')
                ->icon('heroicon-o-arrow-down-tray')
                ->url(fn (): string => route('employees.import-template'))
                ->openUrlInNewTab(),
            Action::make('import')
                ->label('Importer')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    FileUpload::make('file')
                        ->label('Fichier Excel ou CSV')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                            'text/csv',
                            'text/plain',
                        ])
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $companyId = auth()->user()?->currentCompanyId();
                    if (! $companyId) {
                        Notification::make()->title('Aucune entreprise associée.')->danger()->send();

                        return;
                    }

                    $company = Company::query()->find($companyId);
                    if (! $company) {
                        Notification::make()->title('Entreprise introuvable.')->danger()->send();

                        return;
                    }

                    $import = new EmployeesImport($company);
                    $import->import($data['file']);

                    Notification::make()
                        ->title(sprintf(
                            'Import terminé : %d importé(s), %d ignoré(s).',
                            $import->getImportedCount(),
                            $import->getSkippedCount()
                        ))
                        ->success()
                        ->send();

                    foreach ($import->getErrors() as $error) {
                        Notification::make()->title($error)->warning()->send();
                    }
                }),
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
            CreateAction::make(),
        ];
    }
}
