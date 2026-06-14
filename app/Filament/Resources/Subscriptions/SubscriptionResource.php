<?php

namespace App\Filament\Resources\Subscriptions;

use App\Filament\Concerns\ScopesResourcesToCompany;
use App\Filament\Resources\Subscriptions\Pages\CreateSubscription;
use App\Filament\Resources\Subscriptions\Pages\EditSubscription;
use App\Filament\Resources\Subscriptions\Pages\ListSubscriptions;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Audit\AuditLogger;
use App\Services\Saas\SubscriptionManagementService;
use Filament\Actions\Action;
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

class SubscriptionResource extends Resource
{
    use ScopesResourcesToCompany;

    protected static ?string $model = Subscription::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static string|\UnitEnum|null $navigationGroup = 'Abonnements';

    protected static ?string $navigationLabel = 'Abonnements';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('company_id')->label('Societe')->relationship('company', 'name')->searchable()->preload()->required(),
            Select::make('plan_id')->label('Plan')->relationship('plan', 'name')->searchable()->preload()->required(),
            Select::make('status')->label('Statut')->options(self::statuses())->required()->default('trialing'),
            Select::make('billing_cycle')->label('Cycle')->options(['monthly' => 'Mensuel', 'yearly' => 'Annuel'])->required()->default('monthly'),
            DatePicker::make('starts_at')->label('Debut')->required(),
            DatePicker::make('trial_ends_at')->label('Fin essai'),
            DatePicker::make('ends_at')->label('Fin'),
            DatePicker::make('current_period_start')->label('Debut periode'),
            DatePicker::make('current_period_end')->label('Fin periode'),
            TextInput::make('amount')->label('Montant')->numeric()->prefix('MAD')->required(),
            Textarea::make('notes')->label('Notes')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Societe')->searchable()->sortable(),
                TextColumn::make('plan.name')->label('Plan')->sortable(),
                TextColumn::make('status')->label('Statut')->badge()->formatStateUsing(fn (string $state): string => self::statuses()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'active', 'trialing' => 'success',
                        'past_due' => 'warning',
                        'cancelled', 'expired' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('billing_cycle')->label('Cycle')->formatStateUsing(fn (?string $state): string => $state === 'yearly' ? 'Annuel' : 'Mensuel'),
                TextColumn::make('current_period_start')->label('Debut periode')->date()->sortable(),
                TextColumn::make('current_period_end')->label('Fin periode')->date()->sortable(),
                TextColumn::make('ends_at')->label('Fin abonnement')->date()->sortable()->placeholder('-'),
                TextColumn::make('amount')->label('Montant')->money('MAD')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->label('Statut')->options(self::statuses()),
                SelectFilter::make('plan')->relationship('plan', 'name')->label('Plan'),
                SelectFilter::make('company')->relationship('company', 'name')->label('Societe'),
            ])
            ->recordActions([
                Action::make('changePlan')
                    ->label('Changer de plan')
                    ->icon('heroicon-m-arrow-path')
                    ->form([
                        Select::make('plan_id')->label('Nouveau plan')->options(fn () => Plan::query()->where('is_active', true)->orderBy('sort_order')->pluck('name', 'id'))->required(),
                    ])
                    ->action(function (Subscription $record, array $data): void {
                        app(SubscriptionManagementService::class)->changePlan($record->company, Plan::query()->findOrFail((int) $data['plan_id']));
                    }),
                Action::make('startTrial')
                    ->label('Demarrer essai')
                    ->icon('heroicon-m-sparkles')
                    ->action(function (Subscription $record): void {
                        app(SubscriptionManagementService::class)->startTrial($record->company, $record->plan);
                    }),
                Action::make('activate')
                    ->label('Activer')
                    ->icon('heroicon-m-check-circle')
                    ->action(function (Subscription $record): void {
                        $record->forceFill([
                            'status' => 'active',
                            'amount' => $record->billing_cycle === 'yearly'
                                ? ($record->plan?->yearly_price ?? ($record->plan?->monthly_price ?? 0) * 12)
                                : $record->plan?->monthly_price,
                        ])->save();
                        app(AuditLogger::class)->log('subscription_activated', $record);
                    }),
                Action::make('cancel')
                    ->label('Annuler')
                    ->color('danger')
                    ->icon('heroicon-m-x-circle')
                    ->requiresConfirmation()
                    ->action(function (Subscription $record): void {
                        app(SubscriptionManagementService::class)->cancelSubscription($record->company);
                    }),
                Action::make('renew')
                    ->label('Renouveler')
                    ->icon('heroicon-m-calendar-days')
                    ->action(function (Subscription $record): void {
                        app(SubscriptionManagementService::class)->renewSubscription($record->company);
                    }),
                Action::make('generateInvoice')
                    ->label('Generer facture')
                    ->icon('heroicon-m-document-text')
                    ->action(function (Subscription $record): void {
                        app(SubscriptionManagementService::class)->generateInvoice($record);
                    }),
                EditAction::make(),
            ])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubscriptions::route('/'),
            'create' => CreateSubscription::route('/create'),
            'edit' => EditSubscription::route('/{record}/edit'),
        ];
    }

    private static function statuses(): array
    {
        return [
            'trialing' => 'Essai',
            'active' => 'Actif',
            'past_due' => 'Paiement en retard',
            'cancelled' => 'Annule',
            'expired' => 'Expire',
        ];
    }
}
