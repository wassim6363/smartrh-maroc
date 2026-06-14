<?php

namespace App\Services\Imports;

use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Services\Saas\SubscriptionGate;
use Illuminate\Support\Str;

class EmployeeCsvImporter
{
    /**
     * CSV is supported for the MVP. XLSX can be added later with a spreadsheet
     * package while keeping the same normalized row mapping.
     */
    public function import(string $path, int $companyId): array
    {
        $handle = fopen($path, 'r');
        if (! $handle) {
            return ['imported' => 0, 'skipped' => 0, 'errors' => ['Impossible de lire le fichier.']];
        }

        $headers = array_map(fn ($value) => Str::snake(trim((string) $value)), fgetcsv($handle) ?: []);
        $imported = 0;
        $skipped = 0;
        $errors = [];
        $company = \App\Models\Company::query()->findOrFail($companyId);

        while (($row = fgetcsv($handle)) !== false) {
            $data = array_combine($headers, array_pad($row, count($headers), null));

            if (blank($data['first_name'] ?? null) || blank($data['last_name'] ?? null)) {
                $skipped++;
                $errors[] = 'Ligne ignorée: prénom et nom obligatoires.';
                continue;
            }

            $department = null;
            if (filled($data['department'] ?? null)) {
                $department = Department::query()->firstOrCreate([
                    'company_id' => $companyId,
                    'name' => trim($data['department']),
                ]);
            }

            $position = null;
            if (filled($data['position'] ?? null)) {
                $position = Position::query()->firstOrCreate([
                    'company_id' => $companyId,
                    'title' => trim($data['position']),
                ], [
                    'department_id' => $department?->id,
                ]);
            }

            try {
                app(SubscriptionGate::class)->assertCanAddEmployee($company);
            } catch (\Throwable $exception) {
                $skipped++;
                $errors[] = $exception->getMessage();
                break;
            }

            Employee::query()->create([
                'company_id' => $companyId,
                'department_id' => $department?->id,
                'position_id' => $position?->id,
                'employee_number' => 'IMP-' . now()->format('YmdHis') . '-' . str_pad((string) ($imported + 1), 3, '0', STR_PAD_LEFT),
                'first_name' => trim($data['first_name']),
                'last_name' => trim($data['last_name']),
                'cin' => $data['cin'] ?? null,
                'cnss_number' => $data['cnss_number'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'base_salary' => (float) ($data['base_salary'] ?? 0),
                'hire_date' => filled($data['hire_date'] ?? null) ? $data['hire_date'] : now()->toDateString(),
                'contract_type' => $data['contract_type'] ?: 'cdi',
                'status' => 'active',
            ]);

            $imported++;
        }

        fclose($handle);

        return ['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors];
    }
}
