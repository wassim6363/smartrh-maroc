<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SmartRhSeedDemo extends Command
{
    protected $signature = 'smartrh:seed-demo {--force : Run without confirmation}';

    protected $description = 'Seed SmartRH Maroc demo data only when the database is empty.';

    public function handle(): int
    {
        if (! Schema::hasTable('companies')) {
            $this->error('Run php artisan migrate --force before seeding demo data.');

            return self::FAILURE;
        }

        if (Company::query()->exists()) {
            $this->info('Demo seeding skipped: company data already exists.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Seed demo data into the empty database?')) {
            $this->warn('Demo seeding cancelled.');

            return self::SUCCESS;
        }

        $exitCode = Artisan::call('db:seed', ['--force' => true]);
        $this->line(Artisan::output());

        if ($exitCode !== self::SUCCESS) {
            $this->error('Demo seeding failed.');

            return self::FAILURE;
        }

        $this->info('SmartRH Maroc demo data seeded.');
        $this->line('Admin: admin@smartrh.test / password');
        $this->line('Employee: amina.employee@smartrh.test / password');

        return self::SUCCESS;
    }
}
