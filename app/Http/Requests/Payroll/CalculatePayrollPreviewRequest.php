<?php

namespace App\Http\Requests\Payroll;

use App\Models\Employee;
use App\Models\PayrollPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CalculatePayrollPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->canAccessCompany($this->integer('company_id')) ?? false;
    }

    public function rules(): array
    {
        return [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'payroll_period_id' => ['nullable', 'integer', 'exists:payroll_periods,id'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'children_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:50'],
            'items.*.label' => ['required', 'string', 'max:255'],
            'items.*.type' => ['required', 'in:earning,deduction'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.subject_to_cnss' => ['nullable', 'boolean'],
            'items.*.subject_to_amo' => ['nullable', 'boolean'],
            'items.*.subject_to_ir' => ['nullable', 'boolean'],
            'items.*.is_tax_exempt' => ['nullable', 'boolean'],
            'items.*.is_non_taxable_allowance' => ['nullable', 'boolean'],
            'items.*.affects_gross' => ['nullable', 'boolean'],
            'items.*.affects_net' => ['nullable', 'boolean'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $belongs = Employee::query()
                    ->whereKey($this->integer('employee_id'))
                    ->where('company_id', $this->integer('company_id'))
                    ->exists();

                if (! $belongs) {
                    $validator->errors()->add('employee_id', 'The selected employee does not belong to the selected company.');
                }

                if ($this->filled('payroll_period_id')) {
                    $periodBelongs = PayrollPeriod::query()
                        ->whereKey($this->integer('payroll_period_id'))
                        ->where('company_id', $this->integer('company_id'))
                        ->exists();

                    if (! $periodBelongs) {
                        $validator->errors()->add('payroll_period_id', 'The selected payroll period does not belong to the selected company.');
                    }
                }
            },
        ];
    }
}
