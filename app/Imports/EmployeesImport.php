<?php

namespace App\Imports;

use App\Models\Company;
use App\Models\Employee;
use App\Services\Saas\SubscriptionLimitService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class EmployeesImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    use Importable;

    private Company $company;

    private array $errors = [];

    private int $importedCount = 0;

    private int $skippedCount = 0;

    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    public function collection(Collection $rows): void
    {
        $limitService = app(SubscriptionLimitService::class);

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $data = $this->normalizeRow($row);

            $validator = Validator::make($data, $this->rules(), $this->messages());

            if ($validator->fails()) {
                $this->errors[] = __('Ligne :row : :errors', [
                    'row' => $rowNumber,
                    'errors' => implode(', ', $validator->errors()->all()),
                ]);
                $this->skippedCount++;

                continue;
            }

            $existing = Employee::query()
                ->where('company_id', $this->company->id)
                ->where(function ($q) use ($data) {
                    $q->where('email', $data['email'])
                        ->orWhere('cin', $data['cin']);
                })
                ->exists();

            if ($existing) {
                $this->errors[] = __('Ligne :row : Un employé avec ce CIN ou cet email existe déjà.', ['row' => $rowNumber]);
                $this->skippedCount++;

                continue;
            }

            if (! $limitService->canAddEmployee($this->company)) {
                $this->errors[] = __('Ligne :row : Limite d\'employés atteinte pour votre abonnement.', ['row' => $rowNumber]);
                $this->skippedCount++;

                continue;
            }

            Employee::create(array_merge($data, ['company_id' => $this->company->id]));
            $this->importedCount++;
        }
    }

    private function normalizeRow($row): array
    {
        return [
            'employee_number' => trim($row['matricule'] ?? $row['employee_number'] ?? ''),
            'cin' => trim($row['cin'] ?? ''),
            'email' => trim($row['email'] ?? ''),
            'first_name' => trim($row['prenom'] ?? $row['first_name'] ?? ''),
            'last_name' => trim($row['nom'] ?? $row['last_name'] ?? ''),
            'cnss_number' => trim($row['num_cnss'] ?? $row['cnss_number'] ?? ''),
            'family_situation' => $row['situation_familiale'] ?? $row['family_situation'] ?? $row['marital_status'] ?? null,
            'dependents_count' => is_numeric($row['nombre_enfants'] ?? $row['dependents_count'] ?? $row['children_count'] ?? null)
                ? (int) ($row['nombre_enfants'] ?? $row['dependents_count'] ?? $row['children_count'])
                : 0,
            'job_title' => trim($row['poste'] ?? $row['job_title'] ?? ''),
            'base_salary' => is_numeric($row['salaire_base'] ?? $row['base_salary'] ?? null)
                ? (float) ($row['salaire_base'] ?? $row['base_salary'])
                : 0,
            'hire_date' => $row['date_embauche'] ?? $row['hire_date'] ?? null,
        ];
    }

    private function rules(): array
    {
        return [
            'employee_number' => ['required', 'string', 'max:50'],
            'cin' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'cnss_number' => ['nullable', 'string', 'max:20'],
            'family_situation' => ['nullable', 'string', 'max:50'],
            'dependents_count' => ['nullable', 'integer', 'min:0', 'max:99'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'hire_date' => ['nullable', 'date'],
        ];
    }

    private function messages(): array
    {
        return [
            'employee_number.required' => __('Le matricule est obligatoire.'),
            'cin.required' => __('Le CIN est obligatoire.'),
            'email.required' => __('L\'email est obligatoire.'),
            'email.email' => __('L\'email n\'est pas valide.'),
            'first_name.required' => __('Le prénom est obligatoire.'),
            'last_name.required' => __('Le nom est obligatoire.'),
            'base_salary.numeric' => __('Le salaire de base doit être un nombre.'),
            'hire_date.date' => __('La date d\'embauche n\'est pas valide.'),
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }
}
