<?php

namespace App\Console\Commands;

use App\Services\Documents\ContractPdfService;
use App\Services\Documents\HrDocumentGenerator;
use App\Services\Documents\PayslipPdfGenerator;
use App\Services\Saas\InvoicePdfService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SmartRhDeploymentCheck extends Command
{
    protected $signature = 'smartrh:deployment-check';

    protected $description = 'Check SmartRH Maroc deployment readiness without exposing secrets.';

    private bool $failed = false;

    public function handle(): int
    {
        $this->info('SmartRH Maroc deployment readiness check');

        $this->warnIf(! app()->environment('production'), 'APP_ENV is not production: ' . app()->environment());
        $this->warnIf(config('app.debug'), 'APP_DEBUG is true; set APP_DEBUG=false for Railway production demos.');
        $this->check('APP_KEY configured', fn (): bool => filled(config('app.key')));
        $this->check('APP_URL configured', fn (): bool => filled(config('app.url')));

        $this->check('Database connectivity', function (): bool {
            DB::connection()->getPdo();

            return true;
        });

        $driver = DB::connection()->getDriverName();
        $this->warnIf(! in_array($driver, ['mysql', 'pgsql'], true), 'Database driver is ' . $driver . '; production should use mysql or pgsql.');
        $this->check('Sessions table exists', fn (): bool => Schema::hasTable(config('session.table', 'sessions')));
        $this->check('Cache table exists', fn (): bool => Schema::hasTable(config('cache.stores.database.table', 'cache')));
        $this->check('Cache locks table exists', fn (): bool => Schema::hasTable(config('cache.stores.database.lock_table', 'cache_locks') ?: 'cache_locks'));
        $this->check('Jobs table exists', fn (): bool => Schema::hasTable(config('queue.connections.database.table', 'jobs')));
        $this->check('Job batches table exists', fn (): bool => Schema::hasTable(config('queue.batching.table', 'job_batches')));
        $this->check('Failed jobs table exists', fn (): bool => Schema::hasTable(config('queue.failed.table', 'failed_jobs')));
        $this->check('Migrations table exists', fn (): bool => Schema::hasTable(config('database.migrations.table', 'migrations')));
        $this->check('Migrations status command runs', fn (): bool => Artisan::call('migrate:status') === self::SUCCESS);

        $this->check('Private storage writable', fn (): bool => $this->privateDiskWritable());
        $this->check('Vite manifest exists', fn (): bool => file_exists(public_path('build/manifest.json')));
        $this->check('Admin route exists', fn (): bool => Route::has('filament.admin.auth.login'));
        $this->check('Employee portal route exists', fn (): bool => Route::has('employee.login'));
        $this->check('Health route exists', fn (): bool => Route::has('health.up'));
        $this->check('Queue configured', fn (): bool => filled(config('queue.default')));
        $this->check('Mail configured', fn (): bool => filled(config('mail.default')) && filled(config('mail.from.address')));
        $this->check('Logo assets exist', fn (): bool => file_exists(public_path('images/branding/smartrh-logo.png'))
            && file_exists(public_path('images/branding/smartrh-icon.png'))
            && file_exists(public_path('images/branding/smartrh-icon.ico')));
        $this->check('PDF services available', fn (): bool => class_exists(PayslipPdfGenerator::class)
            && class_exists(InvoicePdfService::class)
            && class_exists(HrDocumentGenerator::class)
            && class_exists(ContractPdfService::class)
            && view()->exists('pdf.payslip')
            && view()->exists('pdf.invoice')
            && view()->exists('pdf.documents.generic'));

        if (config('smartrh.demo_mode_enabled')) {
            $this->warn('Demo mode is enabled; disable demo credentials before real production.');
        }

        if ($this->failed) {
            $this->error('SmartRH Maroc deployment readiness: FAILED');

            return self::FAILURE;
        }

        $this->info('SmartRH Maroc deployment readiness: OK');

        return self::SUCCESS;
    }

    private function check(string $label, callable $callback): void
    {
        try {
            $passed = (bool) $callback();
        } catch (\Throwable $exception) {
            $passed = false;
        }

        if ($passed) {
            $this->line('[OK] ' . $label);

            return;
        }

        $this->failed = true;
        $this->line('[FAIL] ' . $label);
    }

    private function warnIf(bool $condition, string $message): void
    {
        if ($condition) {
            $this->warn('[WARN] ' . $message);
        }
    }

    private function privateDiskWritable(): bool
    {
        try {
            $disk = Storage::disk(config('filesystems.private_disk'));
            $path = '.smartrh-deployment-check';
            $disk->put($path, 'ok');
            $exists = $disk->exists($path);
            $disk->delete($path);

            return $exists;
        } catch (\Throwable) {
            return false;
        }
    }
}
