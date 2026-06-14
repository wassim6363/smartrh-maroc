<?php

namespace App\Filament\Resources\IrBrackets\Pages;

use App\Filament\Resources\IrBrackets\IrBracketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIrBrackets extends ListRecords
{
    protected static string $resource = IrBracketResource::class;

    protected ?string $subheading = 'Paramétrez les tranches IR et les dates d’application à vérifier avant production.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
