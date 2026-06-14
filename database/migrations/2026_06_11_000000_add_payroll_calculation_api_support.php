<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('companies', 'logo_path')) {
            Schema::table('companies', fn (Blueprint $table) => $table->string('logo_path')->nullable()->after('email'));
        }

        if (! Schema::hasColumn('companies', 'if_number')) {
            Schema::table('companies', fn (Blueprint $table) => $table->string('if_number')->nullable()->after('if'));
        }

        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'job_title')) {
                $table->string('job_title')->nullable()->after('cnss_number');
            }
            if (! Schema::hasColumn('employees', 'department')) {
                $table->string('department')->nullable()->after('job_title');
            }
            if (! Schema::hasColumn('employees', 'marital_status')) {
                $table->string('marital_status')->nullable()->after('contract_type');
            }
            if (! Schema::hasColumn('employees', 'children_count')) {
                $table->unsignedInteger('children_count')->default(0)->after('marital_status');
            }
        });

        if (! Schema::hasTable('payroll_items')) {
            Schema::create('payroll_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->string('code');
                $table->string('label');
                $table->string('type')->default('earning')->index();
                $table->decimal('default_amount', 12, 2)->default(0);
                $table->string('calculation_type')->default('fixed');
                $table->boolean('is_active')->default(true)->index();
                $table->timestamps();
                $table->unique(['company_id', 'code']);
            });
        }

        if (! Schema::hasTable('payroll_item_rules')) {
            Schema::create('payroll_item_rules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_item_id')->constrained()->cascadeOnDelete();
                $table->boolean('subject_to_cnss')->default(false);
                $table->boolean('subject_to_amo')->default(false);
                $table->boolean('subject_to_ir')->default(false);
                $table->boolean('is_tax_exempt')->default(false);
                $table->boolean('is_non_taxable_allowance')->default(false);
                $table->boolean('affects_gross')->default(true);
                $table->boolean('affects_net')->default(true);
                $table->timestamps();
            });
        }

        Schema::table('legal_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('legal_settings', 'professional_expense_rate')) {
                $table->decimal('professional_expense_rate', 8, 4)->nullable()->after('professional_expenses_rate');
            }
            if (! Schema::hasColumn('legal_settings', 'professional_expense_ceiling')) {
                $table->decimal('professional_expense_ceiling', 12, 2)->nullable()->after('professional_expenses_ceiling');
            }
            if (! Schema::hasColumn('legal_settings', 'family_deduction_amount')) {
                $table->decimal('family_deduction_amount', 12, 2)->default(0)->after('professional_expense_ceiling');
            }
            if (! Schema::hasColumn('legal_settings', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('active');
            }
        });

        if (! Schema::hasColumn('ir_brackets', 'period_type')) {
            Schema::table('ir_brackets', fn (Blueprint $table) => $table->string('period_type')->default('monthly')->after('deduction'));
        }

        Schema::table('payroll_periods', function (Blueprint $table) {
            if (! Schema::hasColumn('payroll_periods', 'month')) {
                $table->unsignedTinyInteger('month')->nullable()->after('company_id');
            }
            if (! Schema::hasColumn('payroll_periods', 'year')) {
                $table->unsignedInteger('year')->nullable()->after('month');
            }
            if (! Schema::hasColumn('payroll_periods', 'start_date')) {
                $table->date('start_date')->nullable()->after('year');
            }
            if (! Schema::hasColumn('payroll_periods', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }
        });

        Schema::table('payslips', function (Blueprint $table) {
            $columns = [
                'gross_total' => fn () => $table->decimal('gross_total', 12, 2)->default(0)->after('reference'),
                'taxable_gross' => fn () => $table->decimal('taxable_gross', 12, 2)->default(0)->after('gross_total'),
                'salary_after_contributions' => fn () => $table->decimal('salary_after_contributions', 12, 2)->default(0)->after('amo_employee'),
                'taxable_net_income' => fn () => $table->decimal('taxable_net_income', 12, 2)->default(0)->after('professional_expenses'),
                'ir_brut' => fn () => $table->decimal('ir_brut', 12, 2)->default(0)->after('taxable_net_income'),
                'exempt_allowances' => fn () => $table->decimal('exempt_allowances', 12, 2)->default(0)->after('ir_net'),
                'net_deductions' => fn () => $table->decimal('net_deductions', 12, 2)->default(0)->after('exempt_allowances'),
                'net_to_pay' => fn () => $table->decimal('net_to_pay', 12, 2)->default(0)->after('net_deductions'),
                'pdf_path' => fn () => $table->string('pdf_path')->nullable()->after('status'),
            ];

            foreach ($columns as $column => $definition) {
                if (! Schema::hasColumn('payslips', $column)) {
                    $definition();
                }
            }
        });

        Schema::table('payslip_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('payslip_lines', 'payroll_item_id')) {
                $table->foreignId('payroll_item_id')->nullable()->after('payslip_id')->constrained('payroll_items')->nullOnDelete();
            }
            if (! Schema::hasColumn('payslip_lines', 'base_amount')) {
                $table->decimal('base_amount', 12, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('payslip_lines', 'subject_to_cnss')) {
                $table->boolean('subject_to_cnss')->default(false)->after('rate');
            }
            if (! Schema::hasColumn('payslip_lines', 'subject_to_amo')) {
                $table->boolean('subject_to_amo')->default(false)->after('subject_to_cnss');
            }
            if (! Schema::hasColumn('payslip_lines', 'subject_to_ir')) {
                $table->boolean('subject_to_ir')->default(false)->after('subject_to_amo');
            }
            if (! Schema::hasColumn('payslip_lines', 'is_tax_exempt')) {
                $table->boolean('is_tax_exempt')->default(false)->after('subject_to_ir');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_item_rules');
        Schema::dropIfExists('payroll_items');
    }
};
