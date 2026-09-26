<?php

namespace App\Providers;

use App\View\Composers\BreadcrumbComposer;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Laravel\Fortify\Fortify;
use Modules\Core\Support\Formatter;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Fortify supplies only the two-factor actions; the application's own auth routes stay in routes/auth.php.
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register breadcrumb composer for all views
        View::composer('*', BreadcrumbComposer::class);

        AliasLoader::getInstance()->alias('Formatter', Formatter::class);

        // The application's own strings, kept apart from the laravel-lang files so updating those never
        // overwrites them.
        $this->loadJsonTranslationsFrom(lang_path('erp'));

        Blade::directive('money', fn (string $expression) => "<?php echo e(\\Modules\\Core\\Support\\Formatter::money({$expression})); ?>");

        Password::defaults(fn () => Password::min(12)
            ->letters()
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->when($this->app->isProduction(), fn (Password $rule) => $rule->uncompromised()));
    }
}
