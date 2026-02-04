<?php

namespace App\Http\Controllers;

use App\Repository\Schedule\ScheduleRepository;
use App\Service\Schedule\ScheduleService;
use Illuminate\Http\Request;
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
        $popularRoutes = $this->scheduleService->getPopularRoutesForView(Site::lang());

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
        $popularRoutes = $this->scheduleService->getPopularRoutesForView(Site::lang());

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
