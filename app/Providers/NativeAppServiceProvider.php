<?php

namespace App\Providers;

use Native\Laravel\Facades\Window;
use Native\Laravel\Contracts\ProvidesPhpIni;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        // Run database migrations and seeders automatically on app launch
        try {
            \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
            if (!\App\Models\User::where('email', 'admin@pos.com')->exists()) {
                \Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
            }
        } catch (\Throwable $e) {
            // Log or ignore
        }

        Window::open();
    }

    /**
     * Return an array of php.ini directives to be set.
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
