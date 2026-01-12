<?php

namespace App\Http\Controllers;

use App\Repository\Schedule\ScheduleRepository;
use App\Service\Schedule\ScheduleService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private ScheduleService $scheduleService
    ) {
    }

    /**
     * Display the schedule page
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // Получаем параметры фильтрации
        $filters = [
            'departure' => $request->get('departure'),
            'arrival' => $request->get('arrival'),
            'country' => $request->get('country'),
            'city' => $request->get('city'),
        ];

        // Получаем данные расписания с пагинацией
        $perPage = 16;
        $currentPage = $request->get('page', 1);
        
        // Получаем отфильтрованные маршруты
        $routes = $this->scheduleService->getFilteredRoutes($filters, $currentPage, $perPage);
        
        // Получаем данные для блока "Наши направления"
        $countries = $this->scheduleRepository->getCountriesForHome();
        $cities = $this->scheduleRepository->getPopularCities(10);
        $internationalRoutes = $this->scheduleRepository->getInternationalRoutes();
        $domesticRoutes = $this->scheduleRepository->getDomesticRoutes();
        
        // Подготавливаем данные для заголовка
        $pageTitle = $this->scheduleService->getPageTitle($filters, $routes);

        return view('schedule.index', compact(
            'routes',
            'filters',
            'countries',
            'cities',
            'internationalRoutes',
            'domesticRoutes',
            'pageTitle'
        ));
    }

    /**
     * Get route details for popup
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Get route prices for popup
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

    /**
     * Remember ticket for booking
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
}
