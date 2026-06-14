<?php

namespace App\Filament\Resources\EmployeePayrollItems\Pages;

use App\Filament\Resources\EmployeePayrollItems\EmployeePayrollItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployeePayrollItem extends CreateRecord
{
    protected static string $resource = EmployeePayrollItemResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! auth()->user()?->isSuperAdmin()) {
            $data['company_id'] = auth()->user()?->currentCompanyId();
        }

        return $data;
    }
}
