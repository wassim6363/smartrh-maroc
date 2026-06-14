<?php

namespace App\Filament\Resources\IrBrackets;

use App\Filament\Resources\IrBrackets\Pages\CreateIrBracket;
use App\Filament\Resources\IrBrackets\Pages\EditIrBracket;
use App\Filament\Resources\IrBrackets\Pages\ListIrBrackets;
use App\Models\IrBracket;
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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class IrBracketResource extends Resource
{
    protected static ?string $model = IrBracket::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static ?string $navigationLabel = 'Barème IR';

    protected static string|\UnitEnum|null $navigationGroup = 'Paramètres Paie';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            Select::make('company_id')->label('Société')->relationship('company', 'name')->searchable()->preload(),
            TextInput::make('year')->label('Année')->numeric(),
            TextInput::make('min_amount')->label('Minimum')->numeric()->required()->prefix('MAD'),
            TextInput::make('max_amount')->label('Maximum')->numeric()->prefix('MAD'),
            TextInput::make('rate')->label('Taux')->numeric()->required()->step('0.0001'),
            TextInput::make('deduction')->label('Déduction')->numeric()->default(0)->prefix('MAD'),
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
                TextColumn::make('company.name')->label('Société')->placeholder('Global')->sortable(),
                TextColumn::make('year')->label('Année')->sortable(),
                TextColumn::make('min_amount')->label('Minimum')->money('MAD')->sortable(),
                TextColumn::make('max_amount')->label('Maximum')->money('MAD')->placeholder('-')->sortable(),
                TextColumn::make('rate')->label('Taux')->formatStateUsing(fn ($state) => number_format((float) $state * 100, 2) . ' %'),
                TextColumn::make('deduction')->label('Déduction')->money('MAD'),
                IconColumn::make('active')->label('Actif')->boolean(),
                TextColumn::make('effective_from')->label('Du')->date('d/m/Y'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIrBrackets::route('/'),
            'create' => CreateIrBracket::route('/create'),
            'edit' => EditIrBracket::route('/{record}/edit'),
        ];
    }
}
