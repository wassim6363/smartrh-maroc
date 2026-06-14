<?php

namespace App\Filament\Resources\SeniorityBonusRates\Pages;

use App\Filament\Resources\SeniorityBonusRates\SeniorityBonusRateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSeniorityBonusRate extends EditRecord
{
    protected static string $resource = SeniorityBonusRateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
