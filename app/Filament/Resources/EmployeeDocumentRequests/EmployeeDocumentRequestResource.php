<?php

namespace App\Filament\Resources\EmployeeDocumentRequests;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\EmployeeDocumentRequests\Pages\ListEmployeeDocumentRequests;
use App\Models\EmployeeDocumentRequest;
use App\Models\GeneratedDocument;
use App\Services\Documents\EmployeeDocumentRequestWorkflow;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeDocumentRequestResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = EmployeeDocumentRequest::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Documents';

    protected static ?string $navigationLabel = 'Demandes de documents';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Société')->relationship('company', 'name')->required(),
            Select::make('employee_id')->label('Salarié')->relationship('employee', 'employee_number')->required(),
            Select::make('type')->label('Type')->options(self::typeOptions())->required(),
            Select::make('status')->label('Statut')->options(self::statusOptions())->required(),
            Textarea::make('message')->label('Message')->columnSpanFull(),
            Textarea::make('response_message')->label('Réponse RH')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Société')->sortable()->searchable(),
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('type')->label('Type')->badge()->formatStateUsing(fn (string $state): string => self::typeOptions()[$state] ?? $state),
                TextColumn::make('title')->label('Titre')->searchable(),
                TextColumn::make('status')->label('Statut')->badge()->color(fn (string $state): string => match ($state) {
                    'approved' => 'info',
                    'completed' => 'success',
                    'rejected' => 'danger',
                    default => 'warning',
                }),
                TextColumn::make('requested_at')->label('Demandée le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('processed_at')->label('Traitée le')->dateTime('d/m/Y H:i')->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société')->searchable()->preload(),
                SelectFilter::make('employee_id')->relationship('employee', 'employee_number')->label('Salarié')->searchable()->preload(),
                SelectFilter::make('type')->label('Type')->options(self::typeOptions()),
                SelectFilter::make('status')->label('Statut')->options(self::statusOptions()),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Approuver')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (EmployeeDocumentRequest $record): bool => $record->status === 'pending')
                    ->form([Textarea::make('response_message')->label('Réponse RH')])
                    ->action(function (EmployeeDocumentRequest $record, array $data, EmployeeDocumentRequestWorkflow $workflow): void {
                        $workflow->approve($record, $data['response_message'] ?? null);
                    }),
                Action::make('reject')
                    ->label('Rejeter')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (EmployeeDocumentRequest $record): bool => in_array($record->status, ['pending', 'approved'], true))
                    ->form([Textarea::make('response_message')->label('Motif')->required()])
                    ->action(function (EmployeeDocumentRequest $record, array $data, EmployeeDocumentRequestWorkflow $workflow): void {
                        $workflow->reject($record, $data['response_message']);
                    }),
                Action::make('complete')
                    ->label('Marquer terminée')
                    ->icon('heroicon-o-document-check')
                    ->visible(fn (EmployeeDocumentRequest $record): bool => in_array($record->status, ['pending', 'approved'], true))
                    ->form([
                        Select::make('generated_document_id')
                            ->label('Document généré')
                            ->options(fn (EmployeeDocumentRequest $record): array => GeneratedDocument::query()
                                ->where('company_id', $record->company_id)
                                ->where('employee_id', $record->employee_id)
                                ->latest()
                                ->pluck('title', 'id')
                                ->all())
                            ->searchable(),
                        Textarea::make('response_message')->label('Réponse RH'),
                    ])
                    ->action(function (EmployeeDocumentRequest $record, array $data, EmployeeDocumentRequestWorkflow $workflow): void {
                        $workflow->complete($record, $data['generated_document_id'] ?? null, $data['response_message'] ?? null);
                    }),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('requested_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListEmployeeDocumentRequests::route('/')];
    }

    public static function typeOptions(): array
    {
        return [
            'ATTESTATION_TRAVAIL' => 'Attestation de travail',
            'CERTIFICAT_TRAVAIL' => 'Certificat de travail',
            'BULLETIN_PAIE' => 'Bulletin de paie',
            'AUTRE' => 'Autre',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'completed' => 'Terminée',
        ];
    }
}
