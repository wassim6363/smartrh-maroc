<?php

namespace App\Console\Commands;

use App\Http\Controllers\ExportController;
use App\Models\Company;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\GeneratedDocument;
use App\Models\Invoice;
use App\Models\IrBracket;
use App\Models\LegalSetting;
use App\Models\PayrollPeriod;
use App\Models\Payslip;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SmartRhLocalReadyCheck extends Command
{
    protected $signature = 'smartrh:local-ready-check';

    protected $description = 'Verify SmartRH Maroc local SaaS MVP readiness.';

    private bool $failed = false;

    public function handle(): int
    {
        $this->info('SmartRH Maroc local SaaS readiness check');

        $this->critical('Database connection', function (): bool {
            DB::connection()->getPdo();

            return true;
        });
        $this->critical('Migrations table exists', fn (): bool => Schema::hasTable('migrations'));
        $this->critical('Admin user exists', fn (): bool => User::query()->where('email', 'admin@smartrh.test')->exists());
        $this->critical('Employee user exists', fn (): bool => User::query()->whereHas('employees')->exists());
        $this->critical('Roles exist', fn (): bool => Schema::hasTable('roles') && DB::table('roles')->exists());
        $this->critical('Companies exist', fn (): bool => Company::query()->exists());
        $this->critical('Employees exist', fn (): bool => Employee::query()->exists());
        $this->critical('Legal settings exist', fn (): bool => LegalSetting::query()->where(function ($query) {
            $query->where('active', true)->orWhere('is_active', true);
        })->exists());
        $this->critical('IR brackets exist', fn (): bool => IrBracket::query()->where('active', true)->exists());
        $this->critical('Payroll items exist', fn (): bool => Schema::hasTable('payroll_items') && DB::table('payroll_items')->exists());
        $this->critical('Payroll periods exist', fn (): bool => PayrollPeriod::query()->exists());
        $this->critical('At least one payslip exists', fn (): bool => Payslip::query()->exists());
        $this->critical('At least one payslip PDF exists', fn (): bool => GeneratedDocument::query()
            ->where('type', 'payslip')
            ->get(['file_path', 'pdf_path'])
            ->contains(fn (GeneratedDocument $document): bool => (bool) ($document->file_path && Storage::disk(config('filesystems.private_disk'))->exists($document->file_path))
                || (bool) ($document->pdf_path && Storage::disk(config('filesystems.private_disk'))->exists($document->pdf_path))));
        $this->critical('Contract templates exist', fn (): bool => ContractTemplate::query()->where('is_active', true)->exists());
        $this->critical('Employee contracts exist', fn (): bool => EmployeeContract::query()->exists());
        $this->critical('Generated documents exist', fn (): bool => GeneratedDocument::query()->exists());
        $this->critical('Support tickets exist', fn (): bool => SupportTicket::query()->exists());
        $this->critical('SaaS plans exist', fn (): bool => Plan::query()->exists());
        $this->critical('Subscriptions exist', fn (): bool => Subscription::query()->exists());
        $this->critical('Invoices exist', fn (): bool => Invoice::query()->exists());
        $this->critical('Payments table exists', fn (): bool => Schema::hasTable('payments'));
        $this->critical('Demo reset command exists', fn (): bool => collect(Artisan::all())->has('smartrh:reset-demo'));
        $this->critical('Demo request route exists', fn (): bool => Route::has('request-demo.create'));
        $this->critical('Pricing route exists', fn (): bool => Route::has('pricing'));
        $this->critical('Onboarding route exists', fn (): bool => Route::has('onboarding.company'));
        $this->critical('Employee portal routes exist', fn (): bool => Route::has('employee.login')
            && Route::has('employee.dashboard.show')
            && Route::has('employee.payslips')
            && Route::has('employee.contracts')
            && Route::has('employee.documents')
            && Route::has('employee.support'));
        $this->critical('Export employees XLSX route works', fn (): bool => $this->exportResponse('employees') instanceof BinaryFileResponse);
        $this->critical('Export employees CSV route works', fn (): bool => $this->exportResponse('employeesCsv') instanceof StreamedResponse);
        $this->critical('Import template route works', fn (): bool => app(ExportController::class)->employeeTemplate() instanceof StreamedResponse);
        $this->critical('Private storage writable', fn (): bool => $this->privateDiskWritable());
        $this->critical('Queue configured', fn (): bool => filled(config('queue.default')));
        $this->critical('Mail configured', fn (): bool => filled(config('mail.default')) && filled(config('mail.from.address')));
        $this->critical('Health-check passes', fn (): bool => Artisan::call('smartrh:health-check') === 0);

        if (app()->environment('local')) {
            $this->warn('[WARN] APP_ENV=local is expected for local MVP readiness.');
        }

        if (config('app.debug')) {
            $this->warn('[WARN] APP_DEBUG=true is acceptable locally; disable in production.');
        }

        if ($this->failed) {
            $this->error('SmartRH Maroc local SaaS readiness: FAILED');

            return self::FAILURE;
        }

        $this->info('SmartRH Maroc local SaaS readiness: OK');

        return self::SUCCESS;
    }

    private function critical(string $label, callable $callback): void
    {
        try {
            $passed = (bool) $callback();
        } catch (\Throwable $exception) {
            $passed = false;
            $this->line('[FAIL] ' . $label . ': ' . $exception->getMessage());
        }

        if ($passed) {
            $this->line('[OK] ' . $label);

            return;
        }

        $this->failed = true;
        $this->line('[FAIL] ' . $label);
    }

    private function exportResponse(string $method): mixed
    {
        $admin = User::query()->where('email', 'admin@smartrh.test')->first() ?: User::query()->first();
        if (! $admin) {
            return null;
        }

        Auth::login($admin);

        try {
            return app(ExportController::class)->{$method}();
        } finally {
            Auth::logout();
        }
    }

    private function privateDiskWritable(): bool
    {
        try {
            $disk = Storage::disk(config('filesystems.private_disk'));
            $path = '.smartrh-local-ready-check';
            $disk->put($path, 'ok');
            $exists = $disk->exists($path);
            $disk->delete($path);

            return $exists;
        } catch (\Throwable) {
            return false;
        }
    }
}
