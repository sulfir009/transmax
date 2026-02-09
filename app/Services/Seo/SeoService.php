<?php

namespace App\Services\Seo;

use App\Helpers\LocaleHelper;
use App\Models\City;
use Illuminate\Support\Facades\DB;
use App\Repository\Schedule\ScheduleRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class SeoService
{
    private const ROUTE_TEMPLATE_CODES = [
        'ru' => ['title' => 'SEO_ROUTE_TITLE_RU', 'description' => 'SEO_ROUTE_DESC_RU'],
        'uk' => ['title' => 'SEO_ROUTE_TITLE_UK', 'description' => 'SEO_ROUTE_DESC_UK'],
        'en' => ['title' => 'SEO_ROUTE_TITLE_EN', 'description' => 'SEO_ROUTE_DESC_EN'],
    ];

    private const ROUTE_TEMPLATE_DEFAULTS = [
        'ru' => [
            'title' => 'Автобус [Название маршрута] - Купить билеты онлайн | MaxTrans',
            'description' => 'Билеты на автобус [Название маршрута] от [price] онлайн! ⏩ Актуальное расписание рейсов [Название маршрута] ⭐️ Комфортные автобусы ⚡ 18 лет опыта в перевозках',
        ],
        'uk' => [
            'title' => 'Автобус [Назва маршруту] - Купити квитки онлайн | MaxTrans',
            'description' => 'Квитки на автобус [Назва маршруту] від [price] онлайн! ⏩ Актуальний розклад рейсів [Назва маршруту] ⭐️ Комфортні автобуси ⚡ 18 років досвіду в перевезеннях',
        ],
        'en' => [
            'title' => 'Bus [Route Name] - Buy Tickets Online | MaxTrans',
            'description' => 'Bus tickets for [Route Name] from [price] online! ⏩ Current timetable for [Route Name] ⭐️ Comfortable buses ⚡ 18 years of transportation experience',
        ],
    ];

    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
    ) {
    }

    public function buildContext(Request $request, array $viewData = []): PageContext
    {
        $routeName = Route::currentRouteName() ?? '';
        $locale = app()->getLocale();
        $routeParams = $request->route()?->parameters() ?? [];
        $baseRouteName = $this->stripLocalePrefix($routeName);

        $departureCity = null;
        $arrivalCity = null;
        if ($baseRouteName === 'schedule.route' && isset($routeParams['from'], $routeParams['to'])) {
            $departureCity = $this->getCityBySlug($routeParams['from'], $locale);
            $arrivalCity = $this->getCityBySlug($routeParams['to'], $locale);
        }

        return new PageContext(
            routeName: $routeName,
            baseRouteName: $baseRouteName,
            locale: $locale,
            routeParams: $routeParams,
            viewData: $viewData,
            departureCity: $departureCity,
            arrivalCity: $arrivalCity,
        );
    }

    public function getTitle(PageContext $context): string
    {
        // Важное правило SEO: если meta заполнены вручную, не перезаписываем генерацией.
        $manualTitle = $this->getManualTitle($context->viewData);
        if ($manualTitle !== null) {
            return $manualTitle;
        }

        if ($context->baseRouteName === 'schedule.route') {
            return $this->renderRouteTemplate('route_page_title', $context);
        }

        return $this->getDefaultTitle($context);
    }

    public function getDescription(PageContext $context): string
    {
        // Важное правило SEO: если meta заполнены вручную, не перезаписываем генерацией.
        $manualDescription = $this->getManualDescription($context->viewData);
        if ($manualDescription !== null) {
            return $manualDescription;
        }

        if ($context->baseRouteName === 'schedule.route') {
            return $this->renderRouteTemplate('route_page_description', $context);
        }

        return $this->getDefaultDescription($context);
    }

    public function getCanonicalUrl(PageContext $context): string
    {
        if ($context->baseRouteName === 'schedule.route' && $context->hasRouteCities()) {
            return $this->buildScheduleRouteUrl($context->locale, $context->departureCity, $context->arrivalCity);
        }

        if ($context->routeName && Route::has($context->routeName)) {
            $baseName = $context->baseRouteName ?: $context->routeName;
            return LocaleHelper::localizedRoute($baseName, $context->routeParams, true, $context->locale);
        }

        return LocaleHelper::localizedUrl(request()->path(), $context->locale);
    }

    public function getHreflangs(PageContext $context): array
    {
        $urls = [];

        foreach (LocaleHelper::getSupportedLocales() as $locale) {
            if ($context->baseRouteName === 'schedule.route' && $context->hasRouteCities()) {
                $urls[$locale] = $this->buildScheduleRouteUrl($locale, $context->departureCity, $context->arrivalCity);
                continue;
            }

            if ($context->routeName && Route::has($context->routeName)) {
                $urls[$locale] = LocaleHelper::localizedRoute(
                    $context->baseRouteName ?: $context->routeName,
                    $context->routeParams,
                    true,
                    $locale
                );
                continue;
            }

            $urls[$locale] = LocaleHelper::localizedUrl(request()->path(), $locale);
        }

        return $urls;
    }

    public function getOpenGraph(PageContext $context): array
    {
        $title = $this->getTitle($context);
        $description = $this->getDescription($context);
        $url = $this->getCanonicalUrl($context);

        return [
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'type' => $this->getOgType($context),
            'image' => $this->getDefaultOgImage($context->viewData),
            'site_name' => 'MaxTrans',
        ];
    }

    public function getJsonLd(PageContext $context): array
    {
        $items = [];

        if ($context->baseRouteName === 'main') {
            $items[] = $this->getOrganizationSchema($context);
        }

        $items[] = $this->getBreadcrumbSchema($context);

        return $items;
    }

    private function getDefaultTitle(PageContext $context): string
    {
        $routeTitle = $context->getRouteTitle();
        if ($context->baseRouteName === 'schedule.route' && $routeTitle) {
            return $routeTitle;
        }

        $label = $this->getRouteLabel($context);

        if ($label !== '') {
            return $label . ' | ' . config('app.name', 'MaxTrans');
        }

        return config('app.name', 'MaxTrans');
    }

    private function getDefaultDescription(PageContext $context): string
    {
        $description = data_get($context->viewData, 'pageData.meta_description')
            ?? data_get($context->viewData, 'page_data.meta_d')
            ?? data_get($context->viewData, 'page_data.description')
            ?? data_get($context->viewData, 'pageData.description');

        return $description ? trim($description) : '';
    }

    private function getRouteLabel(PageContext $context): string
    {
        $labels = [
            'main' => __('pages_title_main'),
            'schedule' => __('pages_menu_title_schedule'),
            'avtopark' => __('pages_menu_title_avtopark'),
            'about.us' => __('pages_menu_title_about_us'),
            'kontakti' => __('pages_menu_title_kontakti'),
            'faq' => __('pages_menu_title_faq'),
            'privacy.policy' => data_get($context->viewData, 'pageData.page_title', ''),
            'terms.of.use' => data_get($context->viewData, 'pageData.page_title', ''),
            'offer' => data_get($context->viewData, 'pageData.page_title', ''),
            'transport.rules' => data_get($context->viewData, 'pageData.page_title', ''),
            'return.conditions' => data_get($context->viewData, 'pageData.page_title', ''),
            'data.deletion.instructions' => data_get($context->viewData, 'pageData.page_title', ''),
            'mobile.app' => data_get($context->viewData, 'pageData.page_title', ''),
        ];

        return (string) ($labels[$context->baseRouteName] ?? data_get($context->viewData, 'pageData.page_title', ''));
    }

    private function renderRouteTemplate(string $key, PageContext $context): string
    {
        $templateType = $key === 'route_page_description' ? 'description' : 'title';
        $template = $this->getRouteTemplate($context->locale, $templateType);

        $routeTitle = $context->getRouteTitle() ?? '';
        $price = $this->getRoutePrice($context);

        $routePlaceholders = [
            '[Название маршрута]' => $routeTitle,
            '[Назва маршруту]' => $routeTitle,
            '[Route Name]' => $routeTitle,
            '[route]' => $routeTitle, // backward compatibility with old template values
        ];

        $rendered = str_replace(array_keys($routePlaceholders), array_values($routePlaceholders), (string) $template);

        if ($price === '') {
            $rendered = $this->removePricePlaceholder($rendered, $context->locale);
            $rendered = str_replace('[price]', '', $rendered);
        } else {
            $rendered = str_replace('[price]', $price, $rendered);
        }

        return trim(preg_replace('/\s{2,}/', ' ', $rendered));
    }

    private function removePricePlaceholder(string $text, string $locale): string
    {
        $patterns = [
            'ru' => '/от\s*\[price\]/i',
            'uk' => '/від\s*\[price\]/i',
            'en' => '/from\s*\[price\]/i',
        ];

        $pattern = $patterns[$locale] ?? null;
        if ($pattern) {
            $text = preg_replace($pattern, '', $text);
        }

        return str_replace('[price]', '', $text);
    }

    private function getRouteTemplate(string $locale, string $type): string
    {
        $locale = in_array($locale, ['ru', 'uk', 'en'], true) ? $locale : 'ru';
        $type = $type === 'description' ? 'description' : 'title';

        $settingsCode = self::ROUTE_TEMPLATE_CODES[$locale][$type] ?? null;
        $defaultTemplate = self::ROUTE_TEMPLATE_DEFAULTS[$locale][$type] ?? '';

        if (!$settingsCode) {
            return $defaultTemplate;
        }

        $prefix = env('DB_PREFIX', 'mt');
        $table = $prefix . '_settings';

        if (!DB::getSchemaBuilder()->hasTable($table)) {
            return $defaultTemplate;
        }

        $value = DB::table($table)
            ->where('code', $settingsCode)
            ->value('value');

        $manual = $this->normalizeManualValue($value);

        // If template value is empty in admin settings, we use defaults.
        return $manual !== '' ? $manual : $defaultTemplate;
    }

    private function getRoutePrice(PageContext $context): string
    {
        if (!$context->hasRouteCities()) {
            return '';
        }

        $price = $this->scheduleRepository->getMinPriceForRoute(
            $context->departureCity->id,
            $context->arrivalCity->id
        );

        if (!$price) {
            return '';
        }

        return number_format($price, 0, '.', ' ') . ' грн';
    }

    private function getManualTitle(array $viewData): ?string
    {
        $title = data_get($viewData, 'page_data.page_title')
            ?? data_get($viewData, 'pageData.page_title')
            ?? data_get($viewData, 'pageData.title')
            ?? data_get($viewData, 'page_data.title');

        $title = $this->normalizeManualValue($title);

        return $title !== '' ? $title : null;
    }

    private function getManualDescription(array $viewData): ?string
    {
        $description = data_get($viewData, 'page_data.meta_d')
            ?? data_get($viewData, 'pageData.meta_description')
            ?? data_get($viewData, 'page_data.description')
            ?? data_get($viewData, 'pageData.description');

        $description = $this->normalizeManualValue($description);

        return $description !== '' ? $description : null;
    }

    private function normalizeManualValue(mixed $value): string
    {
        if (is_array($value)) {
            $value = Arr::first($value, static fn ($item) => is_string($item) && trim($item) !== '');
        }

        if ($value === null) {
            return '';
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return '';
        }

        return trim((string) $value);
    }

    private function getCityBySlug(string $slug, string $locale): ?City
    {
        $column = 'slug_' . $locale;

        return City::query()
            ->where($column, $slug)
            ->first();
    }

    private function buildScheduleRouteUrl(string $locale, City $departureCity, City $arrivalCity): string
    {
        $fromSlug = $departureCity->getSlug($locale);
        $toSlug = $arrivalCity->getSlug($locale);

        return LocaleHelper::localizedRoute(
            'schedule.route',
            ['from' => $fromSlug, 'to' => $toSlug],
            true,
            $locale
        );
    }

    private function getDefaultOgImage(array $viewData): string
    {
        $logo = data_get($viewData, 'logo.black_logo')
            ?? data_get($viewData, 'logo.white_logo');

        if ($logo) {
            return asset('images/legacy/upload/logos/' . ltrim($logo, '/'));
        }

        return asset('images/legacy/upload/logos/favicon.svg');
    }

    private function getOgType(PageContext $context): string
    {
        return $context->baseRouteName === 'news.show' ? 'article' : 'website';
    }

    private function getOrganizationSchema(PageContext $context): array
    {
        $contacts = config('contacts');
        $address = $contacts['address'][$context->locale] ?? Arr::first($contacts['address'] ?? []) ?? '';
        $social = array_filter($contacts['social'] ?? []);

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'MaxTrans',
            'url' => $this->getCanonicalUrl($context),
            'logo' => $this->getDefaultOgImage($context->viewData),
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'telephone' => $contacts['phone'] ?? '',
                'contactType' => 'customer service',
                'email' => $contacts['email'] ?? '',
                'availableLanguage' => LocaleHelper::getSupportedLocales(),
            ],
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $address,
            ],
            'sameAs' => array_values($social),
        ];
    }

    private function getBreadcrumbSchema(PageContext $context): array
    {
        $breadcrumbs = [];
        $homeLabel = __('pages_title_main');
        $homeUrl = LocaleHelper::localizedRoute('main', [], true, $context->locale);

        $breadcrumbs[] = [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => $homeLabel ?: 'Home',
            'item' => $homeUrl,
        ];

        $position = 2;
        if ($context->baseRouteName === 'schedule.route') {
            $breadcrumbs[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => __('pages_menu_title_schedule'),
                'item' => LocaleHelper::localizedRoute('schedule', [], true, $context->locale),
            ];

            $routeTitle = $context->getRouteTitle() ?? '';
            if ($routeTitle !== '') {
                $breadcrumbs[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $routeTitle,
                    'item' => $this->getCanonicalUrl($context),
                ];
            }
        } elseif ($context->baseRouteName !== 'main') {
            $label = $this->getRouteLabel($context);
            if ($label !== '') {
                $breadcrumbs[] = [
                    '@type' => 'ListItem',
                    'position' => $position,
                    'name' => $label,
                    'item' => $this->getCanonicalUrl($context),
                ];
            }
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $breadcrumbs,
        ];
    }

    private function stripLocalePrefix(string $routeName): string
    {
        foreach (LocaleHelper::getSupportedLocales() as $locale) {
            if (str_starts_with($routeName, $locale . '.')) {
                return substr($routeName, strlen($locale) + 1);
            }
        }

        return $routeName;
    }
}