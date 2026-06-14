<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demo_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('demo_requests', 'company_size')) {
                $table->string('company_size')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('demo_requests', 'target_plan')) {
                $table->string('target_plan')->nullable()->after('company_size');
            }
            if (! Schema::hasColumn('demo_requests', 'source')) {
                $table->string('source')->nullable()->after('status');
            }
            if (! Schema::hasColumn('demo_requests', 'converted_company_id')) {
                $table->foreignId('converted_company_id')->nullable()->after('source')->constrained('companies')->nullOnDelete();
            }
            if (! Schema::hasColumn('demo_requests', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')->nullable()->after('converted_company_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('demo_requests', 'contacted_at')) {
                $table->dateTime('contacted_at')->nullable()->after('assigned_to_user_id');
            }
            if (! Schema::hasColumn('demo_requests', 'converted_at')) {
                $table->dateTime('converted_at')->nullable()->after('contacted_at');
            }
        });
    }

    public function down(): void
    {
        // The base demo_requests table belongs to an earlier migration.
    }
};
