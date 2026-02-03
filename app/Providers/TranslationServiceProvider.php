<?php

namespace App\Providers;

use App\Translation\DatabaseTranslationLoader;
use App\Translation\DatabaseTranslator;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Translation\FileLoader;
use Illuminate\Support\ServiceProvider;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('translation.loader', function ($app) {
            $fileLoader = new FileLoader($app['files'], $app['path.lang']);
            return new DatabaseTranslationLoader($fileLoader, (string) env('DB_PREFIX', 'mt'));
        });

        $this->app->singleton('translator', function ($app) {
            /** @var Loader $loader */
            $loader = $app['translation.loader'];

            $locale = (string) $app['config']['app.locale'];
            $fallback = (string) $app['config']['app.fallback_locale'];

            $translator = new DatabaseTranslator($loader, $locale);
            $translator->setFallback($fallback);

            return $translator;
        });

        // НИЧЕГО больше не надо:
        // - НЕ extend('translator') (он и ломал всё)
        // - НЕ bind(self::class) (это не контракт переводчика и только путает контейнер)
    }

    public function boot(): void
    {
        //
    }
}
