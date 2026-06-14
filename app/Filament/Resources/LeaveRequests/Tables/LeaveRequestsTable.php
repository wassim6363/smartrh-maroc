<?php

namespace App\Filament\Resources\LeaveRequests\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use App\Models\LeaveRequest;
use App\Services\Audit\AuditLogger;
use Filament\Notifications\Notification;
use App\Notifications\SimpleFrenchNotification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LeaveRequestsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->sortable()->searchable(),
                TextColumn::make('employee.full_name')->label('Employee')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('leaveType.name')->label('Type')->sortable(),
                TextColumn::make('starts_at')->date()->sortable(),
                TextColumn::make('ends_at')->date()->sortable(),
                TextColumn::make('days')->sortable(),
                TextColumn::make('status')->badge()->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),
                SelectFilter::make('status')->options(['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'cancelled' => 'Cancelled']),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Exporter CSV')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(route('exports.leave-requests')),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (LeaveRequest $record): bool => $record->status === 'pending')
                    ->action(function (LeaveRequest $record, AuditLogger $audit): void {
                        $record->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
                        $audit->log('leave_approved', $record, [], ['status' => 'approved']);
                        $record->employee?->user?->notify(new SimpleFrenchNotification('Congé accepté', 'Votre demande de congé a été acceptée.'));
                        Notification::make()->title('Congé approuvé')->success()->send();
                    }),
                Action::make('reject')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->visible(fn (LeaveRequest $record): bool => $record->status === 'pending')
                    ->action(function (LeaveRequest $record, AuditLogger $audit): void {
                        $record->update(['status' => 'rejected']);
                        $audit->log('leave_refused', $record, [], ['status' => 'rejected']);
                        $record->employee?->user?->notify(new SimpleFrenchNotification('Congé refusé', 'Votre demande de congé a été refusée.'));
                        Notification::make()->title('Congé refusé')->warning()->send();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucune demande de congé')
            ->emptyStateDescription('Les demandes salariés apparaîtront ici.')
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
