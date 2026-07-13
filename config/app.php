<?php

use Illuminate\Support\Facades\Facade;

return [
    'name'             => env('APP_NAME', 'IE Santa Rosa'),
    'env'              => env('APP_ENV', 'production'),
    'debug'            => (bool) env('APP_DEBUG', false),
    'url'              => env('APP_URL', 'http://localhost'),
    'timezone'         => env('APP_TIMEZONE', 'America/Lima'),
    'locale'           => env('APP_LOCALE', 'es'),
    'fallback_locale'  => env('APP_FALLBACK_LOCALE', 'en'),
    'faker_locale'     => env('APP_FAKER_LOCALE', 'es_PE'),
    'cipher'           => 'AES-256-CBC',
    'key'              => env('APP_KEY'),
    'previous_keys'    => [],
    'maintenance'      => ['driver' => 'file'],
    'providers'        => \Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
    ])->toArray(),
    'aliases'          => Facade::defaultAliases()->merge([])->toArray(),
];
