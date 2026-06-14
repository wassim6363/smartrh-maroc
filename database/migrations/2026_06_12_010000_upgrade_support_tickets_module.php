<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table): void {
            if (! Schema::hasColumn('support_tickets', 'employee_id')) {
                $table->foreignId('employee_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('support_tickets', 'assigned_to_user_id')) {
                $table->foreignId('assigned_to_user_id')->nullable()->after('message')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('support_tickets', 'resolved_at')) {
                $table->dateTime('resolved_at')->nullable()->after('assigned_to_user_id');
            }
            if (! Schema::hasColumn('support_tickets', 'closed_at')) {
                $table->dateTime('closed_at')->nullable()->after('resolved_at');
            }
            $table->index('company_id', 'support_tickets_company_id_index');
            $table->index('user_id', 'support_tickets_user_id_index');
            $table->index('employee_id', 'support_tickets_employee_id_index');
            $table->index('priority', 'support_tickets_priority_index');
            $table->index('category', 'support_tickets_category_index');
            $table->index('assigned_to_user_id', 'support_tickets_assigned_to_user_id_index');
        });

        if (! Schema::hasTable('support_ticket_replies')) {
            Schema::create('support_ticket_replies', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
                $table->longText('message');
                $table->boolean('is_internal')->default(false);
                $table->timestamps();
                $table->index('support_ticket_id');
                $table->index('user_id');
                $table->index('employee_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_replies');
    }
};
