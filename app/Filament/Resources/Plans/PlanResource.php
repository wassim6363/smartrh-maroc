<?php

namespace App\Filament\Resources\Plans;

use App\Filament\Resources\Plans\Pages\CreatePlan;
use App\Filament\Resources\Plans\Pages\EditPlan;
use App\Filament\Resources\Plans\Pages\ListPlans;
use App\Models\Plan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Plans';

    protected static ?string $modelLabel = 'plan';

    protected static ?string $pluralModelLabel = 'plans';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nom')->required()->maxLength(255),
            TextInput::make('slug')->label('Slug')->required()->unique(ignoreRecord: true)->maxLength(255),
            Textarea::make('description')->label('Description')->columnSpanFull(),
            TextInput::make('monthly_price')->label('Prix mensuel')->numeric()->prefix('MAD')->required(),
            TextInput::make('yearly_price')->label('Prix annuel')->numeric()->prefix('MAD'),
            TextInput::make('max_companies')->label('Societes max')->numeric(),
            TextInput::make('max_employees')->label('Salaries max')->numeric(),
            TextInput::make('max_payslips_per_month')->label('Bulletins / mois')->numeric(),
            TextInput::make('max_contracts_per_month')->label('Contrats / mois')->numeric(),
            TextInput::make('company_limit')->label('Limite societes legacy')->numeric(),
            TextInput::make('employee_limit')->label('Limite salaries legacy')->numeric(),
            Toggle::make('employee_portal_enabled')->label('Portail salarie')->default(true),
            Toggle::make('document_requests_enabled')->label('Demandes documents')->default(false),
            Toggle::make('audit_logs_enabled')->label('Audit logs')->default(true),
            Toggle::make('api_access_enabled')->label('Acces API')->default(false),
            Toggle::make('is_active')->label('Actif')->default(true),
            TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
            KeyValue::make('features')->label('Fonctionnalites')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('#')->sortable(),
                TextColumn::make('name')->label('Plan')->searchable()->sortable(),
                TextColumn::make('slug')->label('Slug')->searchable()->toggleable(),
                TextColumn::make('monthly_price')->label('Mensuel')->money('MAD')->sortable(),
                TextColumn::make('yearly_price')->label('Annuel')->money('MAD')->sortable()->placeholder('Sur devis'),
                TextColumn::make('max_employees')->label('Salaries')->sortable()->placeholder('Illimite'),
                TextColumn::make('max_payslips_per_month')->label('Bulletins/mois')->sortable()->placeholder('Illimite'),
                TextColumn::make('max_contracts_per_month')->label('Contrats/mois')->sortable()->placeholder('Illimite'),
                IconColumn::make('employee_portal_enabled')->label('Portail')->boolean(),
                IconColumn::make('document_requests_enabled')->label('Documents')->boolean(),
                IconColumn::make('api_access_enabled')->label('API')->boolean(),
                IconColumn::make('is_active')->label('Actif')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit' => EditPlan::route('/{record}/edit'),
        ];
    }
}
