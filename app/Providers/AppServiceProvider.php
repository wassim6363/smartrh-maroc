<?php

namespace App\Providers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        App::setLocale('fr');
        date_default_timezone_set(config('smartrh.timezone', config('app.timezone')));

        if (app()->environment('production') && (bool) env('FORCE_HTTPS', true)) {
            URL::forceScheme('https');
        }
    }
}
