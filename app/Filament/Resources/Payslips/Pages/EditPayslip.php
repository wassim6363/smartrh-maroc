<?php

namespace App\Filament\Resources\Payslips\Pages;

use App\Filament\Resources\Payslips\PayslipResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;

class EditPayslip extends EditRecord
{
    protected static string $resource = PayslipResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function beforeSave(): void
    {
        if ($this->record->payrollPeriod?->status === 'closed' && ! auth()->user()?->hasAnyRole(['Super Admin', 'Payroll Manager'])) {
            Notification::make()->title('La période de paie est clôturée')->danger()->send();
            throw new Halt();
        }
    }
}
