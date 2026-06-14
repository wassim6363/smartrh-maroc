<?php

namespace App\Filament\Resources\Employees\RelationManagers;

use App\Filament\Resources\EmployeeContracts\EmployeeContractResource;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Services\Documents\ContractGeneratorService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ContractsRelationManager extends RelationManager
{
    protected static string $relationship = 'employeeContracts';

    protected static ?string $title = 'Contrats employés';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->label('Référence')->searchable()->sortable(),
                TextColumn::make('type')->label('Type de contrat')->badge(),
                TextColumn::make('start_date')->label('Date début')->date('d/m/Y')->sortable(),
                TextColumn::make('end_date')->label('Date fin')->date('d/m/Y')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Générer un contrat')
                    ->schema([
                        Select::make('contract_template_id')
                            ->label('Modèle de contrat')
                            ->options(fn () => ContractTemplate::query()
                                ->where(function ($query) {
                                    $query->where('company_id', $this->getOwnerRecord()->company_id)->orWhereNull('company_id');
                                })
                                ->where('is_active', true)
                                ->pluck('title', 'id'))
                            ->required(),
                        Select::make('type')->label('Type de contrat')->options(EmployeeContractResource::typeOptions())->required(),
                        DatePicker::make('start_date')->label('Date début')->default(now())->required(),
                        DatePicker::make('end_date')->label('Date fin'),
                        TextInput::make('salary')->label('Salaire')->numeric()->prefix('MAD'),
                        TextInput::make('job_title')->label('Poste'),
                        TextInput::make('city')->label('Ville'),
                    ])
                    ->using(function (array $data): Model {
                        /** @var Employee $employee */
                        $employee = $this->getOwnerRecord();

                        return app(ContractGeneratorService::class)->generate([
                            ...$data,
                            'company_id' => $employee->company_id,
                            'employee_id' => $employee->id,
                        ]);
                    })
                    ->successNotificationTitle('Contrat généré'),
            ])
            ->recordActions([
                ViewAction::make()->url(fn (EmployeeContract $record): string => EmployeeContractResource::getUrl('view', ['record' => $record])),
                Action::make('downloadPdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (EmployeeContract $record): string => route('contracts.download', $record))
                    ->openUrlInNewTab(),
                Action::make('markSigned')
                    ->label('Marquer comme signé')
                    ->icon('heroicon-o-check-circle')
                    ->action(function (EmployeeContract $record): void {
                        $record->update(['status' => 'signed', 'signed_at' => now()]);
                        Notification::make()->title('Contrat marqué comme signé')->success()->send();
                    }),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
