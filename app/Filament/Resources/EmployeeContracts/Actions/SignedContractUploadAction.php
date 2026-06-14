<?php

namespace App\Filament\Resources\EmployeeContracts\Actions;

use App\Models\EmployeeContract;
use App\Services\Documents\ContractSignatureService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;

class SignedContractUploadAction
{
    public static function make(): Action
    {
        return Action::make('uploadSignedPdf')
            ->label('Uploader PDF signé')
            ->icon('heroicon-o-arrow-up-tray')
            ->form([
                FileUpload::make('signed_pdf_path')
                    ->label('PDF signé')
                    ->disk('local')
                    ->directory('companies/signed-contracts')
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(5120)
                    ->required(),
            ])
            ->action(function (EmployeeContract $record, array $data, ContractSignatureService $signatures): void {
                $signatures->markSigned($record, $data['signed_pdf_path']);
                Notification::make()->title('Contrat signé enregistré')->success()->send();
            });
    }
}
