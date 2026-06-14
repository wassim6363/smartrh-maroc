<?php

namespace App\Filament\Resources\SeniorityBonusRates\Pages;

use App\Filament\Resources\SeniorityBonusRates\SeniorityBonusRateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSeniorityBonusRates extends ListRecords
{
    protected static string $resource = SeniorityBonusRateResource::class;

    protected ?string $subheading = 'Définissez les taux de prime d’ancienneté selon les années complètes.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
