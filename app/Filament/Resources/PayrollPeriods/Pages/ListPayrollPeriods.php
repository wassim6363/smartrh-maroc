<?php

namespace App\Filament\Resources\PayrollPeriods\Pages;

use App\Exports\PayrollJournalExport;
use App\Filament\Resources\PayrollPeriods\PayrollPeriodResource;
use App\Models\PayrollPeriod;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Maatwebsite\Excel\Facades\Excel;

class ListPayrollPeriods extends ListRecords
{
    protected static string $resource = PayrollPeriodResource::class;

    protected ?string $subheading = 'Préparez, générez, validez et clôturez les bulletins de paie mensuels.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportPayrollJournal')
                ->label('Exporter le journal de paie')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    $period = PayrollPeriod::query()
                        ->where('company_id', auth()->user()->currentCompanyId())
                        ->where('status', 'closed')
                        ->latest('year')
                        ->latest('month')
                        ->first();

                    if (! $period) {
                        Notification::make()
                            ->title('Aucune période clôturée trouvée pour l\'export.')
                            ->warning()
                            ->send();

                        return;
                    }

                    return Excel::download(
                        new PayrollJournalExport($period->id),
                        sprintf('journal_paie_%s_%s.xlsx', $period->year, str_pad($period->month, 2, '0', STR_PAD_LEFT)),
                    );
                }),
            CreateAction::make(),
        ];
    }
}
