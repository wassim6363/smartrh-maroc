<?php

namespace App\Filament\Resources\EmployeeContracts\Pages;

use App\Filament\Resources\EmployeeContracts\Actions\SignedContractUploadAction;
use App\Filament\Resources\EmployeeContracts\EmployeeContractResource;
use App\Models\EmployeeContract;
use App\Services\Documents\ContractPdfService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewEmployeeContract extends ViewRecord
{
    protected static string $resource = EmployeeContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')->label('Télécharger PDF')->icon('heroicon-o-document-arrow-down')->url(fn (EmployeeContract $record): string => route('contracts.download', $record))->openUrlInNewTab(),
            Action::make('regeneratePdf')->label('Régénérer PDF')->icon('heroicon-o-arrow-path')->action(function (EmployeeContract $record, ContractPdfService $service): void {
                $service->generate($record);
                Notification::make()->title('PDF régénéré')->success()->send();
            }),
            SignedContractUploadAction::make(),
            EditAction::make(),
        ];
    }
}
