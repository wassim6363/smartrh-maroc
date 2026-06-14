<?php

namespace App\Filament\Resources\DemoRequests;

use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\User;
use App\Notifications\DemoTenantCreatedNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Saas\SubscriptionManagementService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\SelectAction;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DemoRequestResource extends Resource
{
    protected static ?string $model = DemoRequest::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;
    protected static string|\UnitEnum|null $navigationGroup = 'Support';
    protected static ?string $navigationLabel = 'Demandes de démo';

    public static function form(Schema $schema): Schema { return $schema; }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('full_name')->label('Nom')->searchable(),
            TextColumn::make('company_name')->label('Société')->searchable(),
            TextColumn::make('email')->searchable(),
            TextColumn::make('phone')->label('Téléphone')->searchable(),
            TextColumn::make('company_size')->label('Taille')->toggleable(),
            TextColumn::make('target_plan')->label('Pack')->badge()->toggleable(),
            TextColumn::make('source')->label('Source')->badge()->toggleable(),
            TextColumn::make('status')->label('Statut')->badge()->sortable()->formatStateUsing(fn ($state) => match ($state) {
                'new' => 'Nouveau',
                'contacted' => 'Contacté',
                'demo_scheduled' => 'Démo planifiée',
                'converted' => 'Converti',
                'lost' => 'Perdu',
                default => $state,
            })->color(fn ($state) => match ($state) {
                'new' => 'info',
                'contacted' => 'warning',
                'demo_scheduled' => 'warning',
                'converted' => 'success',
                'lost' => 'danger',
                default => 'gray',
            }),
            TextColumn::make('assignedTo.name')->label('Assigné à')->toggleable(),
            TextColumn::make('convertedCompany.name')->label('Société convertie')->toggleable(),
            TextColumn::make('created_at')->label('Date')->dateTime()->sortable(),
        ])->filters([
            SelectFilter::make('status')
                ->label('Statut')
                ->options([
                    'new' => 'Nouveau',
                    'contacted' => 'Contacté',
                    'demo_scheduled' => 'Démo planifiée',
                    'converted' => 'Converti',
                    'lost' => 'Perdu',
                ]),
            SelectFilter::make('target_plan')
                ->label('Pack')
                ->options(['Starter' => 'Starter', 'Business' => 'Business', 'Cabinet' => 'Cabinet', 'Enterprise' => 'Enterprise']),
            SelectFilter::make('source')
                ->label('Source')
                ->options(['request-demo' => 'Demande directe', 'demo' => 'Démo', 'landing' => 'Page d\'accueil']),
        ])->recordActions([
            Action::make('assign')
                ->label('Assigner à')
                ->icon(Heroicon::OutlinedUserPlus)
                ->form([
                    \Filament\Forms\Components\Select::make('user_id')
                        ->label('Utilisateur')
                        ->options(User::query()->whereHas('roles', fn ($q) => $q->whereIn('name', ['Super Admin', 'Company Owner']))->pluck('name', 'id'))
                        ->required(),
                ])
                ->action(function (array $data, DemoRequest $record) {
                    $record->update(['assigned_to_user_id' => $data['user_id']]);
                    Notification::make()->title('Demande assignée')->success()->send();
                }),
            Action::make('mark_contacted')
                ->label('Marquer comme contacté')
                ->icon(Heroicon::OutlinedPhone)
                ->color('warning')
                ->requiresConfirmation()
                ->action(fn (DemoRequest $record) => $record->update(['status' => 'contacted', 'contacted_at' => now()])),
            Action::make('schedule_demo')
                ->label('Planifier une démo')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\DateTimePicker::make('scheduled_at')
                        ->label('Date de démo')
                        ->required(),
                ])
                ->action(function (array $data, DemoRequest $record) {
                    $record->update(['status' => 'demo_scheduled', 'contacted_at' => now()]);
                    Notification::make()->title('Démo planifiée')->success()->send();
                }),
            Action::make('convert')
                ->label('Convertir en société')
                ->icon(Heroicon::OutlinedBuildingOffice2)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Convertir cette demande en société')
                ->modalDescription('Créer une société, un abonnement d\'essai Business et un utilisateur administrateur.')
                ->action(function (DemoRequest $record) {
                    try {
                        DB::transaction(function () use ($record) {
                            $company = Company::query()->create([
                                'name' => $record->company_name,
                                'email' => $record->email,
                                'phone' => $record->phone,
                            ]);

                            $businessPlan = Plan::query()->where('slug', 'business')->firstOrFail();

                            app(SubscriptionManagementService::class)->startTrial($company, $businessPlan);

                            $user = User::query()->create([
                                'name' => $record->full_name,
                                'email' => $record->email,
                                'password' => Hash::make('password'),
                                'company_id' => $company->id,
                            ]);
                            if (method_exists($user, 'assignRole')) {
                                $user->assignRole('Company Owner');
                            }

                            $record->update([
                                'converted_company_id' => $company->id,
                                'status' => 'converted',
                                'converted_at' => now(),
                            ]);

                            try {
                                $user->notify(new DemoTenantCreatedNotification($company, $user, 'password'));
                            } catch (\Throwable $e) {
                                Log::warning('Demo tenant notification failed: ' . $e->getMessage());
                            }

                            try {
                                app(AuditLogger::class)->log('demo_request_converted', $record, [], [
                                    'company_id' => $company->id,
                                    'user_id' => $user->id,
                                    'plan' => 'Business',
                                ]);
                            } catch (\Throwable $e) {
                                Log::warning('Demo conversion audit log failed: ' . $e->getMessage());
                            }
                        });
                        Notification::make()->title('Demande convertie avec succès')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Erreur: ' . $e->getMessage())->danger()->send();
                    }
                }),
            Action::make('mark_lost')
                ->label('Marquer comme perdu')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->action(fn (DemoRequest $record) => $record->update(['status' => 'lost'])),
        ])->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return ['index' => ListDemoRequests::route('/')];
    }
}