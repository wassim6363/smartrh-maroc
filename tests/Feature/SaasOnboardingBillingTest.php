<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SubscriptionUsage;
use App\Models\SupportTicket;
use App\Models\User;
use App\Services\Saas\InvoicePaymentService;
use App\Services\Saas\SubscriptionManagementService;
use App\Services\Saas\SubscriptionLimitService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SaasOnboardingBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_pricing_page_loads(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Business')
            ->assertSee('Cabinet')
            ->assertSee('Enterprise');
    }

    public function test_onboarding_creates_company_and_trial_subscription(): void
    {
        $this->seed(PlanSeeder::class);
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();

        $this->post('/onboarding/company', [
            'name' => 'Onboarding SARL',
            'city' => 'Casablanca',
            'email' => 'contact@onboarding.test',
        ])->assertRedirect('/onboarding/plan');

        $this->post('/onboarding/plan', [
            'plan_id' => $plan->id,
        ])->assertRedirect('/onboarding/admin-user');

        $this->post('/onboarding/admin-user', [
            'name' => 'Owner Onboarding',
            'email' => 'owner-onboarding@test.local',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect('/onboarding/complete');

        $company = Company::query()->where('name', 'Onboarding SARL')->firstOrFail();

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
        ]);
        $this->assertNotNull($company->subscriptions()->first()?->trial_ends_at);
    }

    public function test_cannot_downgrade_if_usage_exceeds_new_plan(): void
    {
        $this->seed(PlanSeeder::class);
        $company = Company::query()->create(['name' => 'Downgrade Co']);
        $business = Plan::query()->where('slug', 'business')->firstOrFail();
        $starter = Plan::query()->where('slug', 'starter')->firstOrFail();
        $this->subscription($company, $business);

        for ($i = 1; $i <= 30; $i++) {
            Employee::query()->create([
                'company_id' => $company->id,
                'employee_number' => 'D-' . $i,
                'first_name' => 'Employee',
                'last_name' => (string) $i,
                'hire_date' => '2026-01-01',
                'base_salary' => 5000,
            ]);
        }

        $this->expectException(ValidationException::class);

        app(SubscriptionManagementService::class)->changePlan($company, $starter);
    }

    public function test_can_upgrade_from_starter_to_business(): void
    {
        $this->seed(PlanSeeder::class);
        $company = Company::query()->create(['name' => 'Upgrade Co']);
        $starter = Plan::query()->where('slug', 'starter')->firstOrFail();
        $business = Plan::query()->where('slug', 'business')->firstOrFail();
        $this->subscription($company, $starter);

        $subscription = app(SubscriptionManagementService::class)->changePlan($company, $business);

        $this->assertSame($business->id, $subscription->plan_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'subscription_plan_changed']);
    }

    public function test_invoice_generation_and_pdf_creation_work(): void
    {
        Storage::fake('local');
        $this->seed(PlanSeeder::class);
        $company = Company::query()->create(['name' => 'Invoice Co']);
        $business = Plan::query()->where('slug', 'business')->firstOrFail();
        $subscription = $this->subscription($company, $business);

        $invoice = app(SubscriptionManagementService::class)->generateInvoice($subscription);

        $this->assertSame($company->id, $invoice->company_id);
        $this->assertSame('pending', $invoice->status);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }

    public function test_subscription_action_service_creates_audit_log(): void
    {
        $this->seed(PlanSeeder::class);
        $company = Company::query()->create(['name' => 'Audit Trial Co']);
        $starter = Plan::query()->where('slug', 'starter')->firstOrFail();

        app(SubscriptionManagementService::class)->startTrial($company, $starter);

        $this->assertTrue(AuditLog::query()->where('action', 'subscription_trial_started')->exists());
    }

    public function test_admin_billing_resources_load(): void
    {
        $this->seed(PlanSeeder::class);
        Role::findOrCreate('Super Admin');
        $admin = User::query()->create([
            'name' => 'Billing Admin',
            'email' => 'billing-admin@test.local',
            'password' => 'password',
        ]);
        $admin->assignRole('Super Admin');

        foreach (['/admin/plans', '/admin/subscriptions', '/admin/invoices', '/admin/payments'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_admin_can_see_invoice_pdf_download_action(): void
    {
        $this->seed(PlanSeeder::class);
        Role::findOrCreate('Super Admin');
        $admin = User::query()->create([
            'name' => 'Invoice PDF Admin',
            'email' => 'invoice-pdf-admin@test.local',
            'password' => 'password',
        ]);
        $admin->assignRole('Super Admin');

        $company = Company::query()->create(['name' => 'Invoice Action Co']);
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $subscription = $this->subscription($company, $plan);
        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-ACTION-001',
            'amount' => 299,
            'currency' => 'MAD',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)
            ->get('/admin/invoices')
            ->assertOk()
            ->assertSee('Télécharger PDF');

        $this->actingAs($admin)
            ->get('/admin/invoices/' . $invoice->id . '/edit')
            ->assertOk()
            ->assertSee('Télécharger PDF');
    }

    public function test_invoice_pdf_download_generates_missing_pdf_and_downloads_clean_filename(): void
    {
        Storage::fake('local');
        $this->seed(PlanSeeder::class);
        Role::findOrCreate('Super Admin');
        $admin = User::query()->create([
            'name' => 'Invoice Download Admin',
            'email' => 'invoice-download-admin@test.local',
            'password' => 'password',
        ]);
        $admin->assignRole('Super Admin');

        $company = Company::query()->create(['name' => 'Invoice Download Co']);
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $subscription = $this->subscription($company, $plan);
        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-DOWNLOAD-001',
            'amount' => 299,
            'currency' => 'MAD',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)
            ->get(route('invoices.download', $invoice))
            ->assertOk()
            ->assertDownload('facture-INV-DOWNLOAD-001.pdf');

        $invoice->refresh();
        $this->assertSame('companies/' . $company->id . '/billing/invoices/facture-INV-DOWNLOAD-001.pdf', $invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }

    public function test_usage_warning_appears_when_usage_is_above_80_percent(): void
    {
        $this->seed(PlanSeeder::class);
        $company = Company::query()->create(['name' => 'Warning Co']);
        $starter = Plan::query()->where('slug', 'starter')->firstOrFail();
        $subscription = $this->subscription($company, $starter);

        for ($i = 1; $i <= 8; $i++) {
            Employee::query()->create([
                'company_id' => $company->id,
                'employee_number' => 'W-' . $i,
                'first_name' => 'Warning',
                'last_name' => (string) $i,
                'hire_date' => '2026-01-01',
                'base_salary' => 5000,
            ]);
        }

        SubscriptionUsage::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
            'employees_count' => 8,
        ]);

        $summary = app(SubscriptionLimitService::class)->getLimitsSummary($company);
        $this->assertSame(8, $summary['employees']['used']);
        $this->assertSame(10, $summary['employees']['limit']);

        $this->assertStringContainsString(
            'Utilisation actuelle de votre abonnement',
            file_get_contents(resource_path('views/filament/widgets/subscription-status-widget.blade.php')),
        );
    }

    public function test_admin_can_mark_invoice_as_paid(): void
    {
        $this->seed(PlanSeeder::class);
        $company = Company::query()->create(['name' => 'Paid Co']);
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $subscription = $this->subscription($company, $plan);
        $invoice = Invoice::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'invoice_number' => 'INV-PAID-001',
            'amount' => 299,
            'currency' => 'MAD',
            'status' => 'pending',
            'issued_at' => now(),
            'due_at' => now()->addDays(7),
        ]);

        $payment = app(InvoicePaymentService::class)->markPaid($invoice);

        $this->assertSame('paid', $invoice->refresh()->status);
        $this->assertSame($invoice->id, $payment->invoice_id);
        $this->assertDatabaseHas('payments', [
            'invoice_id' => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_admin_can_view_usage_demo_requests_audit_logs_and_support_tickets(): void
    {
        $this->seed(PlanSeeder::class);
        Role::findOrCreate('Super Admin');
        $admin = User::query()->create([
            'name' => 'SaaS Admin',
            'email' => 'saas-admin@test.local',
            'password' => 'password',
        ]);
        $admin->assignRole('Super Admin');

        $company = Company::query()->create(['name' => 'Admin Ready Co']);
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $subscription = $this->subscription($company, $plan);
        SubscriptionUsage::query()->create([
            'company_id' => $company->id,
            'subscription_id' => $subscription->id,
            'period_year' => now()->year,
            'period_month' => now()->month,
        ]);
        DemoRequest::query()->create([
            'full_name' => 'Lead Ready',
            'company_name' => 'Lead Co',
            'email' => 'lead-ready@test.local',
            'phone' => '0600000000',
            'status' => 'new',
        ]);
        AuditLog::query()->create([
            'company_id' => $company->id,
            'event' => 'admin_ready_test',
            'action' => 'admin_ready_test',
        ]);
        SupportTicket::query()->create([
            'company_id' => $company->id,
            'subject' => 'Support admin readiness',
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'open',
            'message' => 'Test',
        ]);

        foreach (['/admin/subscription-usages', '/admin/demo-requests', '/admin/audit-logs', '/admin/support-tickets'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    private function subscription(Company $company, Plan $plan): Subscription
    {
        return Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->startOfMonth()->toDateString(),
            'ends_at' => now()->addMonth()->endOfMonth()->toDateString(),
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->startOfMonth()->toDateString(),
            'current_period_end' => now()->endOfMonth()->toDateString(),
            'amount' => $plan->monthly_price,
        ]);
    }
}
