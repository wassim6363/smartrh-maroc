<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Paiements';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Societe')->relationship('company', 'name')->searchable()->preload()->required(),
            Select::make('invoice_id')->label('Facture')->relationship('invoice', 'invoice_number')->searchable()->preload(),
            TextInput::make('amount')->label('Montant')->numeric()->prefix('MAD')->required(),
            TextInput::make('currency')->label('Devise')->default('MAD')->maxLength(3)->required(),
            TextInput::make('provider')->label('Fournisseur'),
            TextInput::make('provider_reference')->label('Reference fournisseur'),
            Select::make('status')->label('Statut')->options(self::statuses())->default('pending')->required(),
            DateTimePicker::make('paid_at')->label('Paye le'),
            KeyValue::make('metadata')->label('Metadonnees')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Societe')->searchable()->sortable(),
                TextColumn::make('invoice.invoice_number')->label('Facture')->searchable()->sortable(),
                TextColumn::make('amount')->label('Montant')->money('MAD')->sortable(),
                TextColumn::make('provider')->label('Fournisseur')->placeholder('Manuel'),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => self::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'succeeded' => 'success',
                        'pending' => 'warning',
                        'failed', 'refunded' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')->label('Paye le')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(self::statuses()),
                SelectFilter::make('company')->relationship('company', 'name')->label('Societe'),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }

    private static function statuses(): array
    {
        return [
            'pending' => 'En attente',
            'succeeded' => 'Reussi',
            'failed' => 'Echoue',
            'refunded' => 'Rembourse',
        ];
    }
}
