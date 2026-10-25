<?php

namespace App\Providers;

use App\Hashing\LegacyBbpHasher;
use Illuminate\Support\Facades\Hash;
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
        // Registra el driver de hashing que valida contraseñas legacy de Bebé Genial y rehashea a bcrypt
        Hash::extend('legacy_bbp', function () {
            return new LegacyBbpHasher();
        });
    }
}
