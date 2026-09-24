<?php

namespace App\Providers;

use App\View\Composers\BreadcrumbComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        // Register breadcrumb composer for all views
        View::composer('*', BreadcrumbComposer::class);

        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->when($this->app->isProduction(), fn (Password $rule) => $rule->uncompromised()));
    }
}
