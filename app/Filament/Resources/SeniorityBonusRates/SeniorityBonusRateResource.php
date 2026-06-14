<?php

namespace App\Filament\Resources\SeniorityBonusRates;

use App\Filament\Resources\SeniorityBonusRates\Pages\CreateSeniorityBonusRate;
use App\Filament\Resources\SeniorityBonusRates\Pages\EditSeniorityBonusRate;
use App\Filament\Resources\SeniorityBonusRates\Pages\ListSeniorityBonusRates;
use App\Models\SeniorityBonusRate;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SeniorityBonusRateResource extends Resource
{
    protected static ?string $model = SeniorityBonusRate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static ?string $navigationLabel = "Prime d’ancienneté";

    protected static string|\UnitEnum|null $navigationGroup = 'Paramètres Paie';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('min_years')->label('Années min.')->numeric()->required(),
            TextInput::make('max_years')->label('Années max.')->numeric(),
            TextInput::make('rate')->label('Taux')->numeric()->required()->step('0.0001'),
            DatePicker::make('effective_from')->label('Date effet')->required(),
            DatePicker::make('effective_to')->label('Fin effet'),
            Toggle::make('active')->label('Actif')->default(true),
            Textarea::make('notes')->label('Notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('min_years')->label('Min')->sortable(),
                TextColumn::make('max_years')->label('Max')->placeholder('+')->sortable(),
                TextColumn::make('rate')->label('Taux')->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2) . ' %'),
                IconColumn::make('active')->label('Actif')->boolean(),
                TextColumn::make('effective_from')->label('Du')->date('d/m/Y'),
                TextColumn::make('effective_to')->label('Au')->date('d/m/Y')->placeholder('-'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSeniorityBonusRates::route('/'),
            'create' => CreateSeniorityBonusRate::route('/create'),
            'edit' => EditSeniorityBonusRate::route('/{record}/edit'),
        ];
    }
}
