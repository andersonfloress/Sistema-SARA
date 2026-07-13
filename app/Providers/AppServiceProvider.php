<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Set Carbon locale for date formatting in Spanish
        Carbon::setLocale('es');

        // Force the correct root URL from APP_URL so redirects work
        // behind Replit's reverse proxy
        $appUrl = config('app.url');
        // Only force root URL when running behind the Replit proxy (non-localhost URL)
        if ($appUrl && !str_contains($appUrl, 'localhost') && !str_contains($appUrl, '127.0.0.1')) {
            URL::forceRootUrl($appUrl);
            if (str_starts_with($appUrl, 'https://')) {
                URL::forceScheme('https');
            }
        }
    }
}
