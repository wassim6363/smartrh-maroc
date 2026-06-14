<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RailwayDeploymentReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_route_returns_ok_json(): void
    {
        $this->get('/up')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_routes_are_cacheable_for_production_startup(): void
    {
        try {
            $this->assertSame(0, Artisan::call('route:cache'));
        } finally {
            Artisan::call('route:clear');
        }
    }

    public function test_database_config_accepts_railway_database_url_and_sslmode(): void
    {
        putenv('DATABASE_URL=postgresql://user:pass@example.railway.internal:5432/railway');
        putenv('DB_SSLMODE=require');
        $_ENV['DATABASE_URL'] = 'postgresql://user:pass@example.railway.internal:5432/railway';
        $_ENV['DB_SSLMODE'] = 'require';
        $_SERVER['DATABASE_URL'] = 'postgresql://user:pass@example.railway.internal:5432/railway';
        $_SERVER['DB_SSLMODE'] = 'require';

        try {
            $databaseConfig = require config_path('database.php');

            $this->assertSame(
                'postgresql://user:pass@example.railway.internal:5432/railway',
                $databaseConfig['connections']['pgsql']['url'],
            );
            $this->assertSame('require', $databaseConfig['connections']['pgsql']['sslmode']);
        } finally {
            putenv('DATABASE_URL');
            putenv('DB_SSLMODE');
            unset($_ENV['DATABASE_URL'], $_ENV['DB_SSLMODE'], $_SERVER['DATABASE_URL'], $_SERVER['DB_SSLMODE']);
        }
    }

    public function test_required_database_tables_exist_after_migrations(): void
    {
        foreach (['sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'migrations'] as $table) {
            $this->assertTrue(Schema::hasTable($table), 'Missing deployment table: ' . $table);
        }
    }

    public function test_private_storage_disk_is_configurable_and_writable(): void
    {
        $root = storage_path('framework/testing/private-deployment');
        File::deleteDirectory($root);

        config([
            'filesystems.private_disk' => 'private',
            'filesystems.disks.private.root' => $root,
        ]);

        Storage::disk('private')->put('health/check.txt', 'ok');

        Storage::disk('private')->assertExists('health/check.txt');
        $this->assertStringStartsWith($root, Storage::disk('private')->path('health/check.txt'));

        File::deleteDirectory($root);
    }

    public function test_deployment_check_command_passes_with_current_local_configuration(): void
    {
        $createdManifest = $this->ensureViteManifestExists();

        try {
            $this->artisan('smartrh:deployment-check')
                ->expectsOutputToContain('SmartRH Maroc deployment readiness')
                ->assertExitCode(0);
        } finally {
            if ($createdManifest) {
                File::delete(public_path('build/manifest.json'));
            }
        }
    }

    public function test_demo_seed_command_is_non_destructive_when_demo_data_exists(): void
    {
        Company::query()->create(['name' => 'Existing Demo Company']);
        User::query()->create([
            'name' => 'Existing Admin',
            'email' => 'existing-admin@smartrh.test',
            'password' => 'password',
        ]);

        $this->artisan('smartrh:seed-demo --force')
            ->expectsOutputToContain('Demo seeding skipped: company data already exists.')
            ->assertExitCode(0);

        $this->assertSame(1, Company::query()->count());
        $this->assertSame(1, User::query()->count());
    }

    private function ensureViteManifestExists(): bool
    {
        $manifestPath = public_path('build/manifest.json');

        if (File::exists($manifestPath)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($manifestPath));
        File::put($manifestPath, '{}');

        return true;
    }
}
