<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            if (! Schema::hasColumn('plans', 'slug')) {
                $table->string('slug')->nullable()->unique()->after('name');
            }
            if (! Schema::hasColumn('plans', 'description')) {
                $table->text('description')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('plans', 'yearly_price')) {
                $table->decimal('yearly_price', 12, 2)->nullable()->after('monthly_price');
            }
            if (! Schema::hasColumn('plans', 'max_companies')) {
                $table->unsignedInteger('max_companies')->nullable()->after('yearly_price');
            }
            if (! Schema::hasColumn('plans', 'max_employees')) {
                $table->unsignedInteger('max_employees')->nullable()->after('max_companies');
            }
            if (! Schema::hasColumn('plans', 'max_payslips_per_month')) {
                $table->unsignedInteger('max_payslips_per_month')->nullable()->after('max_employees');
            }
            if (! Schema::hasColumn('plans', 'max_contracts_per_month')) {
                $table->unsignedInteger('max_contracts_per_month')->nullable()->after('max_payslips_per_month');
            }
            foreach (['employee_portal_enabled', 'document_requests_enabled', 'audit_logs_enabled', 'api_access_enabled'] as $column) {
                if (! Schema::hasColumn('plans', $column)) {
                    $table->boolean($column)->default(false);
                }
            }
            if (! Schema::hasColumn('plans', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0);
            }
        });

        DB::table('plans')->orderBy('id')->get()->each(function ($plan): void {
            DB::table('plans')->where('id', $plan->id)->update([
                'slug' => $plan->slug ?: Str::slug($plan->name),
                'max_companies' => $plan->max_companies ?? $plan->company_limit,
                'max_employees' => $plan->max_employees ?? $plan->employee_limit,
                'max_payslips_per_month' => $plan->max_payslips_per_month ?? 100,
                'max_contracts_per_month' => $plan->max_contracts_per_month ?? 50,
                'employee_portal_enabled' => $plan->employee_portal_enabled ?? true,
                'document_requests_enabled' => $plan->document_requests_enabled ?? false,
                'audit_logs_enabled' => $plan->audit_logs_enabled ?? true,
                'api_access_enabled' => $plan->api_access_enabled ?? false,
            ]);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            if (! Schema::hasColumn('subscriptions', 'trial_ends_at')) {
                $table->date('trial_ends_at')->nullable()->after('starts_at');
            }
            if (! Schema::hasColumn('subscriptions', 'billing_cycle')) {
                $table->string('billing_cycle')->default('monthly')->after('ends_at');
            }
            if (! Schema::hasColumn('subscriptions', 'current_period_start')) {
                $table->date('current_period_start')->nullable()->after('billing_cycle');
            }
            if (! Schema::hasColumn('subscriptions', 'current_period_end')) {
                $table->date('current_period_end')->nullable()->after('current_period_start');
            }
        });

        DB::table('subscriptions')->where('status', 'trial')->update(['status' => 'trialing']);
        DB::table('subscriptions')->whereNull('current_period_start')->update(['current_period_start' => DB::raw('starts_at')]);
        DB::table('subscriptions')->whereNull('current_period_end')->update(['current_period_end' => DB::raw('ends_at')]);

        if (! Schema::hasTable('subscription_usage')) {
            Schema::create('subscription_usage', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subscription_id')->constrained('subscriptions')->cascadeOnDelete();
                $table->unsignedInteger('period_year');
                $table->unsignedTinyInteger('period_month');
                $table->unsignedInteger('employees_count')->default(0);
                $table->unsignedInteger('payslips_generated')->default(0);
                $table->unsignedInteger('contracts_generated')->default(0);
                $table->unsignedInteger('documents_generated')->default(0);
                $table->timestamps();
                $table->unique(['company_id', 'subscription_id', 'period_year', 'period_month'], 'subscription_usage_period_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_usage');
    }
};
