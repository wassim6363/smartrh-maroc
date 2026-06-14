<?php

namespace App\Filament\Resources\EmployeeContracts\Tables;

use App\Filament\Resources\EmployeeContracts\Actions\SignedContractUploadAction;
use App\Filament\Resources\EmployeeContracts\EmployeeContractResource;
use App\Models\EmployeeContract;
use App\Services\Documents\ContractPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Société')->sortable()->searchable(),
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('reference')->label('Référence')->searchable()->sortable(),
                TextColumn::make('type')->label('Type de contrat')->badge()->formatStateUsing(fn (string $state): string => EmployeeContractResource::typeOptions()[$state] ?? $state),
                TextColumn::make('start_date')->label('Date début')->date('d/m/Y')->sortable(),
                TextColumn::make('end_date')->label('Date fin')->date('d/m/Y')->sortable(),
                TextColumn::make('salary')->label('Salaire')->money('MAD')->sortable(),
                TextColumn::make('job_title')->label('Poste')->toggleable(),
                TextColumn::make('status')->label('Statut')->badge()->color(fn (string $state): string => match ($state) {
                    'signed' => 'success',
                    'generated' => 'info',
                    'archived' => 'gray',
                    'cancelled' => 'danger',
                    default => 'warning',
                }),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société')->searchable()->preload(),
                SelectFilter::make('employee_id')->relationship('employee', 'employee_number')->label('Salarié')->searchable()->preload(),
                SelectFilter::make('type')->label('Type de contrat')->options(EmployeeContractResource::typeOptions()),
                SelectFilter::make('status')->label('Statut')->options([
                    'draft' => 'Brouillon',
                    'generated' => 'Généré',
                    'signed' => 'Signé',
                    'archived' => 'Archivé',
                    'cancelled' => 'Annulé',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadPdf')->label('Télécharger PDF')->icon('heroicon-o-document-arrow-down')->url(fn (EmployeeContract $record): string => route('contracts.download', $record))->openUrlInNewTab(),
                Action::make('regeneratePdf')
                    ->label('Régénérer PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (EmployeeContract $record, ContractPdfService $service): void {
                        $service->generate($record);
                        Notification::make()->title('PDF régénéré')->success()->send();
                    }),
                Action::make('markSigned')
                    ->label('Marquer comme signé')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (EmployeeContract $record): bool => $record->status !== 'signed')
                    ->action(function (EmployeeContract $record): void {
                        $record->update(['status' => 'signed', 'signed_at' => now()]);
                        Notification::make()->title('Contrat marqué comme signé')->success()->send();
                    }),
                SignedContractUploadAction::make(),
                Action::make('archive')
                    ->label('Archiver')
                    ->icon('heroicon-o-archive-box')
                    ->visible(fn (EmployeeContract $record): bool => $record->status !== 'archived')
                    ->action(function (EmployeeContract $record): void {
                        $record->update(['status' => 'archived']);
                        Notification::make()->title('Contrat archivé')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }
}
