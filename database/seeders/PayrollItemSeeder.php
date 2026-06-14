<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\PayrollItem;
use Illuminate\Database\Seeder;

class PayrollItemSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::query()->get();

        if ($companies->isEmpty()) {
            $companies = collect([Company::query()->create(['name' => 'SmartRH Demo'])]);
        }

        $items = [
            ['BASE', 'Salaire de base', 'earning', 0, true, true, true, false, false, true, true],
            ['PRIMES', 'Primes imposables', 'earning', 0, true, true, true, false, false, true, true],
            ['TRANSPORT', 'Indemnite transport', 'earning', 500, false, false, false, true, true, true, true],
            ['PANIER', 'Prime panier', 'earning', 930, false, false, false, true, true, true, true],
            ['ABS', 'Absences et retards', 'deduction', 0, false, false, false, false, false, false, true],
            ['AVANCE', 'Avance sur salaire', 'deduction', 0, false, false, false, false, false, false, true],
        ];

        foreach ($companies as $company) {
            foreach ($items as [$code, $label, $type, $amount, $cnss, $amo, $ir, $exempt, $allowance, $gross, $net]) {
                $item = PayrollItem::query()->updateOrCreate(
                    ['company_id' => $company->id, 'code' => $code],
                    [
                        'label' => $label,
                        'type' => $type,
                        'default_amount' => $amount,
                        'calculation_type' => $amount > 0 ? 'fixed' : 'manual',
                        'is_active' => true,
                    ],
                );

                $item->rule()->updateOrCreate(
                    ['payroll_item_id' => $item->id],
                    [
                        'subject_to_cnss' => $cnss,
                        'subject_to_amo' => $amo,
                        'subject_to_ir' => $ir,
                        'is_tax_exempt' => $exempt,
                        'is_non_taxable_allowance' => $allowance,
                        'affects_gross' => $gross,
                        'affects_net' => $net,
                    ],
                );
            }
        }
    }
}
