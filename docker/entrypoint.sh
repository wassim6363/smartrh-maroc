#!/usr/bin/env sh
set -e

PORT="${PORT:-8080}"
PRIVATE_STORAGE_PATH="${PRIVATE_STORAGE_PATH:-/var/www/html/storage/app/private}"

sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

mkdir -p \
    "${PRIVATE_STORAGE_PATH}" \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache "${PRIVATE_STORAGE_PATH}" || true
chmod -R ug+rwX /var/www/html/storage /var/www/html/bootstrap/cache "${PRIVATE_STORAGE_PATH}" || true

echo "Waiting for database..."
php -r '
$attempts = 60;
while ($attempts-- > 0) {
    try {
        $basePath = getcwd();
        require $basePath . "/vendor/autoload.php";
        $app = require $basePath . "/bootstrap/app.php";
        $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        Illuminate\Support\Facades\DB::connection()->getPdo();
        exit(0);
    } catch (Throwable $e) {
        fwrite(STDERR, ".");
        sleep(2);
    }
}
fwrite(STDERR, PHP_EOL . "Database is not reachable." . PHP_EOL);
exit(1);
'

php artisan migrate --force

if [ "${SEED_DEMO_DATA:-false}" = "true" ]; then
    php artisan smartrh:seed-demo --force
fi

php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
