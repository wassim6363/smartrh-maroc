<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('legal_settings')) {
            Schema::create('legal_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('year')->nullable()->index();
                $table->string('label');
                $table->decimal('cnss_ceiling', 12, 2)->nullable();
                $table->decimal('cnss_employee_rate', 8, 4)->nullable();
                $table->decimal('cnss_short_term_employee_rate', 8, 4)->nullable();
                $table->decimal('cnss_long_term_employee_rate', 8, 4)->nullable();
                $table->decimal('amo_employee_rate', 8, 4)->nullable();
                $table->decimal('professional_expenses_rate', 8, 4)->nullable();
                $table->decimal('professional_expenses_ceiling', 12, 2)->nullable();
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('ir_brackets', 'year')) {
            Schema::table('ir_brackets', function (Blueprint $table) {
                $table->unsignedInteger('year')->nullable()->index();
                $table->boolean('active')->default(true)->index();
                $table->text('notes')->nullable();
            });
        }

        if (! Schema::hasTable('seniority_bonus_rates')) {
            Schema::create('seniority_bonus_rates', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('min_years');
                $table->unsignedInteger('max_years')->nullable();
                $table->decimal('rate', 8, 4);
                $table->date('effective_from');
                $table->date('effective_to')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('employee_payroll_items')) {
            Schema::create('employee_payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->string('label');
                $table->string('code')->nullable();
                $table->string('type')->index();
                $table->decimal('amount', 12, 2);
                $table->boolean('taxable')->default(true);
                $table->boolean('recurring')->default(false);
                $table->date('starts_at')->nullable();
                $table->date('ends_at')->nullable();
                $table->boolean('active')->default(true)->index();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'employee_id', 'type']);
            });
        }

        Schema::table('absences', function (Blueprint $table) {
            if (! Schema::hasColumn('absences', 'duration_days')) {
                $table->decimal('duration_days', 6, 2)->nullable();
            }
            if (! Schema::hasColumn('absences', 'justified')) {
                $table->boolean('justified')->default(false);
            }
            if (! Schema::hasColumn('absences', 'payroll_impact')) {
                $table->boolean('payroll_impact')->default(true);
            }
            if (! Schema::hasColumn('absences', 'deduction_amount')) {
                $table->decimal('deduction_amount', 12, 2)->nullable();
            }
        });

        Schema::table('payslips', function (Blueprint $table) {
            $columns = [
                'base_salary' => fn () => $table->decimal('base_salary', 12, 2)->default(0),
                'total_taxable_primes' => fn () => $table->decimal('total_taxable_primes', 12, 2)->default(0),
                'total_taxable_indemnities' => fn () => $table->decimal('total_taxable_indemnities', 12, 2)->default(0),
                'total_non_taxable_indemnities' => fn () => $table->decimal('total_non_taxable_indemnities', 12, 2)->default(0),
                'total_overtime' => fn () => $table->decimal('total_overtime', 12, 2)->default(0),
                'total_absences' => fn () => $table->decimal('total_absences', 12, 2)->default(0),
                'cnss_base' => fn () => $table->decimal('cnss_base', 12, 2)->default(0),
                'cnss_employee' => fn () => $table->decimal('cnss_employee', 12, 2)->default(0),
                'amo_base' => fn () => $table->decimal('amo_base', 12, 2)->default(0),
                'amo_employee' => fn () => $table->decimal('amo_employee', 12, 2)->default(0),
                'taxable_before_professional_expenses' => fn () => $table->decimal('taxable_before_professional_expenses', 12, 2)->default(0),
                'professional_expenses' => fn () => $table->decimal('professional_expenses', 12, 2)->default(0),
                'taxable_income' => fn () => $table->decimal('taxable_income', 12, 2)->default(0),
                'ir_gross' => fn () => $table->decimal('ir_gross', 12, 2)->default(0),
                'ir_net' => fn () => $table->decimal('ir_net', 12, 2)->default(0),
                'total_advances' => fn () => $table->decimal('total_advances', 12, 2)->default(0),
                'total_other_deductions' => fn () => $table->decimal('total_other_deductions', 12, 2)->default(0),
                'total_deductions' => fn () => $table->decimal('total_deductions', 12, 2)->default(0),
                'net_pay' => fn () => $table->decimal('net_pay', 12, 2)->default(0),
                'ytd_gross_salary' => fn () => $table->decimal('ytd_gross_salary', 12, 2)->default(0),
                'ytd_taxable_income' => fn () => $table->decimal('ytd_taxable_income', 12, 2)->default(0),
                'ytd_ir' => fn () => $table->decimal('ytd_ir', 12, 2)->default(0),
                'ytd_cnss' => fn () => $table->decimal('ytd_cnss', 12, 2)->default(0),
                'ytd_amo' => fn () => $table->decimal('ytd_amo', 12, 2)->default(0),
                'ytd_net_pay' => fn () => $table->decimal('ytd_net_pay', 12, 2)->default(0),
                'ytd_total_deductions' => fn () => $table->decimal('ytd_total_deductions', 12, 2)->default(0),
                'generated_at' => fn () => $table->timestamp('generated_at')->nullable(),
                'closed_at' => fn () => $table->timestamp('closed_at')->nullable(),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('payslips', $column)) {
                    $definition();
                }
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_payroll_items');
        Schema::dropIfExists('seniority_bonus_rates');
        Schema::dropIfExists('legal_settings');
    }
};
