<?php

namespace App\Filament\Resources\EmployeeContracts\Pages;

use App\Filament\Resources\EmployeeContracts\EmployeeContractResource;
use App\Models\EmployeeContract;
use App\Services\Documents\ContractGeneratorService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateEmployeeContract extends CreateRecord
{
    protected static string $resource = EmployeeContractResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(ContractGeneratorService::class)->generate($data);
    }

    protected function getRedirectUrl(): string
    {
        /** @var EmployeeContract $record */
        $record = $this->record;

        return EmployeeContractResource::getUrl('view', ['record' => $record]);
    }
}
