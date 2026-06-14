<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            if (! Schema::hasColumn('invoices', 'currency')) {
                $table->string('currency', 3)->default('MAD')->after('amount');
            }
            if (! Schema::hasColumn('invoices', 'billing_period_start')) {
                $table->date('billing_period_start')->nullable()->after('status');
            }
            if (! Schema::hasColumn('invoices', 'billing_period_end')) {
                $table->date('billing_period_end')->nullable()->after('billing_period_start');
            }
            if (! Schema::hasColumn('invoices', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('paid_at');
            }
        });

        if (! Schema::hasTable('payments')) {
            Schema::create('payments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
                $table->decimal('amount', 12, 2);
                $table->string('currency', 3)->default('MAD');
                $table->string('provider')->nullable();
                $table->string('provider_reference')->nullable();
                $table->string('status')->default('pending')->index();
                $table->dateTime('paid_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
