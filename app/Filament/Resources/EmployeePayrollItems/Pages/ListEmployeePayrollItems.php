<?php

namespace App\Filament\Resources\EmployeePayrollItems\Pages;

use App\Filament\Resources\EmployeePayrollItems\EmployeePayrollItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEmployeePayrollItems extends ListRecords
{
    protected static string $resource = EmployeePayrollItemResource::class;

    protected ?string $subheading = 'Gérez primes, indemnités, avances, retenues et heures supplémentaires par salarié.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
