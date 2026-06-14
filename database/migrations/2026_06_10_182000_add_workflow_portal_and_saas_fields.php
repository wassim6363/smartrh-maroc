<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->decimal('seniority_bonus', 12, 2)->default(0)->after('base_salary');
            $table->decimal('transport_bonus', 12, 2)->default(0)->after('seniority_bonus');
            $table->decimal('meal_bonus', 12, 2)->default(0)->after('transport_bonus');
            $table->decimal('performance_bonus', 12, 2)->default(0)->after('meal_bonus');
            $table->decimal('overtime_amount', 12, 2)->default(0)->after('performance_bonus');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->foreignId('validated_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable()->after('validated_by');
            $table->timestamp('sent_at')->nullable()->after('validated_at');
        });

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->foreignId('generated_by')->nullable()->after('payslip_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('monthly_price', 12, 2);
            $table->unsignedInteger('employee_limit')->nullable();
            $table->unsignedInteger('company_limit')->nullable();
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('trial')->index();
            $table->date('starts_at');
            $table->date('ends_at')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->index();
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('draft')->index();
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->date('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'invoice_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');

        Schema::table('generated_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_by');
        });

        Schema::table('payslips', function (Blueprint $table) {
            $table->dropConstrainedForeignId('validated_by');
            $table->dropColumn(['validated_at', 'sent_at']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'seniority_bonus',
                'transport_bonus',
                'meal_bonus',
                'performance_bonus',
                'overtime_amount',
            ]);
        });
    }
};
