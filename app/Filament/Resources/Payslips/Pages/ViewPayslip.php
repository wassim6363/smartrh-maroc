<?php

namespace App\Filament\Resources\Payslips\Pages;

use App\Filament\Resources\Payslips\PayslipResource;
use App\Models\Payslip;
use App\Services\Payroll\PayslipPdfService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewPayslip extends ViewRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (Payslip $record): string => route('payslips.download', $record))
                ->openUrlInNewTab(),
            Action::make('regeneratePdf')
                ->label('Régénérer PDF')
                ->icon('heroicon-o-arrow-path')
                ->action(function (Payslip $record, PayslipPdfService $generator): void {
                    $generator->generate($record);
                    Notification::make()->title('PDF régénéré')->success()->send();
                }),
            EditAction::make(),
        ];
    }
}
