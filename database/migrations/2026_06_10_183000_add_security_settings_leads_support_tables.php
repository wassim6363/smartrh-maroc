<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });

        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('default_working_days')->default(26)->after('minimum_wage');
            $table->unsignedTinyInteger('payroll_closing_day')->default(25)->after('default_working_days');
            $table->string('currency', 3)->default('MAD')->after('payroll_closing_day');
            $table->string('payslip_number_prefix')->default('BP')->after('currency');
            $table->string('document_number_prefix')->default('DOC')->after('payslip_number_prefix');
            $table->string('email_sender_name')->nullable()->after('document_number_prefix');
            $table->string('email_sender_address')->nullable()->after('email_sender_name');
            $table->string('default_language')->default('fr')->after('email_sender_address');
            $table->string('timezone')->default('Africa/Casablanca')->after('default_language');
        });

        Schema::create('demo_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('company_name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('business_type')->nullable();
            $table->unsignedInteger('employees_count')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject');
            $table->string('category')->default('Autre');
            $table->string('priority')->default('normal');
            $table->string('status')->default('open')->index();
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('demo_requests');

        Schema::table('payroll_settings', function (Blueprint $table) {
            $table->dropColumn([
                'default_working_days',
                'payroll_closing_day',
                'currency',
                'payslip_number_prefix',
                'document_number_prefix',
                'email_sender_name',
                'email_sender_address',
                'default_language',
                'timezone',
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
