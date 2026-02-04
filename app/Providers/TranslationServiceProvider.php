<?php

namespace App\Providers;

use App\Translation\DatabaseTranslationLoader;
use App\Translation\DatabaseTranslator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 1) Подменяем стандартный translation.loader на наш (который подмешивает БД)
        $this->app->singleton('translation.loader', function ($app) {
            $files = $app->make(Filesystem::class);

            // стандартный file loader
            $fileLoader = new FileLoader($files, lang_path());

            // наш декоратор поверх file loader
            return new DatabaseTranslationLoader($fileLoader, 'mt');
        });

        // 2) Подменяем translator на наш DatabaseTranslator (чтобы работали ключи без точки)
        $this->app->singleton('translator', function ($app) {
            $loader = $app->make('translation.loader');

            $translator = new DatabaseTranslator($loader, $app['config']['app.locale']);

            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });

        // 3) alias для контракта
        $this->app->alias('translator', \Illuminate\Contracts\Translation\Translator::class);
    }
}
