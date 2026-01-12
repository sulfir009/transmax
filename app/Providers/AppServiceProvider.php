<?php

namespace App\Providers;

use App\Providers\Composers\FilterComposer;
use App\Providers\Composers\FooterScriptComposer;
use App\Providers\Composers\HeadComposer;
use App\Providers\Composers\HeaderComposer;
use App\Providers\Composers\FooterComposer;
use App\Providers\Composers\LayoutAppComposer;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades;

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
        Facades\View::composer('layout.app', LayoutAppComposer::class);
        Facades\View::composer('layout.components.header.head', HeadComposer::class);
        Facades\View::composer('layout.components.header.header', HeaderComposer::class);
        Facades\View::composer('layout.components.filter.filter', FilterComposer::class);
        Facades\View::composer('layout.components.footer.footer', FooterComposer::class);
        Facades\View::composer('layout.components.footer.footer_scripts', FooterScriptComposer::class);
    }
}
