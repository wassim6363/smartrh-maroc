<?php

namespace App\Filament\Resources\SupportTickets\Pages;

use App\Filament\Resources\SupportTickets\SupportTicketResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupportTickets extends ListRecords
{
    protected static string $resource = SupportTicketResource::class;

    protected ?string $subheading = 'Suivez les demandes support et les priorités clients.';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
