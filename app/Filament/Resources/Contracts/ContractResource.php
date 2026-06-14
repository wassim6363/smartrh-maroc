<?php

namespace App\Filament\Resources\Contracts;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\Contracts\Pages\CreateContract;
use App\Filament\Resources\Contracts\Pages\EditContract;
use App\Filament\Resources\Contracts\Pages\ListContracts;
use App\Models\Contract;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ContractResource extends Resource
{
    use ScopesResourcesToCompany;
    protected static ?string $model = Contract::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;
    protected static string|\UnitEnum|null $navigationGroup = 'Documents';
    protected static ?string $navigationLabel = 'Contrats';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->relationship('company', 'name')->searchable()->preload()->required(),
            Select::make('employee_id')->relationship('employee', 'employee_number')->searchable()->preload()->required(),
            Select::make('contract_template_id')->relationship('template', 'name')->searchable()->preload(),
            TextInput::make('contract_number')->label('Numéro')->required(),
            Select::make('type')->options(['cdi' => 'CDI', 'cdd' => 'CDD', 'stage' => 'Stage', 'anapec' => 'ANAPEC', 'freelance' => 'Freelance'])->required(),
            DatePicker::make('start_date')->label('Début')->required(),
            DatePicker::make('end_date')->label('Fin'),
            TextInput::make('salary')->label('Salaire')->numeric()->prefix('MAD')->required(),
            Select::make('status')->options(['draft' => 'Brouillon', 'signed' => 'Signé', 'expired' => 'Expiré', 'cancelled' => 'Annulé'])->required(),
            Textarea::make('content')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('company.name')->label('Société')->searchable()->sortable(),
            TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
            TextColumn::make('contract_number')->label('Numéro')->searchable(),
            TextColumn::make('type')->badge(),
            TextColumn::make('start_date')->date()->sortable(),
            TextColumn::make('end_date')->date()->sortable(),
            TextColumn::make('status')->badge()->color(fn (string $state): string => match ($state) { 'signed' => 'success', 'expired' => 'warning', 'cancelled' => 'danger', default => 'gray' }),
        ])->filters([
            SelectFilter::make('company_id')->relationship('company', 'name')->label('Société'),
            SelectFilter::make('status')->options(['draft' => 'Brouillon', 'signed' => 'Signé', 'expired' => 'Expiré', 'cancelled' => 'Annulé']),
        ])->recordActions([EditAction::make()])->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListContracts::route('/'), 'create' => CreateContract::route('/create'), 'edit' => EditContract::route('/{record}/edit')];
    }
}
