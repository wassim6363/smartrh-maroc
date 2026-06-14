<?php

namespace App\Console\Commands;

use App\Models\ContractTemplate;
use App\Models\EmployeeContract;
use App\Models\GeneratedDocument;
use App\Models\Invoice;
use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class SmartRhHealthCheck extends Command
{
    protected $signature = 'smartrh:health-check';
    protected $description = 'Check basic SmartRH Maroc production readiness.';

    public function handle(): int
    {
        $this->info('SmartRH Maroc health check');

        try {
            DB::connection()->getPdo();
            $this->info('[OK] Database connection');
        } catch (\Throwable $exception) {
            $this->error('[FAIL] Database connection: ' . $exception->getMessage());
        }

        $privateDisk = config('filesystems.private_disk');
        $this->line($this->privateDiskAccessible() ? '[OK] Private storage exists: ' . $privateDisk : '[WARN] Private storage missing: ' . $privateDisk);
        $this->line($this->privateDiskWritable() ? '[OK] Private storage writable: ' . $privateDisk : '[WARN] Private storage not writable: ' . $privateDisk);
        $this->line(LegalSetting::query()->where('active', true)->exists() ? '[OK] Active legal settings exist' : '[WARN] Active legal settings missing');
        $this->line(IrBracket::query()->where('active', true)->exists() ? '[OK] Active IR brackets exist' : '[WARN] Active IR brackets missing');
        $this->line(User::query()->where('email', 'admin@smartrh.test')->exists() ? '[OK] Admin user exists' : '[WARN] Admin user missing');
        $this->line(User::query()->whereHas('employees')->exists() ? '[OK] Employee portal user exists' : '[WARN] Employee portal user missing');
        $this->line(ContractTemplate::query()->where('is_active', true)->exists() ? '[OK] Contract templates exist' : '[WARN] Contract templates missing');
        $this->line(GeneratedDocument::query()->exists() ? '[OK] Generated documents exist' : '[WARN] Generated documents missing');
        $this->line($this->hasStoredPayslipPdf() ? '[OK] Payslip PDF exists' : '[WARN] Payslip PDF missing');
        $this->line($this->hasStoredContractPdf() ? '[OK] Contract PDF exists' : '[WARN] Contract PDF missing');
        $this->line(Plan::query()->exists() ? '[OK] SaaS plans exist' : '[WARN] SaaS plans missing');
        $this->line(Subscription::query()->exists() ? '[OK] Subscriptions exist' : '[WARN] Subscriptions missing');
        $this->line(Schema::hasTable('invoices') ? '[OK] Invoices table exists' : '[WARN] Invoices table missing');
        $this->line(Schema::hasTable('payments') ? '[OK] Payments table exists' : '[WARN] Payments table missing');
        $this->line($this->privateDiskWritable() ? '[OK] Billing storage writable' : '[WARN] Billing storage not writable');
        $this->line(Route::has('pricing') ? '[OK] Pricing route exists' : '[WARN] Pricing route missing');
        $this->line(Route::has('onboarding.company') && Route::has('onboarding.complete') ? '[OK] Onboarding routes exist' : '[WARN] Onboarding routes missing');
        $this->line(Invoice::query()->exists() ? '[OK] Invoices exist' : '[WARN] Invoices missing');

        $this->line(Schema::hasTable('demo_requests') ? '[OK] Demo requests table exists' : '[WARN] Demo requests table missing');
        $this->line(Route::has('request-demo.create') ? '[OK] /request-demo route exists' : '[WARN] /request-demo route missing');
        $this->line(Route::has('legal.terms') ? '[OK] /terms route exists' : '[WARN] /terms route missing');
        $this->line(Route::has('legal.privacy') ? '[OK] /privacy route exists' : '[WARN] /privacy route missing');
        $this->line(Route::has('legal.legal-notice') ? '[OK] /legal-notice route exists' : '[WARN] /legal-notice route missing');

        $this->line(view()->exists('legal.privacy') ? '[OK] Privacy view exists' : '[WARN] Privacy view missing');
        $this->line(view()->exists('legal.legal-notice') ? '[OK] Legal notice view exists' : '[WARN] Legal notice view missing');
        $this->line(view()->exists('demo-request') ? '[OK] Demo request view exists' : '[WARN] Demo request view missing');
        $this->line(view()->exists('demo-request-thank-you') ? '[OK] Demo thank-you view exists' : '[WARN] Demo thank-you view missing');
        $this->line(Schema::hasTable('support_tickets') ? '[OK] Support tickets table exists' : '[WARN] Support tickets table missing');
        $this->line(Schema::hasTable('support_ticket_replies') ? '[OK] Support ticket replies table exists' : '[WARN] Support ticket replies table missing');
        $this->line(Route::has('filament.admin.resources.support-tickets.index') ? '[OK] SupportTicketResource route exists' : '[WARN] SupportTicketResource route missing');
        $this->line(Route::has('employee.support') && Route::has('employee.support.create') && Route::has('employee.support.reply') ? '[OK] Employee support routes exist' : '[WARN] Employee support routes missing');
        $this->line(view()->exists('employee.support.index') && view()->exists('employee.support.create') && view()->exists('employee.support.show') ? '[OK] Support views exist' : '[WARN] Support views missing');
        $this->line(class_exists(\App\Notifications\SupportTicketCreatedNotification::class)
            && class_exists(\App\Notifications\SupportTicketRepliedNotification::class)
            && class_exists(\App\Notifications\SupportTicketResolvedNotification::class)
            ? '[OK] Support notifications exist'
            : '[WARN] Support notifications missing');

        $commands = collect(Artisan::all())->keys();
        $this->line($commands->contains('smartrh:create-demo-tenant') ? '[OK] Demo tenant command registered' : '[WARN] Demo tenant command missing');
        $this->line($commands->contains('smartrh:reset-demo') ? '[OK] Demo reset command registered' : '[WARN] Demo reset command missing');

        $adminEmail = config('smartrh.support_email');
        $this->line($adminEmail ? '[OK] Admin notification email configured: ' . $adminEmail : '[WARN] Admin notification email not configured');

        $usefulRoles = ['Company Owner', 'Super Admin', 'company_admin', 'admin', 'owner'];
        $foundRole = collect($usefulRoles)->first(fn ($r) => Role::query()->where('name', $r)->where('guard_name', 'web')->exists());
        $this->line($foundRole ? '[OK] A usable admin/company role exists: ' . $foundRole : '[WARN] No usable admin/company role found (command can create fallback)');

        $docsDir = base_path('docs');
        $this->line(is_dir($docsDir) ? '[OK] Docs directory exists' : '[WARN] Docs directory missing');
        $this->line(file_exists($docsDir . '/production-checklist.md') ? '[OK] Production checklist doc exists' : '[WARN] Production checklist doc missing');
        $this->line(file_exists($docsDir . '/deployment-laravel.md') ? '[OK] Deployment guide doc exists' : '[WARN] Deployment guide doc missing');
        $this->line(file_exists($docsDir . '/backup-strategy.md') ? '[OK] Backup strategy doc exists' : '[WARN] Backup strategy doc missing');
        $this->line(file_exists($docsDir . '/demo-guide.md') ? '[OK] Demo guide doc exists' : '[WARN] Demo guide doc missing');

        $queueConnection = config('queue.default');
        $this->line($queueConnection ? '[OK] Queue connection configured: ' . $queueConnection : '[WARN] Queue connection not configured');

        $mailDriver = config('mail.default');
        $mailFrom = config('mail.from.address');
        $this->line($mailDriver ? '[OK] Mail driver configured: ' . $mailDriver : '[WARN] Mail driver not configured');
        $this->line($mailFrom ? '[OK] Mail from address: ' . $mailFrom : '[WARN] Mail from address not set');

        $this->line(class_exists(\Illuminate\Console\Scheduling\Schedule::class) ? '[OK] Scheduler class available' : '[WARN] Scheduler class missing');

        $privateDirs = ['payslips', 'contracts', 'documents', 'invoices'];
        foreach ($privateDirs as $dir) {
            $this->line(Storage::disk($privateDisk)->makeDirectory($dir) ? '[OK] Private ' . $dir . ' path accessible' : '[WARN] Private ' . $dir . ' path not writable');
        }

        $this->line('APP_ENV=' . app()->environment());
        $this->line('APP_DEBUG=' . (config('app.debug') ? 'true' : 'false'));
        $this->line($mailDriver ? '[OK] Mail driver configured: ' . $mailDriver : '[WARN] Mail driver missing');

        $this->warn('Payroll legal rules must be validated by a Moroccan accountant before production.');

        return self::SUCCESS;
    }

    private function hasStoredPayslipPdf(): bool
    {
        return GeneratedDocument::query()
            ->where('type', 'payslip')
            ->get(['file_path', 'pdf_path'])
            ->contains(fn (GeneratedDocument $document): bool => (bool) ($document->file_path && Storage::disk(config('filesystems.private_disk'))->exists($document->file_path))
                || (bool) ($document->pdf_path && Storage::disk(config('filesystems.private_disk'))->exists($document->pdf_path)));
    }

    private function hasStoredContractPdf(): bool
    {
        return EmployeeContract::query()
            ->whereNotNull('pdf_path')
            ->get(['pdf_path'])
            ->contains(fn (EmployeeContract $contract): bool => Storage::disk(config('filesystems.private_disk'))->exists($contract->pdf_path));
    }

    private function privateDiskAccessible(): bool
    {
        try {
            $disk = Storage::disk(config('filesystems.private_disk'));

            return $disk->exists('.') || $disk->makeDirectory('.');
        } catch (\Throwable) {
            return false;
        }
    }

    private function privateDiskWritable(): bool
    {
        try {
            $disk = Storage::disk(config('filesystems.private_disk'));
            $path = '.smartrh-health-check';
            $disk->put($path, 'ok');
            $exists = $disk->exists($path);
            $disk->delete($path);

            return $exists;
        } catch (\Throwable) {
            return false;
        }
    }
}
