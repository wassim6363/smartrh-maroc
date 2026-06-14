<?php

namespace App\Filament\Resources\EmployeePayrollItems\Pages;

use App\Filament\Resources\EmployeePayrollItems\EmployeePayrollItemResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditEmployeePayrollItem extends EditRecord
{
    protected static string $resource = EmployeePayrollItemResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
