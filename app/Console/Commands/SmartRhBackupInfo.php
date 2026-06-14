<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SmartRhBackupInfo extends Command
{
    protected $signature = 'smartrh:backup-info';
    protected $description = 'Show SmartRH Maroc MVP backup checklist.';

    public function handle(): int
    {
        $this->info('Back up these assets:');
        $this->line('- Database');
        $this->line('- storage/app/private');
        $this->line('- .env');
        $this->line('- public uploads, if configured later');
        $this->warn('This command is informational only. Configure real off-site backups before production.');

        return self::SUCCESS;
    }
}
