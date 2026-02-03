<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Поддерживаемые языки
     *
     * @var array
     */
    protected $supportedLocales = ['en', 'uk', 'ru'];

    /**
     * Язык по умолчанию (без префикса в URL)
     *
     * @var string
     */
    protected $defaultLocale = 'ru';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        $this->routes(function () {
            // API маршруты (без языкового префикса)
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            // Web маршруты с языковыми префиксами
            $this->mapWebRoutes();
            
            // Legacy маршруты
            Route::middleware('web')
                ->group(base_path('routes/legacy.php'));
        });
    }

    /**
     * Define the "web" routes for the application with language prefixes.
     *
     * @return void
     */
    protected function mapWebRoutes()
    {
        // Маршруты для языков с префиксами (en, uk)
        foreach ($this->supportedLocales as $locale) {
            if ($locale !== $this->defaultLocale) {
                Route::middleware(['web', 'language'])
                    ->prefix($locale)
                    ->name($locale . '.')
                    ->group(function () use ($locale) {
                        $this->loadRoutesWithLocale(base_path('routes/web.php'), $locale);
                    });
            }
        }

        // Маршруты для языка по умолчанию (без префикса)
        Route::middleware(['web', 'language'])
            ->group(function () {
                $this->loadRoutesWithLocale(base_path('routes/web.php'), $this->defaultLocale);
            });
    }

    /**
     * Загружаем маршруты с установленной локалью
     *
     * @param string $path
     * @param string $locale
     * @return void
     */
    protected function loadRoutesWithLocale($path, $locale)
    {
        app()->setLocale($locale);
        require $path;
    }
}
