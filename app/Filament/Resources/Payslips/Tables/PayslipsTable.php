<?php

namespace App\Filament\Resources\Payslips\Tables;

use App\Models\Payslip;
use App\Notifications\SimpleFrenchNotification;
use App\Services\Audit\AuditLogger;
use App\Services\Payroll\PayrollCalculator;
use App\Services\Payroll\PayslipPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class PayslipsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->label('Société')->sortable()->searchable(),
                TextColumn::make('reference')->label('Référence')->sortable()->searchable(),
                TextColumn::make('payrollPeriod.name')->label('Période de paie')->sortable()->searchable(),
                TextColumn::make('employee.full_name')->label('Salarié')->searchable(['employees.first_name', 'employees.last_name']),
                TextColumn::make('gross_total')->label('Salaire brut')->money('MAD')->sortable(),
                TextColumn::make('taxable_gross')->label('Brut imposable')->money('MAD')->sortable()->toggleable(),
                TextColumn::make('cnss_employee')->label('CNSS salarié')->money('MAD')->sortable(),
                TextColumn::make('amo_employee')->label('AMO salarié')->money('MAD')->sortable(),
                TextColumn::make('ir_net')->label('IR')->money('MAD')->sortable(),
                TextColumn::make('net_to_pay')->label('Net à payer')->money('MAD')->sortable()->weight('bold'),
                TextColumn::make('generated_at')->label('Généré le')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'validated' => 'success',
                        'sent' => 'info',
                        'generated' => 'warning',
                        'closed' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Brouillon',
                        'generated' => 'Généré',
                        'validated' => 'Validé',
                        'sent' => 'Envoyé',
                        'closed' => 'Clôturé',
                        'cancelled' => 'Annulé',
                        default => ucfirst($state),
                    })
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Société')->searchable()->preload(),
                SelectFilter::make('payroll_period_id')->relationship('payrollPeriod', 'name')->label('Période de paie')->searchable()->preload(),
                SelectFilter::make('employee_id')->relationship('employee', 'employee_number')->label('Salarié')->searchable()->preload(),
                SelectFilter::make('status')->label('Statut')->options([
                    'draft' => 'Brouillon',
                    'generated' => 'Généré',
                    'validated' => 'Validé',
                    'sent' => 'Envoyé',
                    'closed' => 'Clôturé',
                    'cancelled' => 'Annulé',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('downloadPdf')
                    ->label('Télécharger PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->url(fn (Payslip $record): string => route('payslips.download', $record))
                    ->openUrlInNewTab(),
                Action::make('regeneratePdf')
                    ->label('Régénérer PDF')
                    ->icon('heroicon-o-arrow-path')
                    ->action(function (Payslip $record, PayslipPdfService $generator): void {
                        $generator->generate($record);
                        Notification::make()->title('PDF régénéré')->success()->send();
                    }),
                Action::make('fiscalDetails')
                    ->label('Détails fiscaux')
                    ->icon('heroicon-o-receipt-percent')
                    ->modalHeading('Détails fiscaux du bulletin')
                    ->modalContent(fn (Payslip $record): HtmlString => new HtmlString(self::fiscalDetailsHtml($record)))
                    ->modalSubmitAction(false),
                Action::make('recalculate')
                    ->label('Recalculer')
                    ->icon('heroicon-o-calculator')
                    ->requiresConfirmation()
                    ->visible(fn (Payslip $record): bool => $record->payrollPeriod?->status !== 'closed')
                    ->action(function (Payslip $record, PayrollCalculator $calculator): void {
                        $payslip = $calculator->calculate($record->employee, $record->payrollPeriod);
                        app(PayslipPdfService::class)->generate($payslip);
                        Notification::make()->title('Bulletin recalculé')->success()->send();
                    }),
                Action::make('snapshot')
                    ->label('Snapshot')
                    ->icon('heroicon-o-code-bracket-square')
                    ->modalHeading('Snapshot de calcul')
                    ->modalContent(fn (Payslip $record): HtmlString => new HtmlString('<pre style="white-space:pre-wrap;font-size:12px;">' . e(json_encode($record->calculation_snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) . '</pre>'))
                    ->modalSubmitAction(false),
                Action::make('validate')
                    ->label('Valider')
                    ->icon('heroicon-o-check-circle')
                    ->visible(fn (Payslip $record): bool => in_array($record->status, ['draft', 'generated'], true))
                    ->action(function (Payslip $record, AuditLogger $audit): void {
                        $record->update([
                            'status' => 'validated',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                        ]);
                        $audit->log('payslip_validated', $record, [], ['status' => 'validated']);
                        Notification::make()->title('Bulletin validé')->success()->send();
                    }),
                Action::make('markSent')
                    ->label('Marquer envoyé')
                    ->icon('heroicon-o-paper-airplane')
                    ->visible(fn (Payslip $record): bool => in_array($record->status, ['draft', 'validated'], true))
                    ->action(function (Payslip $record, AuditLogger $audit): void {
                        $record->update(['status' => 'sent', 'sent_at' => now()]);
                        $audit->log('payslip_sent', $record, [], ['status' => 'sent']);
                        $record->employee?->user?->notify(new SimpleFrenchNotification('Bulletin disponible', 'Votre bulletin de paie est disponible dans votre portail salarié.'));
                        Notification::make()->title('Bulletin marqué envoyé')->success()->send();
                    }),
                EditAction::make(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Aucun bulletin')
            ->emptyStateDescription('Générez les bulletins depuis une période de paie.')
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('validateSelected')
                        ->label('Valider sélection')
                        ->icon('heroicon-o-check-circle')
                        ->action(function (Collection $records): void {
                            $records->each(fn (Payslip $payslip) => $payslip->update([
                                'status' => 'validated',
                                'validated_by' => auth()->id(),
                                'validated_at' => now(),
                            ]));
                        }),
                    BulkAction::make('sendSelected')
                        ->label('Marquer envoyés')
                        ->icon('heroicon-o-paper-airplane')
                        ->action(fn (Collection $records) => $records->each(fn (Payslip $payslip) => $payslip->update(['status' => 'sent', 'sent_at' => now()]))),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private static function fiscalDetailsHtml(Payslip $payslip): string
    {
        $rows = [
            'Salaire brut total' => $payslip->gross_total,
            'Brut imposable' => $payslip->taxable_gross,
            'Base CNSS' => $payslip->cnss_base,
            'Base AMO' => $payslip->amo_base,
            'CNSS salarié' => $payslip->cnss_employee,
            'AMO salarié' => $payslip->amo_employee,
            'Frais professionnels' => $payslip->professional_expenses,
            'Revenu net imposable' => $payslip->taxable_net_income,
            'IR net' => $payslip->ir_net,
            'Indemnités exonérées' => $payslip->exempt_allowances,
            'Net à payer' => $payslip->net_to_pay,
        ];

        return '<dl>' . collect($rows)
            ->map(fn ($value, $label) => '<div class="flex justify-between gap-4 py-1"><dt>' . e($label) . '</dt><dd><strong>' . e(number_format((float) $value, 2, ',', ' ') . ' MAD') . '</strong></dd></div>')
            ->implode('') . '</dl>';
    }
}
