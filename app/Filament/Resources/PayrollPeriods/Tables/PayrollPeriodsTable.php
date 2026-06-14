<?php

namespace App\Filament\Resources\PayrollPeriods\Tables;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use App\Services\Documents\PayslipPdfGenerator;
use App\Services\Audit\AuditLogger;
use App\Services\Payroll\TaxRuleResolver;
use App\Services\Payroll\PayrollCalculator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class PayrollPeriodsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')->sortable()->searchable(),
                TextColumn::make('name')->sortable()->searchable(),
                TextColumn::make('starts_at')->date()->sortable(),
                TextColumn::make('ends_at')->date()->sortable(),
                TextColumn::make('payment_date')->date()->sortable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('payslips_count')->counts('payslips')->label('Payslips')->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')->relationship('company', 'name')->label('Company'),
                SelectFilter::make('status')->options(['draft' => 'Brouillon', 'generated' => 'Généré', 'validated' => 'Validé', 'sent' => 'Envoyé', 'closed' => 'Clôturé', 'cancelled' => 'Annulé']),
            ])
            ->recordActions([
                Action::make('generateSelectedPayslips')
                    ->label('Générer sélection')
                    ->icon('heroicon-o-user-group')
                    ->form([
                        Select::make('employee_ids')
                            ->label('Salariés')
                            ->multiple()
                            ->options(fn (PayrollPeriod $record): array => Employee::query()
                                ->where('company_id', $record->company_id)
                                ->where('status', 'active')
                                ->orderBy('last_name')
                                ->get()
                                ->mapWithKeys(fn (Employee $employee) => [$employee->id => $employee->employee_number . ' - ' . $employee->full_name])
                                ->all())
                            ->required(),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (PayrollPeriod $record): bool => $record->status !== 'closed')
                    ->action(function (PayrollPeriod $record, array $data, PayrollCalculator $calculator, TaxRuleResolver $rules, PayslipPdfGenerator $pdfGenerator, AuditLogger $audit): void {
                        if ($record->status === 'closed') {
                            Notification::make()->title('La période est clôturée')->danger()->send();
                            return;
                        }

                        $missingRules = $rules->missingRules($record->company_id, $record->ends_at);
                        if ($missingRules !== []) {
                            Notification::make()
                                ->title('Paramètres de paie manquants')
                                ->body('Veuillez configurer: ' . implode(', ', $missingRules))
                                ->warning()
                                ->send();

                            return;
                        }

                        $generated = 0;
                        Employee::query()
                            ->where('company_id', $record->company_id)
                            ->whereIn('id', $data['employee_ids'] ?? [])
                            ->where('status', 'active')
                            ->get()
                            ->each(function (Employee $employee) use ($record, $calculator, $pdfGenerator, &$generated): void {
                                if ($record->payslips()->where('employee_id', $employee->id)->exists()) {
                                    return;
                                }

                                $payslip = $calculator->calculate($employee, $record);
                                $pdfGenerator->generate($payslip);
                                $generated++;
                            });

                        if ($generated > 0) {
                            $record->update(['status' => 'generated']);
                        }

                        $audit->log('payroll_selected_generated', $record, [], ['payslips' => $generated]);

                        Notification::make()
                            ->title($generated . ' bulletins générés avec succès.')
                            ->success()
                            ->send();
                    }),
                Action::make('generatePayslips')
                    ->label('Générer les bulletins')
                    ->icon('heroicon-o-calculator')
                    ->requiresConfirmation()
                    ->modalHeading('Prévisualisation paie')
                    ->modalDescription(fn (PayrollPeriod $record): HtmlString => new HtmlString(self::warningsHtml($record)))
                    ->action(function (PayrollPeriod $record, PayrollCalculator $calculator, TaxRuleResolver $rules, PayslipPdfGenerator $pdfGenerator, AuditLogger $audit): void {
                        if ($record->status === 'closed') {
                            Notification::make()->title('La période est clôturée')->danger()->send();
                            return;
                        }

                        $missingRules = $rules->missingRules($record->company_id, $record->ends_at);
                        if ($missingRules !== []) {
                            Notification::make()
                                ->title('Paramètres de paie manquants')
                                ->body('Veuillez configurer: ' . implode(', ', $missingRules))
                                ->warning()
                                ->send();

                            return;
                        }

                        $employees = Employee::query()
                            ->where('company_id', $record->company_id)
                            ->where('status', 'active')
                            ->get();

                        if ($employees->isEmpty()) {
                            Notification::make()
                                ->title('Aucun salarié actif trouvé')
                                ->warning()
                                ->send();

                            return;
                        }

                        $generated = 0;
                        foreach ($employees as $employee) {
                            if ($record->payslips()->where('employee_id', $employee->id)->exists()) {
                                continue;
                            }

                            $payslip = $calculator->calculate($employee, $record);
                            $pdfGenerator->generate($payslip);
                            $generated++;
                        }

                        $record->update(['status' => 'generated']);
                        $audit->log('payroll_generated', $record, [], ['payslips' => $generated]);

                        Notification::make()
                            ->title($generated . ' bulletins générés avec succès.')
                            ->success()
                            ->send();
                    }),
                Action::make('summary')
                    ->label('Résumé')
                    ->icon('heroicon-o-chart-bar')
                    ->modalHeading('Résumé de la période')
                    ->modalContent(fn (PayrollPeriod $record): HtmlString => new HtmlString(self::summaryHtml($record)))
                    ->modalSubmitAction(false),
                Action::make('validatePayslips')
                    ->label('Valider les bulletins')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->visible(fn (PayrollPeriod $record): bool => in_array($record->status, ['generated', 'validated'], true))
                    ->action(function (PayrollPeriod $record, AuditLogger $audit): void {
                        $record->payslips()->where('status', 'generated')->update([
                            'status' => 'validated',
                            'validated_by' => auth()->id(),
                            'validated_at' => now(),
                        ]);
                        $record->update(['status' => 'validated']);
                        $audit->log('payroll_validated', $record);
                        Notification::make()->title('Bulletins valides')->success()->send();
                    }),
                Action::make('close')
                    ->label('Clôturer')
                    ->icon('heroicon-o-lock-closed')
                    ->requiresConfirmation()
                    ->visible(fn (PayrollPeriod $record): bool => $record->status !== 'closed')
                    ->action(function (PayrollPeriod $record, AuditLogger $audit): void {
                        $record->update(['status' => 'closed']);
                        $record->payslips()->update(['status' => 'closed']);
                        $audit->log('payroll_closed', $record);
                        Notification::make()->title('Période clôturée')->success()->send();
                    }),
                Action::make('reopen')
                    ->label('Réouvrir')
                    ->icon('heroicon-o-lock-open')
                    ->requiresConfirmation()
                    ->visible(fn (PayrollPeriod $record): bool => $record->status === 'closed' && auth()->user()?->hasAnyRole(['Super Admin', 'Payroll Manager']))
                    ->action(function (PayrollPeriod $record, AuditLogger $audit): void {
                        $record->update(['status' => 'generated']);
                        $record->payslips()->where('status', 'closed')->update(['status' => 'validated']);
                        $audit->log('payroll_reopened', $record);
                        Notification::make()->title('Période réouverte')->success()->send();
                    }),
                EditAction::make()
                    ->visible(fn (PayrollPeriod $record): bool => $record->status !== 'closed' || auth()->user()?->hasAnyRole(['Super Admin', 'Payroll Manager'])),
            ])
            ->defaultSort('starts_at', 'desc')
            ->emptyStateHeading('Aucune période de paie')
            ->emptyStateDescription('Créez une période puis générez les bulletins des salariés actifs.')
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }

    private static function warningsHtml(PayrollPeriod $period): string
    {
        $employees = Employee::query()->where('company_id', $period->company_id)->where('status', 'active')->withCount('bankAccounts')->get();
        $warnings = [];

        if (blank($period->company?->cnss_number)) {
            $warnings[] = 'Numéro CNSS société manquant.';
        }

        foreach ($employees as $employee) {
            if ((float) $employee->base_salary <= 0) {
                $warnings[] = "{$employee->full_name}: salaire de base à 0.";
            }
            if (blank($employee->cnss_number)) {
                $warnings[] = "{$employee->full_name}: numéro CNSS manquant.";
            }
            if ($employee->bank_accounts_count === 0) {
                $warnings[] = "{$employee->full_name}: RIB manquant.";
            }
        }

        $list = $warnings ? '<ul><li>' . implode('</li><li>', array_map('e', $warnings)) . '</li></ul>' : '<p>Aucune alerte bloquante détectée.</p>';

        return '<div class="space-y-2"><p><strong>Salariés actifs:</strong> ' . $employees->count() . '</p><p>Les paramètres légaux doivent être validés par un expert-comptable marocain avant production.</p>' . $list . '</div>';
    }

    private static function summaryHtml(PayrollPeriod $period): string
    {
        $payslips = $period->payslips();
        $rows = [
            'Total salariés' => Employee::query()->where('company_id', $period->company_id)->where('status', 'active')->count(),
            'Salaire brut total' => number_format((float) $payslips->sum('gross_salary'), 2) . ' MAD',
            'Déductions totales' => number_format((float) $payslips->sum('total_employee_deductions'), 2) . ' MAD',
            'Net total' => number_format((float) $payslips->sum('net_salary'), 2) . ' MAD',
            'Brouillons' => $period->payslips()->whereIn('status', ['draft', 'generated'])->count(),
            'Validés' => $period->payslips()->where('status', 'validated')->count(),
            'Envoyés' => $period->payslips()->where('status', 'sent')->count(),
        ];

        return '<dl>' . collect($rows)->map(fn ($value, $label) => '<div class="flex justify-between gap-4 py-1"><dt>' . e($label) . '</dt><dd><strong>' . e((string) $value) . '</strong></dd></div>')->implode('') . '</dl>';
    }
}
