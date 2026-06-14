<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_payroll_items', function (Blueprint $table) {
            if (! Schema::hasColumn('employee_payroll_items', 'subject_to_cnss')) {
                $table->boolean('subject_to_cnss')->default(true)->after('taxable');
            }
            if (! Schema::hasColumn('employee_payroll_items', 'subject_to_amo')) {
                $table->boolean('subject_to_amo')->default(true)->after('subject_to_cnss');
            }
            if (! Schema::hasColumn('employee_payroll_items', 'subject_to_ir')) {
                $table->boolean('subject_to_ir')->default(true)->after('subject_to_amo');
            }
            if (! Schema::hasColumn('employee_payroll_items', 'is_tax_exempt')) {
                $table->boolean('is_tax_exempt')->default(false)->after('subject_to_ir');
            }
        });

        DB::table('employee_payroll_items')
            ->whereIn('type', ['indemnity_non_taxable'])
            ->update([
                'subject_to_cnss' => false,
                'subject_to_amo' => false,
                'subject_to_ir' => false,
                'is_tax_exempt' => true,
            ]);

        DB::table('employee_payroll_items')
            ->whereIn('type', ['advance', 'deduction', 'other'])
            ->update([
                'subject_to_cnss' => false,
                'subject_to_amo' => false,
                'subject_to_ir' => false,
            ]);

        Schema::table('legal_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('legal_settings', 'professional_expenses_base')) {
                $table->string('professional_expenses_base')->default('taxable_after_contributions')->after('professional_expenses_ceiling');
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_settings', function (Blueprint $table) {
            if (Schema::hasColumn('legal_settings', 'professional_expenses_base')) {
                $table->dropColumn('professional_expenses_base');
            }
        });

        Schema::table('employee_payroll_items', function (Blueprint $table) {
            foreach (['subject_to_cnss', 'subject_to_amo', 'subject_to_ir', 'is_tax_exempt'] as $column) {
                if (Schema::hasColumn('employee_payroll_items', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
