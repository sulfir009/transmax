<?php

namespace App\Http\Controllers;

use App\Repository\Schedule\ScheduleRepository;
use App\Service\Schedule\ScheduleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Service\Site;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private ScheduleService $scheduleService
    ) {
    }

    /**
     * Display the schedule page
     */
    public function index(Request $request)
    {
        // 1) SEO-редирект: если пришли с id-шками — пробуем собрать красивый URL.
        if ($request->filled(['departure', 'arrival'])) {
            $redirectUrl = $this->scheduleService->buildRouteRedirectUrl(
                (int) $request->get('departure'),
                (int) $request->get('arrival'),
                $request->query()
            );

            if ($redirectUrl !== null) {
                return redirect($redirectUrl, 301);
            }

            abort(404);
        }

        // 2) Фильтры страницы (это влияет ТОЛЬКО на основной список расписания)
        $filters = [
            'departure' => $request->get('departure'),
            'arrival'   => $request->get('arrival'),
            'country'   => $request->get('country'),
            'city'      => $request->get('city'),
        ];

        // 3) Пагинация основного списка
        $perPage     = 16;
        $currentPage = (int) $request->get('page', 1);

        $routes = $this->scheduleService->getFilteredRoutes($filters, $currentPage, $perPage);
        $routes = $this->applySort($routes, $request->get('sort'));

        // 4) “Наші напрямки”
        $countries           = $this->scheduleRepository->getCountriesForHome();
        $cities              = $this->scheduleRepository->getPopularCities(10);
        $internationalRoutes = $this->scheduleRepository->getInternationalRoutes();
        $domesticRoutes      = $this->scheduleRepository->getDomesticRoutes();

        $pageTitle = $this->scheduleService->getPageTitle($filters, $routes);

        // ✅ 5) Самое важное: популярные рейсы должны быть НЕ из $routes (пагинации),
        // а из полного списка направлений.
        $popularRoutes = $this->buildPopularRoutesForView(Site::lang());

        return view('schedule.index', compact(
            'routes',
            'filters',
            'countries',
            'cities',
            'internationalRoutes',
            'domesticRoutes',
            'pageTitle',
            'popularRoutes'
        ));
    }

    public function route(string $from, string $to, Request $request)
    {
        // ⚠️ чтобы не путать с $citiesList, называем по-другому
        $citiesBySlug = $this->scheduleService->getCitiesBySlugs($from, $to, Site::lang());

        if (!$citiesBySlug) {
            abort(404);
        }

        $filters = [
            'departure' => $citiesBySlug['departure']->id,
            'arrival'   => $citiesBySlug['arrival']->id,
            'country'   => $request->get('country'),
            'city'      => $request->get('city'),
        ];

        $perPage     = 16;
        $currentPage = (int) $request->get('page', 1);

        $routes = $this->scheduleService->getFilteredRoutes($filters, $currentPage, $perPage);
        $routes = $this->applySort($routes, $request->get('sort'));

        $countries           = $this->scheduleRepository->getCountriesForHome();
        $citiesList          = $this->scheduleRepository->getPopularCities(10);
        $internationalRoutes = $this->scheduleRepository->getInternationalRoutes();
        $domesticRoutes      = $this->scheduleRepository->getDomesticRoutes();
        $pageTitle           = $this->scheduleService->getPageTitle($filters, $routes);

        // ✅ то же самое — популярные берём отдельно, не из $routes
        $popularRoutes = $this->buildPopularRoutesForView(Site::lang());

        return view('schedule.index', [
            'routes'              => $routes,
            'filters'             => $filters,
            'countries'           => $countries,
            'cities'              => $citiesList,
            'internationalRoutes' => $internationalRoutes,
            'domesticRoutes'      => $domesticRoutes,
            'pageTitle'           => $pageTitle,
            'popularRoutes'       => $popularRoutes,
        ]);
    }

    /**
     * Собираем "ПОПУЛЯРНІ РЕЙСИ" как:
     * - все уникальные пары departure+arrival
     * - цена = минимальная (от ...)
     * - дата для ссылки = ближайшая (если есть)
     *
     * Почему так:
     * - $routes это пагинация и не содержит все маршруты
     * - здесь мы собираем полный список и кэшируем, чтобы не убивать сервер
     */
    private function buildPopularRoutesForView(string $lang)
    {
        // ключ кэша на язык, чтобы не мешать uk/ru/en
        $cacheKey = "schedule:popular_routes_cards:v1:{$lang}";

        // моё мнение: 30-60 минут идеальный TTL.
        // Цены/рейсы меняются не каждую минуту, а нагрузку срезаем сильно.
        $ttlSeconds = 60 * 30;

        return Cache::remember($cacheKey, $ttlSeconds, function () {
            // Берём все маршруты через тот же сервис, но без фильтров.
            // Это важно: мы не привязываемся к таблицам/SQL — используем вашу текущую логику.
            $filtersAll = [
                'departure' => null,
                'arrival'   => null,
                'country'   => null,
                'city'      => null,
            ];

            // Чтобы не было 1000 запросов, берём крупными страницами.
            // Если станет тяжело — уменьшай до 100.
            $perPage = 200;

            $page          = 1;
            $maxPagesSafe  = 300; // защита от бесконечного цикла, если что-то пойдёт не так
            $allRoutesFlat = collect();

            while ($page <= $maxPagesSafe) {
                $paginator = $this->scheduleService->getFilteredRoutes($filtersAll, $page, $perPage);

                // Важно: getFilteredRoutes возвращает пагинатор (ты уже используешь getCollection/setCollection),
                // значит getCollection() есть.
                $collection = method_exists($paginator, 'getCollection')
                    ? $paginator->getCollection()
                    : collect($paginator);

                if ($collection->isEmpty()) {
                    break;
                }

                // У тебя в Blade уже есть логика: коллекция может быть массивом групп.
                $flat = is_array($collection->first())
                    ? $collection->flatten(1)
                    : $collection;

                $allRoutesFlat = $allRoutesFlat->merge($flat);

                // Если пагинатор знает lastPage — останавливаемся корректно.
                if (method_exists($paginator, 'lastPage')) {
                    $last = (int) $paginator->lastPage();
                    if ($page >= $last) {
                        break;
                    }
                }

                $page++;
            }

            // 1) Аггрегируем по паре departure+arrival
            // 2) Считаем min_price
            $pairs = [];

            foreach ($allRoutesFlat as $route) {
                $depId = data_get($route, 'departure');
                $arrId = data_get($route, 'arrival');

                // без id-шек маршрут бессмысленный для ссылки
                if (!$depId || !$arrId) {
                    continue;
                }

                $depCity = (string) data_get($route, 'departure_city', '');
                $arrCity = (string) data_get($route, 'arrival_city', '');

                if ($depCity === '' || $arrCity === '') {
                    continue;
                }

                $key = $depId . '_' . $arrId;

                $priceRaw = data_get($route, 'ticket_price', null);
                $price    = is_numeric($priceRaw) ? (float) $priceRaw : null;

                $date = data_get($route, 'nearest_departure_date', null);
                $date = $date ?: null;

                if (!isset($pairs[$key])) {
                    $pairs[$key] = [
                        'departure'              => (int) $depId,
                        'arrival'                => (int) $arrId,
                        'departure_city'         => $depCity,
                        'arrival_city'           => $arrCity,
                        'min_price'              => $price,
                        'nearest_departure_date' => $date,
                    ];
                } else {
                    // min price
                    if ($price !== null) {
                        $prev = $pairs[$key]['min_price'];
                        $pairs[$key]['min_price'] = ($prev === null) ? $price : min($prev, $price);
                    }

                    // дата: берём самую раннюю (если обе есть)
                    $prevDate = $pairs[$key]['nearest_departure_date'];
                    if ($prevDate === null && $date !== null) {
                        $pairs[$key]['nearest_departure_date'] = $date;
                    } elseif ($prevDate !== null && $date !== null) {
                        $pairs[$key]['nearest_departure_date'] = min($prevDate, $date);
                    }
                }
            }

            $pairsCollection = collect(array_values($pairs))
                ->sortBy(fn ($r) => $r['departure_city'] . '|' . $r['arrival_city'])
                ->values();

            // Группируем по городу отправления → карточки
            return $pairsCollection
                ->groupBy('departure_city')
                ->map(function ($group, $departureCity) {
                    return [
                        'title' => 'З МІСТА ' . mb_strtoupper($departureCity, 'UTF-8'),
                        'items' => $group->map(function ($r) {
                            $price = $r['min_price'] !== null
                                ? number_format((float) $r['min_price'], 0, '.', ' ') . ' грн'
                                : '—';

                            $date = $r['nearest_departure_date'] ?: now()->format('Y-m-d');

                            return [
                                'label' => $r['departure_city'] . ' → ' . $r['arrival_city'],
                                'price' => $price,
                                'url'   => route('tickets.index', [
                                    'departure' => $r['departure'],
                                    'arrival'   => $r['arrival'],
                                    'date'      => $date,
                                    'adults'    => 1,
                                    'kids'      => 0,
                                ]),
                            ];
                        })->values(),
                    ];
                })
                ->values();
        });
    }

    // ---------------------------------------------------------------------
    // Остальное без изменений
    // ---------------------------------------------------------------------

    public function getRouteDetails(Request $request)
    {
        $tourId = $request->input('id');
        $departureId = $request->input('departure');
        $arrivalId = $request->input('arrival');

        $details = $this->scheduleService->getRouteDetails($tourId, $departureId, $arrivalId);

        if (!$details) {
            return response()->json(['error' => 'err'], 400);
        }

        return response()->json([
            'html' => view('schedule.partials.route-details', compact('details'))->render()
        ]);
    }

    public function getRoutePrices(Request $request)
    {
        $tourId = $request->input('id');
        $departureId = $request->input('departure');
        $arrivalId = $request->input('arrival');

        $prices = $this->scheduleService->getRoutePrices($tourId, $departureId, $arrivalId);

        if (!$prices) {
            return response()->json(['error' => 'err'], 400);
        }

        return response()->json([
            'data' => view('schedule.partials.route-prices', compact('prices'))->render()
        ]);
    }

    public function rememberTicket(Request $request)
    {
        $tourId = $request->input('id');
        $passengers = $request->input('passengers', 1);
        $departureId = $request->input('departure');
        $arrivalId = $request->input('arrival');
        $date = $request->input('date');

        $result = $this->scheduleService->rememberTicket($tourId, $passengers, $departureId, $arrivalId, $date);

        if ($result === 'late') {
            return response()->json('late');
        }

        if ($result) {
            return response()->json('ok');
        }

        return response()->json('err', 400);
    }

    private function applySort($routes, ?string $sort)
    {
        $availableSorts = ['price', 'dep', 'arr', 'popular'];
        if (!$sort || !in_array($sort, $availableSorts, true)) {
            return $routes;
        }

        $collection = $routes->getCollection();
        if ($collection->isEmpty()) {
            return $routes;
        }

        $timeToSeconds = function (?string $time): int {
            if (!$time) {
                return PHP_INT_MAX;
            }
            $time = trim($time);
            if (strlen($time) === 5) {
                $time .= ':00';
            }
            $timestamp = strtotime($time);
            return $timestamp !== false ? $timestamp : PHP_INT_MAX;
        };

        $sortValue = match ($sort) {
            'price' => fn ($route) => (float) data_get($route, 'ticket_price', PHP_INT_MAX),
            'dep' => fn ($route) => $timeToSeconds(data_get($route, 'departure_details.departure_time') ?? data_get($route, 'departure_time')),
            'arr' => fn ($route) => $timeToSeconds(data_get($route, 'arrival_details.arrival_time') ?? data_get($route, 'arrival_time')),
            'popular' => fn ($route) => (float) data_get($route, 'ticket_price', 0),
            default => fn () => 0,
        };

        $sortGroup = function ($group) use ($sort, $sortValue) {
            $groupCollection = collect($group);
            $sorted = $sort === 'popular'
                ? $groupCollection->sortByDesc($sortValue)
                : $groupCollection->sortBy($sortValue);

            return $sorted->values()->all();
        };

        $firstItem = $collection->first();
        if (is_array($firstItem)) {
            $routes->setCollection($collection->map($sortGroup));
            return $routes;
        }

        $sorted = $sort === 'popular'
            ? $collection->sortByDesc($sortValue)->values()
            : $collection->sortBy($sortValue)->values();

        $routes->setCollection($sorted);

        return $routes;
    }
}
