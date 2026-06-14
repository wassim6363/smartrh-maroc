<?php

namespace App\Filament\Resources\EmployeeContracts\Pages;

use App\Filament\Resources\EmployeeContracts\EmployeeContractResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeeContracts extends ListRecords
{
    protected static string $resource = EmployeeContractResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Générer un contrat')];
    }
}
