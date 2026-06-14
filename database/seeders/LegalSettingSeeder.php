<?php

namespace Database\Seeders;

use App\Models\IrBracket;
use App\Models\LegalSetting;
use Illuminate\Database\Seeder;

class LegalSettingSeeder extends Seeder
{
    public function run(): void
    {
        LegalSetting::query()->updateOrCreate(
            ['year' => 2026, 'label' => 'Parametres legaux demo 2026 API'],
            [
                'cnss_ceiling' => 6000,
                'cnss_employee_rate' => 0.0448,
                'cnss_short_term_employee_rate' => 0.0052,
                'cnss_long_term_employee_rate' => 0.0396,
                'amo_employee_rate' => 0.0226,
                'professional_expenses_rate' => 0.35,
                'professional_expense_rate' => 0.35,
                'professional_expenses_ceiling' => null,
                'professional_expense_ceiling' => null,
                'family_deduction_amount' => 0,
                'effective_from' => '2025-12-31',
                'effective_to' => null,
                'active' => true,
                'is_active' => true,
                'notes' => 'Valeurs de demonstration a verifier avant production avec un expert-comptable marocain.',
            ],
        );

        foreach ([
            [0, 3000, 0.00, 0],
            [3000.01, 4166.67, 0.10, 300],
            [4166.68, 5000, 0.20, 716.67],
            [5000.01, 6666.67, 0.30, 1216.67],
            [6666.68, 15000, 0.34, 1483.33],
            [15000.01, null, 0.38, 2083.33],
        ] as [$min, $max, $rate, $deduction]) {
            IrBracket::query()->updateOrCreate(
                [
                    'year' => 2026,
                    'period_type' => 'monthly',
                    'min_amount' => $min,
                    'max_amount' => $max,
                ],
                [
                    'rate' => $rate,
                    'deduction' => $deduction,
                    'effective_from' => '2025-12-31',
                    'effective_to' => null,
                    'active' => true,
                ],
            );
        }
    }
}
