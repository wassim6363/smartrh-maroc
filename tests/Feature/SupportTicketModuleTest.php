<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Employee;
use App\Models\SupportTicket;
use App\Models\SupportTicketReply;
use App\Models\User;
use App\Notifications\SupportTicketCreatedNotification;
use App\Services\Support\SupportTicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SupportTicketModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_create_support_ticket(): void
    {
        Notification::fake();
        config(['mail.from.address' => 'support@smartrh.test', 'smartrh.support_email' => 'admin@smartrh.test']);
        [$employee] = $this->employees();

        $this->actingAs($employee->user)
            ->post(route('employee.support.store'), [
                'subject' => 'Problème bulletin',
                'category' => 'payroll',
                'priority' => 'normal',
                'message' => 'Je ne retrouve pas mon bulletin.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('support_tickets', [
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'subject' => 'Problème bulletin',
            'status' => 'open',
        ]);
    }

    public function test_employee_can_list_own_tickets(): void
    {
        [$employeeA, $employeeB] = $this->employees();
        $own = $this->ticket($employeeA, 'Mon ticket');
        $this->ticket($employeeB, 'Ticket autre salarié');

        $this->actingAs($employeeA->user)
            ->get(route('employee.support'))
            ->assertOk()
            ->assertSee($own->subject)
            ->assertDontSee('Ticket autre salarié');
    }

    public function test_employee_cannot_view_another_employee_ticket(): void
    {
        [$employeeA, $employeeB] = $this->employees();
        $ticket = $this->ticket($employeeB, 'Ticket privé');

        $this->actingAs($employeeA->user)
            ->get(route('employee.support.show', $ticket))
            ->assertForbidden();
    }

    public function test_employee_can_reply_to_own_ticket(): void
    {
        [$employee] = $this->employees();
        $ticket = $this->ticket($employee, 'Réponse possible');

        $this->actingAs($employee->user)
            ->post(route('employee.support.reply', $ticket), ['message' => 'Merci pour le retour.'])
            ->assertRedirect();

        $this->assertDatabaseHas('support_ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'employee_id' => $employee->id,
            'message' => 'Merci pour le retour.',
            'is_internal' => false,
        ]);
    }

    public function test_employee_cannot_see_internal_notes(): void
    {
        [$employee] = $this->employees();
        $ticket = $this->ticket($employee, 'Notes internes');
        SupportTicketReply::query()->create([
            'support_ticket_id' => $ticket->id,
            'message' => 'Réponse publique',
            'is_internal' => false,
        ]);
        SupportTicketReply::query()->create([
            'support_ticket_id' => $ticket->id,
            'message' => 'Note interne support',
            'is_internal' => true,
        ]);

        $this->actingAs($employee->user)
            ->get(route('employee.support.show', $ticket))
            ->assertOk()
            ->assertSee('Réponse publique')
            ->assertDontSee('Note interne support');
    }

    public function test_company_admin_can_view_company_tickets(): void
    {
        [$employee] = $this->employees();
        $admin = $this->admin($employee->company_id);
        $ticket = $this->ticket($employee, 'Ticket société');

        $this->assertTrue(Gate::forUser($admin)->allows('view', $ticket));
    }

    public function test_company_admin_cannot_view_another_company_ticket(): void
    {
        [$employeeA, $employeeB] = $this->employeesInDifferentCompanies();
        $admin = $this->admin($employeeA->company_id);
        $ticket = $this->ticket($employeeB, 'Ticket autre société');

        $this->assertFalse(Gate::forUser($admin)->allows('view', $ticket));
    }

    public function test_super_admin_can_view_all_tickets(): void
    {
        [$employeeA, $employeeB] = $this->employeesInDifferentCompanies();
        $superAdmin = $this->superAdmin();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $this->ticket($employeeA, 'A')));
        $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $this->ticket($employeeB, 'B')));
    }

    public function test_admin_can_add_internal_note(): void
    {
        [$employee] = $this->employees();
        $admin = $this->admin($employee->company_id);
        $ticket = $this->ticket($employee, 'Note admin');

        app(SupportTicketService::class)->addReply($ticket, 'Diagnostic interne', $admin, null, true);

        $this->assertDatabaseHas('support_ticket_replies', [
            'support_ticket_id' => $ticket->id,
            'message' => 'Diagnostic interne',
            'is_internal' => true,
        ]);
    }

    public function test_admin_can_change_ticket_status(): void
    {
        [$employee] = $this->employees();
        $ticket = $this->ticket($employee, 'Statut admin');

        app(SupportTicketService::class)->changeStatus($ticket, 'in_progress');

        $this->assertSame('in_progress', $ticket->refresh()->status);
        $this->assertDatabaseHas('audit_logs', ['action' => 'support_ticket_status_changed']);
    }

    public function test_audit_log_created_when_ticket_is_created(): void
    {
        [$employee] = $this->employees();

        app(SupportTicketService::class)->createFromEmployee($employee, [
            'subject' => 'Audit ticket',
            'category' => 'technical',
            'priority' => 'normal',
            'message' => 'Audit message',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'support_ticket_created',
            'employee_id' => $employee->id,
        ]);
    }

    public function test_notification_sent_when_ticket_is_created(): void
    {
        Notification::fake();
        config(['mail.from.address' => 'support@smartrh.test', 'smartrh.support_email' => 'admin@smartrh.test']);
        [$employee] = $this->employees();

        app(SupportTicketService::class)->createFromEmployee($employee, [
            'subject' => 'Notification ticket',
            'category' => 'technical',
            'priority' => 'normal',
            'message' => 'Notifier support',
        ]);

        Notification::assertSentOnDemand(SupportTicketCreatedNotification::class);
    }

    public function test_support_filament_resource_loads(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)->get('/admin/support-tickets')->assertOk();
    }

    public function test_employee_support_redirects_to_login_when_guest(): void
    {
        $this->get(route('employee.support'))
            ->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertRedirect(route('employee.login'));
    }

    public function test_health_check_passes(): void
    {
        $this->artisan('smartrh:health-check')->assertExitCode(0);
    }

    private function employees(): array
    {
        Role::findOrCreate('Employee');

        $company = Company::query()->create(['name' => 'Support Test']);

        return [
            $this->employee($company, 'amina-support@test.local', 'EMP-SUP-A'),
            $this->employee($company, 'youssef-support@test.local', 'EMP-SUP-B'),
        ];
    }

    private function employeesInDifferentCompanies(): array
    {
        Role::findOrCreate('Employee');

        return [
            $this->employee(Company::query()->create(['name' => 'Support A']), 'support-a@test.local', 'SUP-A'),
            $this->employee(Company::query()->create(['name' => 'Support B']), 'support-b@test.local', 'SUP-B'),
        ];
    }

    private function employee(Company $company, string $email, string $number): Employee
    {
        $user = User::query()->create(['name' => $number, 'email' => $email, 'password' => 'password']);
        $user->assignRole('Employee');

        return Employee::query()->create([
            'company_id' => $company->id,
            'user_id' => $user->id,
            'employee_number' => $number,
            'first_name' => 'Support',
            'last_name' => $number,
            'email' => $email,
            'hire_date' => now()->toDateString(),
            'base_salary' => 6000,
        ])->refresh();
    }

    private function ticket(Employee $employee, string $subject): SupportTicket
    {
        return SupportTicket::query()->create([
            'company_id' => $employee->company_id,
            'user_id' => $employee->user_id,
            'employee_id' => $employee->id,
            'subject' => $subject,
            'category' => 'technical',
            'priority' => 'normal',
            'status' => 'open',
            'message' => 'Message initial',
        ]);
    }

    private function admin(int $companyId): User
    {
        Role::findOrCreate('Company Owner');
        $admin = User::query()->create([
            'company_id' => $companyId,
            'name' => 'Support Admin',
            'email' => 'support-admin-' . $companyId . '@test.local',
            'password' => 'password',
        ]);
        $admin->assignRole('Company Owner');

        return $admin;
    }

    private function superAdmin(): User
    {
        Role::findOrCreate('Super Admin');
        $admin = User::query()->create([
            'name' => 'Super Support',
            'email' => 'super-support-' . uniqid() . '@test.local',
            'password' => 'password',
        ]);
        $admin->assignRole('Super Admin');

        return $admin;
    }
}
