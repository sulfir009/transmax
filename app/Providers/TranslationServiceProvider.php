<?php

namespace App\Providers;

use App\Service\Site;
use Illuminate\Contracts\Translation\Loader;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\Translator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Throwable;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('translator', function ($app) {
            /** @var Loader $loader */
            $loader = $app['translation.loader'];

            $locale   = (string) $app['config']['app.locale'];
            $fallback = (string) $app['config']['app.fallback_locale'];
            $prefix   = env('DB_PREFIX', 'mt');

            $translator = new class($loader, $locale, $prefix) extends Translator {
                private string $prefix;

                /** @var array<string, string|null> */
                private array $localCache = [];

                public function __construct(Loader $loader, string $locale, string $prefix)
                {
                    parent::__construct($loader, $locale);
                    $this->prefix = $prefix;
                }

                public function get($key, array $replace = [], $locale = null, $fallback = true)
                {
                    // Язык для колонок БД (title_ru/title_uk/title_en)
                    $lang = Site::lang();
                    $lang = in_array($lang, ['ru', 'uk', 'en'], true) ? $lang : 'ru';

                    [$tableBase, $code] = $this->resolveTranslationTarget((string) $key);
                    $table = $this->prefix . '_' . $tableBase;

                    // Кеш в рамках одного запроса (быстро и безопасно)
                    $cacheKey = $lang . '|' . $table . '|' . $code;

                    if (array_key_exists($cacheKey, $this->localCache)) {
                        $value = $this->localCache[$cacheKey];
                        return $value ?: parent::get($key, $replace, $locale, $fallback);
                    }

                    try {
                        if (!Schema::hasTable($table)) {
                            $this->localCache[$cacheKey] = null;
                            return parent::get($key, $replace, $locale, $fallback);
                        }

                        $column = "title_{$lang}";

                        $value = DB::table($table)
                            ->where('code', $code)
                            ->value($column);

                        $value = is_string($value) && $value !== '' ? $value : null;

                        $this->localCache[$cacheKey] = $value;

                        return $value ?: parent::get($key, $replace, $locale, $fallback);
                    } catch (Throwable $e) {
                        // ВАЖНО: не роняем приложение даже если БД/миграции/соединение умерли
                        $this->localCache[$cacheKey] = null;
                        return parent::get($key, $replace, $locale, $fallback);
                    }
                }

                private function resolveTranslationTarget(string $key): array
                {
                    // dictionary.MSG_ALL_ZVIDKI => [dictionary, MSG_ALL_ZVIDKI]
                    $segments = explode('.', $key, 2);

                    if (count($segments) > 1) {
                        return match ($segments[0]) {
                            'dictionary'       => ['dictionary', $segments[1]],
                            'settings'         => ['settings', $segments[1]],
                            'pages_title'      => ['pages_title', $segments[1]],
                            'pages_menu_title' => ['pages_menu_title', $segments[1]],
                            default            => ['dictionary', $key],
                        };
                    }

                    // pages_title_xxx => таблица pages_title, code = xxx
                    if (str_starts_with($key, 'pages_title_')) {
                        return ['pages_title', substr($key, strlen('pages_title_'))];
                    }

                    // pages_menu_title_xxx => таблица pages_menu_title, code = xxx
                    if (str_starts_with($key, 'pages_menu_title_')) {
                        return ['pages_menu_title', substr($key, strlen('pages_menu_title_'))];
                    }

                    return ['dictionary', $key];
                }
            };

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
