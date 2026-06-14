<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeePayrollItem;
use App\Models\PayrollPeriod;
use App\Services\Payroll\PayslipGenerationService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Validation\ValidationException;

class PayrollCalculationPreview extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalculator;

    protected static string|\UnitEnum|null $navigationGroup = 'Paie';

    protected static ?string $navigationLabel = 'Prévisualisation paie';

    protected static ?string $title = 'Prévisualisation paie';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.payroll-calculation-preview';

    public ?int $company_id = null;

    public ?int $employee_id = null;

    public ?int $payroll_period_id = null;

    public int $year = 2026;

    public int $month = 6;

    public array $items = [];

    public ?array $preview = null;

    public ?int $generatedPayslipId = null;

    public ?string $generatedPdfPath = null;

    public function mount(): void
    {
        $requestedEmployee = request()->integer('employee_id')
            ? Employee::query()->find(request()->integer('employee_id'))
            : null;

        $this->company_id = $requestedEmployee?->company_id
            ?: (auth()->user()?->isSuperAdmin()
                ? Company::query()->value('id')
                : auth()->user()?->currentCompanyId());

        $this->employee_id = $requestedEmployee && auth()->user()?->canAccessCompany($requestedEmployee->company_id)
            ? $requestedEmployee->id
            : $this->employees()->keys()->first();

        $this->payroll_period_id = $this->periods()->keys()->first();
        $this->syncPeriod();
        $this->items = $this->defaultItems();
    }

    public function updatedCompanyId(): void
    {
        $this->employee_id = $this->employees()->keys()->first();
        $this->payroll_period_id = $this->periods()->keys()->first();
        $this->syncPeriod();
        $this->items = $this->defaultItems();
        $this->preview = null;
    }

    public function updatedEmployeeId(): void
    {
        $this->items = $this->defaultItems();
        $this->preview = null;
    }

    public function updatedPayrollPeriodId(): void
    {
        $this->syncPeriod();
        $this->items = $this->defaultItems();
        $this->preview = null;
    }

    public function addItem(): void
    {
        $this->items[] = [
            'code' => 'PRIME',
            'label' => 'Nouvelle rubrique',
            'type' => 'earning',
            'amount' => 0,
            'subject_to_cnss' => true,
            'subject_to_amo' => true,
            'subject_to_ir' => true,
            'is_tax_exempt' => false,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->preview = null;
    }

    public function calculatePreview(PayslipGenerationService $service): void
    {
        $data = $this->payload();
        $this->authorizeCompany($data['company_id']);
        $this->preview = $service->preview($data);
        $this->generatedPayslipId = null;
        $this->generatedPdfPath = null;

        Notification::make()->title('Prévisualisation calculée')->success()->send();
    }

    public function generatePayslip(PayslipGenerationService $service): void
    {
        try {
            $data = $this->payload();
            $this->authorizeCompany($data['company_id']);
            $payslip = $service->generate($data);
        } catch (ValidationException $exception) {
            Notification::make()
                ->title(collect($exception->errors())->flatten()->first() ?: 'Impossible de générer le bulletin.')
                ->danger()
                ->send();

            return;
        }

        $this->preview = $payslip->calculation_snapshot['result'] ?? $this->preview;
        $this->generatedPayslipId = $payslip->id;
        $this->generatedPdfPath = $payslip->pdf_path;

        Notification::make()->title('Bulletin de paie généré')->success()->send();
    }

    public function companies()
    {
        $user = auth()->user();

        return $user?->isSuperAdmin()
            ? Company::query()->orderBy('name')->pluck('name', 'id')
            : Company::query()->whereKey($user?->currentCompanyId())->pluck('name', 'id');
    }

    public function employees()
    {
        return Employee::query()
            ->where('company_id', $this->company_id)
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Employee $employee) => [$employee->id => "{$employee->employee_number} - {$employee->full_name}"]);
    }

    public function periods()
    {
        return PayrollPeriod::query()
            ->where('company_id', $this->company_id)
            ->orderByDesc('starts_at')
            ->pluck('name', 'id');
    }

    public function formatMoney(mixed $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ') . ' MAD';
    }

    private function payload(): array
    {
        $this->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'payroll_period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:50'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'in:earning,deduction'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
        ]);

        abort_unless(Employee::query()->whereKey($this->employee_id)->where('company_id', $this->company_id)->exists(), 403);
        if ($this->payroll_period_id) {
            abort_unless(PayrollPeriod::query()->whereKey($this->payroll_period_id)->where('company_id', $this->company_id)->exists(), 403);
        }

        return [
            'company_id' => (int) $this->company_id,
            'employee_id' => (int) $this->employee_id,
            'payroll_period_id' => $this->payroll_period_id ? (int) $this->payroll_period_id : null,
            'year' => $this->year,
            'month' => $this->month,
            'items' => array_map(fn (array $item): array => [
                'code' => $item['code'],
                'label' => $item['label'],
                'type' => $item['type'],
                'amount' => (float) $item['amount'],
                'subject_to_cnss' => (bool) ($item['subject_to_cnss'] ?? false),
                'subject_to_amo' => (bool) ($item['subject_to_amo'] ?? false),
                'subject_to_ir' => (bool) ($item['subject_to_ir'] ?? false),
                'is_tax_exempt' => (bool) ($item['is_tax_exempt'] ?? false),
                'is_non_taxable_allowance' => (bool) ($item['is_non_taxable_allowance'] ?? false),
                'affects_gross' => true,
                'affects_net' => true,
            ], $this->items),
        ];
    }

    private function syncPeriod(): void
    {
        $period = $this->payroll_period_id
            ? PayrollPeriod::query()->where('company_id', $this->company_id)->find($this->payroll_period_id)
            : null;

        if ($period) {
            $this->year = (int) ($period->year ?: $period->starts_at?->year ?: $this->year);
            $this->month = (int) ($period->month ?: $period->starts_at?->month ?: $this->month);
        }
    }

    private function authorizeCompany(int $companyId): void
    {
        abort_unless(auth()->user()?->canAccessCompany($companyId), 403);
    }

    private function defaultItems(): array
    {
        $employee = $this->employee_id
            ? Employee::query()->where('company_id', $this->company_id)->find($this->employee_id)
            : null;
        $period = $this->payroll_period_id
            ? PayrollPeriod::query()->where('company_id', $this->company_id)->find($this->payroll_period_id)
            : null;

        $items = [[
            'code' => 'BASE',
            'label' => 'Salaire de base',
            'type' => 'earning',
            'amount' => (float) ($employee?->base_salary ?: 0),
            'subject_to_cnss' => true,
            'subject_to_amo' => true,
            'subject_to_ir' => true,
            'is_tax_exempt' => false,
        ]];

        if (! $employee || ! $period) {
            return $items;
        }

        EmployeePayrollItem::query()
            ->where('employee_id', $employee->id)
            ->activeForPeriod($period)
            ->orderBy('id')
            ->get()
            ->each(function (EmployeePayrollItem $item) use (&$items): void {
                $type = in_array($item->type, ['advance', 'deduction', 'other'], true) ? 'deduction' : 'earning';
                $items[] = [
                    'code' => $item->code ?: str((string) $item->label)->slug('_')->upper()->limit(40, '')->toString(),
                    'label' => $item->label,
                    'type' => $type,
                    'amount' => (float) $item->amount,
                    'subject_to_cnss' => $type === 'earning' && (bool) $item->subject_to_cnss,
                    'subject_to_amo' => $type === 'earning' && (bool) $item->subject_to_amo,
                    'subject_to_ir' => $type === 'earning' && (bool) $item->subject_to_ir,
                    'is_tax_exempt' => $type === 'earning' && (bool) $item->is_tax_exempt,
                ];
            });

        return $items;
    }
}
