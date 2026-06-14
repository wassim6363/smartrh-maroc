<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('employee_document_requests')) {
            Schema::create('employee_document_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->string('type')->index();
                $table->string('title');
                $table->text('message')->nullable();
                $table->string('status')->default('pending')->index();
                $table->text('response_message')->nullable();
                $table->foreignId('generated_document_id')->nullable()->constrained('generated_documents')->nullOnDelete();
                $table->timestamp('requested_at');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'employee_id', 'status']);
            });
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action')->nullable()->after('event')->index();
            }

            if (! Schema::hasColumn('audit_logs', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }

            if (! Schema::hasColumn('audit_logs', 'metadata')) {
                $table->json('metadata')->nullable()->after('new_values');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_requests');

        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'employee_id')) {
                $table->dropConstrainedForeignId('employee_id');
            }

            foreach (['action', 'metadata'] as $column) {
                if (Schema::hasColumn('audit_logs', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
