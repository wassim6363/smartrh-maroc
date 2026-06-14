<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_templates', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_templates', 'type')) {
                $table->string('type')->default('CDI')->after('company_id')->index();
            }
            if (! Schema::hasColumn('contract_templates', 'title')) {
                $table->string('title')->nullable()->after('type');
            }
            if (! Schema::hasColumn('contract_templates', 'language')) {
                $table->string('language', 2)->default('fr')->after('title')->index();
            }
            if (! Schema::hasColumn('contract_templates', 'content_html')) {
                $table->longText('content_html')->nullable()->after('language');
            }
            if (! Schema::hasColumn('contract_templates', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('content_html')->index();
            }
        });

        if (! Schema::hasTable('employee_contracts')) {
            Schema::create('employee_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->constrained()->cascadeOnDelete();
                $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
                $table->foreignId('contract_template_id')->nullable()->constrained()->nullOnDelete();
                $table->string('type')->index();
                $table->string('reference')->unique();
                $table->string('title');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->decimal('salary', 12, 2)->nullable();
                $table->string('job_title')->nullable();
                $table->string('city')->nullable();
                $table->string('status')->default('draft')->index();
                $table->longText('content_html')->nullable();
                $table->string('pdf_path')->nullable();
                $table->string('signed_pdf_path')->nullable();
                $table->timestamp('generated_at')->nullable();
                $table->timestamp('signed_at')->nullable();
                $table->timestamps();
                $table->index(['company_id', 'employee_id', 'type']);
            });
        }

        Schema::table('generated_documents', function (Blueprint $table) {
            if (! Schema::hasColumn('generated_documents', 'documentable_type')) {
                $table->string('documentable_type')->nullable()->after('employee_id');
            }
            if (! Schema::hasColumn('generated_documents', 'documentable_id')) {
                $table->unsignedBigInteger('documentable_id')->nullable()->after('documentable_type');
            }
            if (! Schema::hasColumn('generated_documents', 'reference')) {
                $table->string('reference')->nullable()->unique()->after('title');
            }
            if (! Schema::hasColumn('generated_documents', 'content_html')) {
                $table->longText('content_html')->nullable()->after('reference');
            }
            if (! Schema::hasColumn('generated_documents', 'pdf_path')) {
                $table->string('pdf_path')->nullable()->after('content_html');
            }
            if (! Schema::hasColumn('generated_documents', 'status')) {
                $table->string('status')->default('generated')->index()->after('pdf_path');
            }
            if (! Schema::hasColumn('generated_documents', 'generated_at')) {
                $table->timestamp('generated_at')->nullable()->after('status');
            }

            if (Schema::hasColumn('generated_documents', 'documentable_type') && Schema::hasColumn('generated_documents', 'documentable_id')) {
                $table->index(['documentable_type', 'documentable_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_contracts');
    }
};
