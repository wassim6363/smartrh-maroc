<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Invoice;
use App\Services\Saas\InvoicePaymentService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|\UnitEnum|null $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Factures';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Societe')->relationship('company', 'name')->searchable()->preload()->required(),
            Select::make('subscription_id')->label('Abonnement')->relationship('subscription', 'id')->searchable()->preload(),
            TextInput::make('invoice_number')->label('Numero')->required(),
            TextInput::make('amount')->label('Montant')->numeric()->prefix('MAD')->required(),
            TextInput::make('currency')->label('Devise')->default('MAD')->maxLength(3)->required(),
            Select::make('status')->label('Statut')->options(self::statuses())->required()->default('draft'),
            DatePicker::make('billing_period_start')->label('Debut periode'),
            DatePicker::make('billing_period_end')->label('Fin periode'),
            DatePicker::make('issued_at')->label('Emise le'),
            DatePicker::make('due_at')->label('Echeance'),
            DatePicker::make('paid_at')->label('Payee le'),
            TextInput::make('pdf_path')->label('PDF')->disabled()->dehydrated(false)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Societe')->searchable()->sortable(),
                TextColumn::make('invoice_number')->label('Numero')->searchable()->sortable(),
                TextColumn::make('subscription.plan.name')->label('Plan')->sortable(),
                TextColumn::make('amount')->label('Montant')->money('MAD')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => self::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'pending', 'draft' => 'warning',
                        'failed', 'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('billing_period_start')->label('Debut')->date()->sortable(),
                TextColumn::make('billing_period_end')->label('Fin')->date()->sortable(),
                TextColumn::make('issued_at')->label('Emission')->date()->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(self::statuses()),
                SelectFilter::make('company')->relationship('company', 'name')->label('Societe'),
            ])
            ->recordActions([
                Action::make('markPaid')
                    ->label('Marquer payée')
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (Invoice $record): bool => $record->status !== 'paid')
                    ->requiresConfirmation()
                    ->action(fn (Invoice $record, InvoicePaymentService $service): mixed => $service->markPaid($record)),
                Action::make('downloadPdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Invoice $record): string => route('invoices.download', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }

    private static function statuses(): array
    {
        return [
            'draft' => 'Brouillon',
            'pending' => 'En attente',
            'paid' => 'Payee',
            'failed' => 'Echec',
            'cancelled' => 'Annulee',
        ];
    }
}
