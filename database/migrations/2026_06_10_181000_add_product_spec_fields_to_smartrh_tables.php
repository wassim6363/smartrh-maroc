<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('email');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->string('family_situation')->nullable()->after('contract_type');
            $table->unsignedTinyInteger('dependents_count')->default(0)->after('family_situation');
            $table->date('probation_ends_at')->nullable()->after('hire_date');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['family_situation', 'dependents_count', 'probation_ends_at']);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('logo_path');
        });
    }
};
