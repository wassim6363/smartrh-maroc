<?php
namespace App\Filament\Resources\SupportTickets\Pages;
use App\Filament\Resources\SupportTickets\SupportTicketResource;
use App\Services\Support\SupportTicketService;
use Filament\Resources\Pages\CreateRecord;

class CreateSupportTicket extends CreateRecord
{
    protected static string $resource = SupportTicketResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= auth()->id();
        $data['assigned_to_user_id'] ??= $data['assigned_to'] ?? null;
        if (! auth()->user()?->isSuperAdmin()) {
            $data['company_id'] = auth()->user()?->currentCompanyId();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        app(SupportTicketService::class)->changeStatus($this->record, $this->record->status ?: 'open');
    }
}
