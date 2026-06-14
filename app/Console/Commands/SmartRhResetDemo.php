<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SmartRhResetDemo extends Command
{
    protected $signature = 'smartrh:reset-demo
        {--force : Run without confirmation}
        {--skip-checks : Do not run health-check and local-ready-check after seeding}';

    protected $description = 'Reset the local SmartRH Maroc demo database.';

    public function handle(): int
    {
        if (! app()->environment('local') && ! $this->option('force')) {
            $this->error('This command is intended for local demo environments. Use --force only if you know what you are doing.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm('This will run migrate:fresh --seed. Continue?')) {
            $this->warn('Demo reset cancelled.');

            return self::SUCCESS;
        }

        $this->info('Clearing cached files...');
        $this->call('optimize:clear');

        $this->info('Resetting and seeding the demo database...');
        $this->call('migrate:fresh', ['--seed' => true, '--force' => true]);

        if (! $this->option('skip-checks')) {
            $this->info('Running SmartRH checks...');

            if (Artisan::call('smartrh:health-check') !== self::SUCCESS) {
                $this->error(Artisan::output());

                return self::FAILURE;
            }

            $this->line(Artisan::output());

            if (Artisan::call('smartrh:local-ready-check') !== self::SUCCESS) {
                $this->error(Artisan::output());

                return self::FAILURE;
            }

            $this->line(Artisan::output());
        }

        $this->newLine();
        $this->info('SmartRH Maroc demo reset complete.');
        $this->line('Admin URL: http://127.0.0.1:8000/admin');
        $this->line('Admin: admin@smartrh.test / password');
        $this->line('Employee portal: http://127.0.0.1:8000/employee/login');
        $this->line('Employee: amina.employee@smartrh.test / password');
        $this->line('Public demo page: http://127.0.0.1:8000/request-demo');

        return self::SUCCESS;
    }
}
