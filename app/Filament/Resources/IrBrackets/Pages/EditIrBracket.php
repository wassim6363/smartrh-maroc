<?php

namespace App\Filament\Resources\IrBrackets\Pages;

use App\Filament\Resources\IrBrackets\IrBracketResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIrBracket extends EditRecord
{
    protected static string $resource = IrBracketResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
