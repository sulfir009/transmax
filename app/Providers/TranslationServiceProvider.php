<?php

namespace App\Providers;

use App\Service\Site;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class TranslationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('translator', function ($app) {
            $loader = $app['translation.loader'];

            // Create your custom translator instance
            $translator = new Translator($loader, $app['config']['app.locale']);

            $translator->setFallback($app['config']['app.fallback_locale']);

            return $translator;
        });

        // Bind the custom translator as the implementation for the TranslatorContract
        $this->app->bind(self::class, function ($app) {
            return $app['translator'];
        });

        $this->app->extend('translation', function ($translator, $app) {
            return new class($translator) extends Translator {
                public function get($key, array $replace = [], $locate = null, $fallback = true)
                {
                    $lang = Site::lang();
                    $table = 'mt_';
                    $locale = $locale ?? app()->getLocale();
                    $segments = explode('.', $key, 2);
                    $table .= count($segments) > 1 ? $segments[0] : 'dictionary';
                    $key = count($segments) > 1 ? $segments[1] : $segments[0];

                    $translation = DB::table($table)
                        ->select([
                            "title_{$lang} as value"
                        ])
                        ->where('code', $key)->value('value');

                    return $translation ?: parent::get($key, $replace, $locale, $fallback);
                }
            };
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
