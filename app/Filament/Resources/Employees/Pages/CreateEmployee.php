<?php

namespace App\Filament\Resources\Employees\Pages;

use App\Filament\Resources\Employees\EmployeeResource;
use App\Models\Company;
use App\Services\Saas\SubscriptionGate;
use Filament\Resources\Pages\CreateRecord;

class CreateEmployee extends CreateRecord
{
    protected static string $resource = EmployeeResource::class;

    protected function beforeCreate(): void
    {
        $company = Company::query()->find($this->data['company_id'] ?? null);

        if ($company) {
            app(SubscriptionGate::class)->assertCanAddEmployee($company);
        }
    }
}
