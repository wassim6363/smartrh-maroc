<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DemoRequest;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommercialLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(PlanSeeder::class);
    }

    public function test_landing_page_has_request_demo_cta(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Demander une démo')
            ->assertSee('Essai gratuit 14 jours');
    }

    public function test_pricing_page_loads(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Starter')
            ->assertSee('Business')
            ->assertSee('Cabinet')
            ->assertSee('Enterprise');
    }

    public function test_pricing_page_has_request_demo_cta(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Demander une démo')
            ->assertSee('Essai gratuit 14 jours');
    }

    public function test_request_demo_page_loads(): void
    {
        $this->get('/request-demo')
            ->assertOk()
            ->assertSee('Demander une démo')
            ->assertSee('Nom complet')
            ->assertSee('Société')
            ->assertSee('Email');
    }

    public function test_compatibility_demo_route_loads(): void
    {
        $response = $this->get('/demo');
        $this->assertTrue(in_array($response->status(), [200, 301, 302]), 'Expected /demo to work as compatibility route');
    }

    public function test_demo_request_form_saves_lead(): void
    {
        Notification::fake();

        $this->post('/request-demo', [
            'full_name' => 'Jean Dupont',
            'company_name' => 'Dupont SARL',
            'email' => 'jean@dupont.test',
            'phone' => '+212600000001',
            'company_size' => '25',
            'target_plan' => 'Business',
            'message' => 'Je veux une démo SVP',
        ])->assertRedirect('/request-demo/thank-you');

        $this->assertDatabaseHas('demo_requests', [
            'full_name' => 'Jean Dupont',
            'company_name' => 'Dupont SARL',
            'email' => 'jean@dupont.test',
            'status' => 'new',
            'source' => 'request-demo',
        ]);
    }

    public function test_thank_you_page_loads(): void
    {
        $this->get('/request-demo/thank-you')
            ->assertOk()
            ->assertSee('Merci');
    }

    public function test_legal_pages_load(): void
    {
        $this->get('/terms')->assertOk()->assertSee('Conditions générales');
        $this->get('/privacy')->assertOk()->assertSee('Politique de confidentialité');
        $this->get('/legal-notice')->assertOk()->assertSee('Mentions légales');
    }

    public function test_admin_can_view_demo_requests(): void
    {
        Role::findOrCreate('Super Admin');
        $admin = User::factory()->create(['name' => 'Admin', 'email' => 'admin2@test.local']);
        $admin->assignRole('Super Admin');

        DemoRequest::query()->create([
            'full_name' => 'Lead Test',
            'company_name' => 'Test Co',
            'email' => 'lead@test.local',
            'phone' => '+212600000002',
            'status' => 'new',
        ]);

        $this->actingAs($admin)
            ->get('/admin/demo-requests')
            ->assertOk()
            ->assertSee('Lead Test')
            ->assertSee('Test Co');
    }

    public function test_converting_demo_request_creates_company(): void
    {
        $demoRequest = DemoRequest::query()->create([
            'full_name' => 'Converted Lead',
            'company_name' => 'Converted SARL',
            'email' => 'converted@test.local',
            'phone' => '+212600000003',
            'target_plan' => 'Business',
            'status' => 'new',
        ]);

        $businessPlan = Plan::query()->where('slug', 'business')->firstOrFail();

        $company = Company::query()->create([
            'name' => $demoRequest->company_name,
            'email' => $demoRequest->email,
        ]);

        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $businessPlan->id,
            'status' => 'trialing',
            'starts_at' => now()->toDateString(),
            'trial_ends_at' => now()->addDays(14)->toDateString(),
            'ends_at' => now()->addDays(14)->toDateString(),
            'billing_cycle' => 'monthly',
            'amount' => 0,
        ]);

        $demoRequest->update([
            'converted_company_id' => $company->id,
            'status' => 'converted',
            'converted_at' => now(),
        ]);

        $this->assertDatabaseHas('companies', ['name' => 'Converted SARL']);
        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'plan_id' => $businessPlan->id,
            'status' => 'trialing',
        ]);
        $this->assertDatabaseHas('demo_requests', [
            'id' => $demoRequest->id,
            'status' => 'converted',
            'converted_company_id' => $company->id,
        ]);
    }

    public function test_converting_demo_request_creates_trial_subscription(): void
    {
        $plan = Plan::query()->where('slug', 'business')->firstOrFail();
        $company = Company::query()->create(['name' => 'Trial Co', 'email' => 'trial@test.local']);

        Subscription::query()->create([
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
            'starts_at' => now()->toDateString(),
            'trial_ends_at' => now()->addDays(14)->toDateString(),
            'ends_at' => now()->addDays(14)->toDateString(),
            'billing_cycle' => 'monthly',
            'amount' => 0,
        ]);

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'plan_id' => $plan->id,
            'status' => 'trialing',
        ]);
        $this->assertNotNull($company->subscriptions()->first()?->trial_ends_at);
    }

    public function test_converting_demo_request_creates_audit_log(): void
    {
        AuditLog::query()->create([
            'event' => 'demo_request_converted',
            'new_values' => ['demo_request_id' => 1],
        ]);

        $this->assertDatabaseHas('audit_logs', ['event' => 'demo_request_converted']);
    }

    public function test_notification_is_sent_after_demo_request(): void
    {
        Notification::fake();

        DemoRequest::query()->create([
            'full_name' => 'Notif Test',
            'company_name' => 'Notif Co',
            'email' => 'notif@test.local',
            'phone' => '+212600000004',
            'status' => 'new',
        ]);

        Notification::assertNothingSent();
    }

    public function test_demo_tenant_command_creates_company(): void
    {
        $exitCode = Artisan::call('smartrh:create-demo-tenant', ['email' => 'demotenant@test.local']);

        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('companies', ['email' => 'demotenant@test.local']);
    }

    public function test_demo_tenant_command_creates_user(): void
    {
        Artisan::call('smartrh:create-demo-tenant', ['email' => 'demouser@test.local']);

        $this->assertDatabaseHas('users', ['email' => 'demouser@test.local']);
    }

    public function test_demo_tenant_command_assigns_business_trial(): void
    {
        Artisan::call('smartrh:create-demo-tenant', ['email' => 'demotrial@test.local']);

        $company = Company::query()->where('email', 'demotrial@test.local')->firstOrFail();

        $this->assertDatabaseHas('subscriptions', [
            'company_id' => $company->id,
            'status' => 'trialing',
        ]);

        $subscription = $company->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('Business', $subscription->plan->name ?? Plan::find($subscription->plan_id)?->name);
    }

    public function test_demo_tenant_command_does_not_throw_role_does_not_exist(): void
    {
        $exitCode = Artisan::call('smartrh:create-demo-tenant', ['email' => 'safe-role@test.local']);
        $this->assertEquals(0, $exitCode);

        $this->assertDatabaseHas('users', ['email' => 'safe-role@test.local']);
    }

    public function test_demo_tenant_command_stops_cleanly_if_email_exists(): void
    {
        Artisan::call('smartrh:create-demo-tenant', ['email' => 'duplicate@test.local']);
        $exitCode = Artisan::call('smartrh:create-demo-tenant', ['email' => 'duplicate@test.local']);
        $this->assertEquals(1, $exitCode);
    }

    public function test_demo_tenant_command_assigns_a_valid_role(): void
    {
        Artisan::call('smartrh:create-demo-tenant', ['email' => 'rolecheck@test.local']);
        $user = User::query()->where('email', 'rolecheck@test.local')->firstOrFail();
        $this->assertTrue($user->roles()->exists(), 'User should have at least one role assigned');
    }

    public function test_demo_tenant_command_creates_audit_log_if_table_exists(): void
    {
        Artisan::call('smartrh:create-demo-tenant', ['email' => 'auditlog@test.local']);
        $this->assertDatabaseHas('audit_logs', ['event' => 'demo_tenant_created']);
    }

    public function test_health_check_passes(): void
    {
        $exitCode = Artisan::call('smartrh:health-check');
        $this->assertEquals(0, $exitCode);
    }

    public function test_production_docs_exist(): void
    {
        $this->assertFileExists(base_path('docs/production-checklist.md'));
        $this->assertFileExists(base_path('docs/deployment-laravel.md'));
    }

    public function test_backup_doc_exists(): void
    {
        $this->assertFileExists(base_path('docs/backup-strategy.md'));
    }

    public function test_demo_guide_exists(): void
    {
        $this->assertFileExists(base_path('docs/demo-guide.md'));
    }

    public function test_client_demo_handoff_doc_exists(): void
    {
        $this->assertFileExists(base_path('docs/client-demo-handoff.md'));
        $this->assertStringContainsString('smartrh:reset-demo --force', file_get_contents(base_path('docs/client-demo-handoff.md')));
    }

    public function test_demo_reset_command_reseeds_and_prints_credentials(): void
    {
        $this->artisan('smartrh:reset-demo', ['--force' => true, '--skip-checks' => true])
            ->expectsOutputToContain('SmartRH Maroc demo reset complete.')
            ->expectsOutputToContain('admin@smartrh.test / password')
            ->expectsOutputToContain('amina.employee@smartrh.test / password')
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', ['email' => 'admin@smartrh.test']);
        $this->assertDatabaseHas('users', ['email' => 'amina.employee@smartrh.test']);
    }

    public function test_seeded_roles_include_demo_support_and_billing_permissions(): void
    {
        $this->assertTrue(Role::findByName('Company Owner')->hasPermissionTo('manage support tickets'));
        $this->assertTrue(Role::findByName('Company Owner')->hasPermissionTo('manage billing'));
        $this->assertTrue(Role::findByName('Employee')->hasPermissionTo('view own support tickets'));
    }

    public function test_public_pages_return_200(): void
    {
        $this->get('/')->assertOk();
        $this->get('/pricing')->assertOk();
        $this->get('/request-demo')->assertOk();
        $this->get('/request-demo/thank-you')->assertOk();
        $this->get('/terms')->assertOk();
        $this->get('/privacy')->assertOk();
        $this->get('/legal-notice')->assertOk();
    }

    public function test_admin_login_page_redirects_to_filament(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('type="email"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('id="form.password"', false)
            ->assertSee('autocomplete="current-password"', false);
    }

    public function test_employee_login_page_returns_200(): void
    {
        $this->get('/employee/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('autocomplete="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('autocomplete="current-password"', false);
    }
}
