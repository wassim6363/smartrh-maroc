<?php

namespace App\Filament\Resources\SupportTickets;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\SupportTickets\Pages\CreateSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\EditSupportTicket;
use App\Filament\Resources\SupportTickets\Pages\ListSupportTickets;
use App\Filament\Resources\SupportTickets\Pages\ViewSupportTicket;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Support\SupportTicketService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SupportTicketResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = SupportTicket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    protected static string|\UnitEnum|null $navigationGroup = 'Support';

    protected static ?string $navigationLabel = 'Tickets support';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload()->required(),
            Select::make('user_id')->label('Utilisateur')->relationship('user', 'email')->searchable()->preload(),
            Select::make('employee_id')->label('Salarié')->relationship('employee', 'employee_number')->searchable()->preload(),
            TextInput::make('subject')->label('Sujet')->required(),
            Select::make('category')->label('Catégorie')->options(SupportTicket::categories())->required()->default('technical'),
            Select::make('priority')->label('Priorité')->options(SupportTicket::priorities())->default('normal')->required(),
            Select::make('status')->label('Statut')->options(SupportTicket::statuses())->default('open')->required(),
            Select::make('assigned_to_user_id')->label('Assigné à')->relationship('assignedUser', 'email')->searchable()->preload(),
            Textarea::make('message')->label('Message')->columnSpanFull()->required(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('subject')->label('Sujet')->searchable()->sortable(),
                TextColumn::make('company.name')->label('Société')->searchable()->sortable(),
                TextColumn::make('requester')->label('Demandeur')->state(fn (SupportTicket $record): string => $record->employee?->full_name ?: ($record->user?->name ?: '-')),
                TextColumn::make('category')->label('Catégorie')->badge()->state(fn (SupportTicket $record): string => $record->category_label),
                TextColumn::make('priority')->label('Priorité')->badge()->state(fn (SupportTicket $record): string => $record->priority_label)
                    ->color(fn (SupportTicket $record): string => match ($record->priority) {
                        'urgent' => 'danger',
                        'high' => 'warning',
                        'low' => 'gray',
                        default => 'info',
                    }),
                TextColumn::make('status')->label('Statut')->badge()->state(fn (SupportTicket $record): string => $record->status_label)
                    ->color(fn (SupportTicket $record): string => match ($record->status) {
                        'open' => 'warning',
                        'in_progress' => 'info',
                        'waiting_customer' => 'gray',
                        'resolved' => 'success',
                        'closed' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('assignedUser.name')->label('Assigné à')->placeholder('-'),
                TextColumn::make('created_at')->label('Créé le')->dateTime()->sortable(),
                TextColumn::make('updated_at')->label('Mis à jour')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('company')->relationship('company', 'name')->label('Société'),
                SelectFilter::make('status')->label('Statut')->options(SupportTicket::statuses()),
                SelectFilter::make('priority')->label('Priorité')->options(SupportTicket::priorities()),
                SelectFilter::make('category')->label('Catégorie')->options(SupportTicket::categories()),
                SelectFilter::make('assigned_to_user_id')->label('Assigné à')->relationship('assignedUser', 'name'),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('reply')
                    ->label('Répondre')
                    ->icon('heroicon-m-chat-bubble-left-right')
                    ->form([Textarea::make('message')->label('Réponse')->required()])
                    ->action(fn (SupportTicket $record, array $data) => app(SupportTicketService::class)->addReply($record, $data['message'], auth()->user())),
                Action::make('internalNote')
                    ->label('Note interne')
                    ->icon('heroicon-m-lock-closed')
                    ->form([Textarea::make('message')->label('Note interne')->required()])
                    ->action(fn (SupportTicket $record, array $data) => app(SupportTicketService::class)->addReply($record, $data['message'], auth()->user(), null, true)),
                Action::make('assign')
                    ->label('Assigner')
                    ->icon('heroicon-m-user-plus')
                    ->form([
                        Select::make('assigned_to_user_id')->label('Assigné à')->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))->searchable(),
                    ])
                    ->action(fn (SupportTicket $record, array $data) => app(SupportTicketService::class)->assign($record, User::query()->find($data['assigned_to_user_id'] ?? null))),
                Action::make('status')
                    ->label('Changer statut')
                    ->icon('heroicon-m-arrow-path')
                    ->form([Select::make('status')->label('Statut')->options(SupportTicket::statuses())->required()])
                    ->action(fn (SupportTicket $record, array $data) => app(SupportTicketService::class)->changeStatus($record, $data['status'])),
                Action::make('resolve')
                    ->label('Marquer comme résolu')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->action(fn (SupportTicket $record) => app(SupportTicketService::class)->changeStatus($record, 'resolved')),
                Action::make('close')
                    ->label('Fermer le ticket')
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (SupportTicket $record) => app(SupportTicketService::class)->changeStatus($record, 'closed')),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSupportTickets::route('/'),
            'create' => CreateSupportTicket::route('/create'),
            'view' => ViewSupportTicket::route('/{record}'),
            'edit' => EditSupportTicket::route('/{record}/edit'),
        ];
    }
}
