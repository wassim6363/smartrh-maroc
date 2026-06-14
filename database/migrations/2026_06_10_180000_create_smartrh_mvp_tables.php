<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('legal_name')->nullable();
            $table->string('ice')->nullable()->index();
            $table->string('rc')->nullable();
            $table->string('if')->nullable();
            $table->string('cnss_number')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Morocco');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'name']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->decimal('min_salary', 12, 2)->nullable();
            $table->decimal('max_salary', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'title']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_number')->index();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('cin')->nullable()->index();
            $table->string('cnss_number')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('contract_type')->default('cdi');
            $table->decimal('base_salary', 12, 2)->default(0);
            $table->decimal('working_hours_per_month', 8, 2)->default(191);
            $table->string('status')->default('active')->index();
            $table->text('address')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'employee_number']);
        });

        Schema::create('employee_bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('bank_name');
            $table->string('rib', 24);
            $table->string('swift')->nullable();
            $table->boolean('is_primary')->default(true);
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->string('file_path')->nullable();
            $table->date('expires_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('contract_type')->default('cdi');
            $table->longText('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number')->index();
            $table->string('type')->default('cdi');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->decimal('salary', 12, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->longText('content')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'contract_number']);
        });

        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_paid')->default(true);
            $table->decimal('annual_days', 6, 2)->default(0);
            $table->timestamps();
            $table->unique(['company_id', 'code']);
        });

        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->restrictOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->decimal('days', 6, 2);
            $table->string('status')->default('pending')->index();
            $table->text('reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('absences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('hours', 6, 2)->default(0);
            $table->string('type')->default('unjustified');
            $table->text('reason')->nullable();
            $table->timestamps();
        });

        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->date('payment_date')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->unique(['company_id', 'starts_at', 'ends_at']);
        });

        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('default_working_hours', 8, 2)->default(191);
            $table->decimal('minimum_wage', 12, 2)->default(0);
            $table->boolean('include_cnss')->default(true);
            $table->boolean('include_amo')->default(true);
            $table->boolean('include_ir')->default(true);
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('cnss_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->default('CNSS');
            $table->decimal('employee_rate', 8, 4);
            $table->decimal('employer_rate', 8, 4);
            $table->decimal('salary_ceiling', 12, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('amo_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name')->default('AMO');
            $table->decimal('employee_rate', 8, 4);
            $table->decimal('employer_rate', 8, 4);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('ir_brackets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('min_amount', 12, 2);
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->decimal('rate', 8, 4);
            $table->decimal('deduction', 12, 2)->default(0);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('professional_expense_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->decimal('rate', 8, 4);
            $table->decimal('monthly_ceiling', 12, 2)->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->timestamps();
        });

        Schema::create('payslips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_period_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->index();
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('taxable_salary', 12, 2)->default(0);
            $table->decimal('total_employee_deductions', 12, 2)->default(0);
            $table->decimal('total_employer_contributions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            $table->string('status')->default('draft')->index();
            $table->json('calculation_snapshot')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'payroll_period_id', 'employee_id']);
            $table->unique(['company_id', 'reference']);
        });

        Schema::create('payslip_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payslip_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('code');
            $table->string('label');
            $table->decimal('base', 12, 2)->default(0);
            $table->decimal('rate', 8, 4)->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('generated_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payslip_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->string('file_path');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('generated_documents');
        Schema::dropIfExists('payslip_lines');
        Schema::dropIfExists('payslips');
        Schema::dropIfExists('professional_expense_rates');
        Schema::dropIfExists('ir_brackets');
        Schema::dropIfExists('amo_rates');
        Schema::dropIfExists('cnss_rates');
        Schema::dropIfExists('payroll_settings');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('absences');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employee_bank_accounts');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('companies');
    }
};
