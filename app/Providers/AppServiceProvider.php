<?php

namespace App\Providers;

use App\Models\TeslimatAdresi;
use App\Observers\TeslimatAdresiObserver;
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
       TeslimatAdresi::observe(TeslimatAdresiObserver::class);
    }
}
