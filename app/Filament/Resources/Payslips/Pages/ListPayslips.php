<?php

namespace App\Filament\Resources\Payslips\Pages;

use App\Filament\Resources\Payslips\PayslipResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayslips extends ListRecords
{
    protected static string $resource = PayslipResource::class;

    protected ?string $subheading = 'Consultez les bulletins générés, leurs calculs et leurs PDF sécurisés.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
