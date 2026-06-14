<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->url(fn (Invoice $record): string => route('invoices.download', $record))
                ->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }
}
