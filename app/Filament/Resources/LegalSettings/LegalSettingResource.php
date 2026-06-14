<?php

namespace App\Filament\Resources\LegalSettings;

use App\Filament\Resources\LegalSettings\Pages\CreateLegalSetting;
use App\Filament\Resources\LegalSettings\Pages\EditLegalSetting;
use App\Filament\Resources\LegalSettings\Pages\ListLegalSettings;
use App\Models\LegalSetting;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LegalSettingResource extends Resource
{
    protected static ?string $model = LegalSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedScale;

    protected static ?string $navigationLabel = 'Paramètres légaux';

    protected static string|\UnitEnum|null $navigationGroup = 'Paramètres Paie';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Paramètres configurables')
                ->description('Ces paramètres sont configurables. Vérifiez les taux officiels avec un expert-comptable marocain.')
                ->columns(2)
                ->schema([
                    TextInput::make('label')->label('Libellé')->required()->maxLength(255),
                    TextInput::make('year')->label('Année')->numeric(),
                    TextInput::make('cnss_ceiling')->label('Plafond CNSS')->numeric()->prefix('MAD'),
                    TextInput::make('cnss_employee_rate')->label('Taux CNSS salarie')->numeric()->step('0.0001'),
                    TextInput::make('cnss_short_term_employee_rate')->label('CNSS court terme')->numeric()->step('0.0001'),
                    TextInput::make('cnss_long_term_employee_rate')->label('CNSS long terme')->numeric()->step('0.0001'),
                    TextInput::make('amo_employee_rate')->label('Taux AMO salarie')->numeric()->step('0.0001'),
                    TextInput::make('professional_expenses_rate')->label('Taux frais professionnels')->numeric()->step('0.0001'),
                    TextInput::make('professional_expenses_ceiling')->label('Plafond frais professionnels')->numeric()->prefix('MAD'),
                    Select::make('professional_expenses_base')
                        ->label('Base de calcul des frais professionnels')
                        ->options([
                            'taxable_gross' => 'Brut imposable',
                            'taxable_after_contributions' => 'Salaire après cotisations',
                        ])
                        ->default('taxable_after_contributions')
                        ->helperText('Détermine si les frais professionnels sont calculés sur le brut imposable ou sur le salaire après cotisations.'),
                    DatePicker::make('effective_from')->label('Date effet')->required(),
                    DatePicker::make('effective_to')->label('Fin effet'),
                    Toggle::make('active')->label('Actif')->default(true),
                    Textarea::make('notes')->label('Notes')->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('label')->label('Libellé')->searchable()->sortable(),
                TextColumn::make('year')->label('Année')->sortable(),
                TextColumn::make('cnss_ceiling')->label('Plafond CNSS')->money('MAD')->sortable(),
                TextColumn::make('cnss_employee_rate')->label('CNSS')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state * 100, 2) . ' %' : '-'),
                TextColumn::make('amo_employee_rate')->label('AMO')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state * 100, 2) . ' %' : '-'),
                TextColumn::make('professional_expenses_rate')->label('FP')->formatStateUsing(fn ($state) => $state !== null ? number_format((float) $state * 100, 2) . ' %' : '-'),
                TextColumn::make('professional_expenses_base')->label('Base FP')->formatStateUsing(fn ($state) => match ($state) {
                    'taxable_gross' => 'Brut imposable',
                    'taxable_after_contributions' => 'Salaire après cotisations',
                    default => '-',
                }),
                IconColumn::make('active')->label('Actif')->boolean(),
                TextColumn::make('effective_from')->label('Du')->date('d/m/Y')->sortable(),
                TextColumn::make('effective_to')->label('Au')->date('d/m/Y')->placeholder('-'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLegalSettings::route('/'),
            'create' => CreateLegalSetting::route('/create'),
            'edit' => EditLegalSetting::route('/{record}/edit'),
        ];
    }
}
