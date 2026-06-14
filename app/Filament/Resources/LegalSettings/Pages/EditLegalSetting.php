<?php

namespace App\Filament\Resources\LegalSettings\Pages;

use App\Filament\Resources\LegalSettings\LegalSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLegalSetting extends EditRecord
{
    protected static string $resource = LegalSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
