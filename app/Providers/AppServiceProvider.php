<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register FormFieldResolver service
        $this->app->bind(
            \App\Contracts\FormFieldResolverInterface::class,
            \App\Services\FormFieldResolver::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        \App\Models\ProductRequest::observe(\App\Observers\ProductRequestObserver::class);
    }
}
