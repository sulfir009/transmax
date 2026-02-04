<?php

namespace App\Providers;

use App\Providers\Composers\FilterComposer;
use App\Providers\Composers\FooterScriptComposer;
use App\Providers\Composers\HeadComposer;
use App\Providers\Composers\HeaderComposer;
use App\Providers\Composers\FooterComposer;
use App\Providers\Composers\LayoutAppComposer;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;

use App\Translation\DatabaseTranslationLoader;
use App\Translation\DatabaseTranslator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
{
    // 1) Расширяем уже зарегистрированный FileLoader нашим DatabaseTranslationLoader
    $this->app->extend('translation.loader', function ($loader, $app) {
        return new DatabaseTranslationLoader($loader, 'mt');
    });

    // 2) Заменяем translator на наш DatabaseTranslator
    $this->app->extend('translator', function ($translator, $app) {
        $loader = $app->make('translation.loader');

        $t = new DatabaseTranslator($loader, $app['config']['app.locale']);
        $t->setFallback($app['config']['app.fallback_locale']);

        return $t;
    });

    // 3) ВАЖНО: если сервис уже был создан — выкидываем инстансы, чтобы пересобрался заново
    $this->app->forgetInstance('translation.loader');
    $this->app->forgetInstance('translator');

    // 4) Alias на контракт
    $this->app->alias('translator', \Illuminate\Contracts\Translation\Translator::class);
}


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layout.app', LayoutAppComposer::class);
        View::composer('layout.components.header.head', HeadComposer::class);
        View::composer('layout.components.header.header', HeaderComposer::class);
        View::composer('layout.components.filter.filter', FilterComposer::class);
        View::composer('layout.components.footer.footer', FooterComposer::class);
        View::composer('layout.components.footer.footer_scripts', FooterScriptComposer::class);
    }
}
