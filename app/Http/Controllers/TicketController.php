<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\TicketRepository;
use App\Repository\CityRepository;
use App\Repository\Races\Params\TicketParams;
use App\Service\Tour\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TicketController extends Controller
{
    protected $ticketRepository;
    protected $cityRepository;
    protected $ticketService;
    protected $router;
    protected $db;

    public function __construct(
        TicketRepository $ticketRepository,
        CityRepository $cityRepository,
        TicketService $ticketService
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->cityRepository = $cityRepository;
        $this->ticketService = $ticketService;

        // Получаем глобальные объекты (временно, пока не рефакторим полностью)
        global $Router, $Db;
        $this->router = $Router;
        $this->db = $Db;
    }

    /**
     * Отображение страницы с билетами
     */
    public function index(Request $request)
    {
        // Инициализация сессии если нужно
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Проверяем, есть ли параметры в запросе
        $hasParams = $request->has('departure') || $request->has('arrival') || 
                     $request->has('date') || $request->has('adults') || $request->has('kids');

        // Если нет параметров и метод GET, делаем редирект с параметрами из сессии или дефолтными
        if (!$hasParams && $request->isMethod('get')) {
            $redirectParams = [
                'departure' => $_SESSION['filter']['departure'] ?? 183,
                'arrival' => $_SESSION['filter']['arrival'] ?? 183,
                'adults' => $_SESSION['filter']['adults'] ?? 1,
                'kids' => $_SESSION['filter']['kids'] ?? 0
            ];

            // Добавляем date только если есть в сессии
            if (isset($_SESSION['filter']['date'])) {
                $redirectParams['date'] = $_SESSION['filter']['date'];
            }

            return redirect()->route('tickets.index', $redirectParams);
        }

        // Обработка POST запроса от формы фильтра
        if ($request->isMethod('post')) {
            // Сохраняем параметры фильтра в сессию
            $_SESSION['filter'] = [
                'departure' => $request->input('departure', 0),
                'arrival' => $request->input('arrival', 0),
                'date' => $request->input('date', date('Y-m-d')),
                'adults' => $request->input('adults', 1),
                'kids' => $request->input('kids', 0)
            ];
            
            // Перенаправляем на GET запрос для избежания повторной отправки формы
            return redirect()->route('tickets.index', [
                'departure' => $_SESSION['filter']['departure'],
                'arrival' => $_SESSION['filter']['arrival'],
                'date' => $_SESSION['filter']['date'],
                'adults' => $_SESSION['filter']['adults'],
                'kids' => $_SESSION['filter']['kids']
            ]);
        }

        // Получение параметров фильтра (для GET запросов)
        $filterDeparture = $request->get('departure', $_SESSION['filter']['departure'] ?? 0);
        $filterArrival = $request->get('arrival', $_SESSION['filter']['arrival'] ?? 0);
        $filterDate = $request->get('date', $_SESSION['filter']['date'] ?? date('Y-m-d'));
        $adults = $request->get('adults', $_SESSION['filter']['adults'] ?? 1);
        $kids = $request->get('kids', $_SESSION['filter']['kids'] ?? 0);
        
        // Обновляем сессию если параметры пришли через GET
        $_SESSION['filter'] = [
            'departure' => $filterDeparture,
            'arrival' => $filterArrival,
            'date' => $filterDate,
            'adults' => $adults,
            'kids' => $kids
        ];
        $lang = $this->router->lang ?? 'ru';

        // Установка языка для репозиториев
        $this->ticketRepository->setLanguage($lang);
        $this->cityRepository->setLanguage($lang);

        // Получение названий городов
        $departureCityTitle = null;
        $arrivalCityTitle = null;

        if ($filterDeparture > 0) {
            $departureCityTitle = $this->cityRepository->getCityTitle($filterDeparture);
        }

        if ($filterArrival > 0) {
            $arrivalCityTitle = $this->cityRepository->getCityTitle($filterArrival);
        }

        // Получение месяца для фильтра даты
        $filterMonth = null;
        $weekDay = date('N', time());

        if ($filterDate !== "today") {
            $weekDay = date('N', strtotime($filterDate));
            $monthId = (int)explode('-', $filterDate)[1];
            $filterMonth = $this->cityRepository->getMonthTitle($monthId);
        }

        // Получение цен для слайдера
        $ticketPrices = $this->ticketRepository->getTicketPrices($filterDeparture, $filterArrival);
        $minTicketsPrice = !empty($ticketPrices) ? min($ticketPrices) : 0;
        $maxTicketsPrice = !empty($ticketPrices) ? max($ticketPrices) : 1;

        // Параметры для пагинации
        $filters = [
            'departure' => $filterDeparture,
            'arrival' => $filterArrival,
            'weekDay' => $filterDate !== "today" ? $weekDay : null
        ];

        $totalTickets = $this->ticketRepository->countTickets($filters);
        $perPage = 6;
        $currentPage = $request->get('page', 1);

        $pagination = [
            'total' => $totalTickets,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'from' => ($currentPage - 1) * $perPage,
            'last_page' => ceil($totalTickets / $perPage)
        ];

        // Получаем список городов для фильтра
        $cities = $this->cityRepository->getCitiesForFilter($lang);
        
        // Получаем словарь переводов
        $translationRepository = new \App\Repository\Site\TranslationRepository();
        $dictionary = $translationRepository->getDictionary($lang);
        
        // Получение билетов через сервис
        $ticketParams = new TicketParams(
            $filterDeparture,
            $filterArrival,
            $filterDate,
            $lang
        );

        $tickets = $this->ticketService->get($ticketParams);

        // Обработка данных для каждого билета
        $processedTickets = [];
        foreach ($tickets as $ticket) {
            $processedTicket = $this->processTicketData($ticket, $filterDeparture, $filterArrival, $filterDate);
            if ($processedTicket) {
                $processedTickets[] = $processedTicket;
            }
        }

        // Получение доступных дней для рекомендаций (если нет билетов)
        $recommendedDates = [];
        if (empty($tickets)) {
            $availableDays = $this->ticketRepository->getAvailableDays($filterDeparture, $filterArrival);
            $months = $this->cityRepository->getMonths();
            $recommendedDates = $this->calculateRecommendedDates($availableDays, $months);
        }

        // Формирование заголовка страницы
        $pageTitle = '';
        if ($filterDeparture && $filterArrival) {
            $pageTitle = sprintf(
                '%s - %s %s %s %s',
                $departureCityTitle['title'] ?? '',
                $arrivalCityTitle['title'] ?? '',
                __('dictionary.MSG_MSG_TICKETS_NA'),
                date('d', strtotime($filterDate)),
                $filterMonth['title'] ?? ''
            );
        }

        $Router = new \App\Service\DbRouter\Router();

        return view('ticket.index', compact(
            'tickets',
            'processedTickets',
            'filterDeparture',
            'filterArrival',
            'filterDate',
            'adults',
            'kids',
            'minTicketsPrice',
            'maxTicketsPrice',
            'pagination',
            'recommendedDates',
            'pageTitle',
            'departureCityTitle',
            'arrivalCityTitle',
            'filterMonth',
            'weekDay',
            'Router',
            'cities',
            'dictionary',
            'lang'
        ));
    }

    /**
     * AJAX обработчик
     */
    public function ajax(Request $request, string $lang): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $requestType = $request->input('request');

        switch ($requestType) {
            case 'remember_ticket':
                return $this->rememberTicket($request);

            case 'route_details':
                return $this->getRouteDetails($request);

            case 'filter':
                return $this->filterTickets($request);

            default:
                return response()->json(['error' => 'Unknown request type'], 400);
        }
    }

    /**
     * Запомнить выбранный билет
     */
    protected function rememberTicket(Request $request): JsonResponse
    {
        try {
            $ticketId = $request->input('id');
            $date = $request->input('date');
            $passengers = $request->input('passengers');
            $departure = $request->input('departure');
            $arrival = $request->input('arrival');
            $fromCity = $request->input('fromCity');
            $toCity = $request->input('toCity');

            // Проверка, не прошёл ли рейс
            $currentTime = time();
            $departureTime = strtotime($date);

            if ($departureTime < $currentTime) {
                return response()->json(['data' => 'late']);
            }

            // Сохранение в сессию в формате, ожидаемом booking.php
            $_SESSION['order'] = [
                'tour_id' => $ticketId,
                'from' => $departure,  // ID остановки посадки
                'to' => $arrival,       // ID остановки высадки
                'passengers' => $passengers,
                'date' => $date,
                'from_city' => $fromCity,
                'to_city' => $toCity
            ];

            // Также сохраняем в новом формате для совместимости
            $_SESSION['selected_ticket'] = [
                'id' => $ticketId,
                'date' => $date,
                'passengers' => $passengers,
                'departure' => $departure,
                'arrival' => $arrival,
                'from_city' => $fromCity,
                'to_city' => $toCity
            ];

            return response()->json(['data' => 'ok']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Получить детали маршрута
     */
    protected function getRouteDetails(Request $request): JsonResponse
    {
        try {
            $tourId = $request->input('id');
            $departureId = $request->input('departure');
            $arrivalId = $request->input('arrival');

            // Получаем остановки маршрута
            $stops = $this->ticketRepository->getTicketStops($tourId);

            // Формируем HTML для popup
            $html = view('ticket.partials.route_details', [
                'stops' => $stops,
                'tourId' => $tourId,
                'departureId' => $departureId,
                'arrivalId' => $arrivalId
            ])->render();

            return response()->json(['data' => $html]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'err'], 500);
        }
    }

    /**
     * Фильтрация билетов
     */
    protected function filterTickets(Request $request): JsonResponse
    {
        try {
            $lang = $this->router->lang ?? 'ru';
            $this->ticketRepository->setLanguage($lang);

            // Получение параметров фильтра
            $filters = [
                'stops' => $request->input('stops'),
                'departure_time' => $request->input('departure_time', []),
                'arrival_time' => $request->input('arrival_time', []),
                'departure_station' => $request->input('departure_station', []),
                'arrival_station' => $request->input('arrival_station', []),
                'comfort' => $request->input('comfort', []),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'departure_city' => $request->input('departure_city'),
                'arrival_city' => $request->input('arrival_city'),
                'date' => $request->input('date'),
                'sort_option' => $request->input('sort_option'),
                'sort_direction' => $request->input('sort_direction')
            ];

            // Получение отфильтрованных билетов
            $ticketParams = new TicketParams(
                $filters['departure_city'],
                $filters['arrival_city'],
                $filters['date'],
                $lang
            );

            $tickets = $this->ticketService->getFiltered($ticketParams, $filters);

            // Обработка данных для каждого билета
            $processedTickets = [];
            foreach ($tickets as $ticket) {
                $processedTicket = $this->processTicketData(
                    $ticket,
                    $filters['departure_city'],
                    $filters['arrival_city'],
                    $filters['date']
                );
                if ($processedTicket) {
                    $processedTickets[] = $processedTicket;
                }
            }

            // Сортировка билетов
            $processedTickets = $this->sortTickets($processedTickets, $filters['sort_option'], $filters['sort_direction']);

            // Генерация HTML
            $html = view('ticket.partials.ticket_list', [
                'tickets' => $processedTickets,
                'filterDeparture' => $filters['departure_city'],
                'filterArrival' => $filters['arrival_city']
            ])->render();

            return response()->json($html);
        } catch (\Exception $e) {
            return response()->json('err', 500);
        }
    }

    /**
     * Сортировка билетов
     */
    protected function sortTickets(array $tickets, $sortOption, $sortDirection): array
    {
        $sortField = 'ticket_price'; // по умолчанию

        switch ($sortOption) {
            case '1':
                $sortField = 'ticket_price';
                break;
            case '2':
                $sortField = 'dep_time';
                break;
            case '3':
                $sortField = 'arr_time';
                break;
            case '4':
                $sortField = 'popularity'; // нужно добавить поле популярности
                break;
        }

        usort($tickets, function($a, $b) use ($sortField, $sortDirection) {
            $result = 0;

            if (isset($a[$sortField]) && isset($b[$sortField])) {
                if ($a[$sortField] < $b[$sortField]) {
                    $result = -1;
                } elseif ($a[$sortField] > $b[$sortField]) {
                    $result = 1;
                }
            }

            // Если направление сортировки DESC (2), меняем порядок
            if ($sortDirection == '2') {
                $result = -$result;
            }

            return $result;
        });

        return $tickets;
    }

    /**
     * Обработка данных билета
     */
    protected function processTicketData(array $ticket, int $filterDeparture, int $filterArrival, string $filterDate): array
    {
        $ticketStops = $this->ticketRepository->getTicketStops($ticket['id']);

        $tourDeparture = $filterDeparture > 0 ? $filterDeparture : $ticket['departure'];
        $tourArrival = $filterArrival > 0 ? $filterArrival : $ticket['arrival'];

        $ticketDepartureDate = $filterDate;
        if ($filterDate == 'today') {
            $ticketDepartureDate = $this->findNearestDayOfWeek(date('Y-m-d'), explode(',', $ticket['days']));
        }

        $dateArray = explode('-', $ticketDepartureDate);
        $month = $this->cityRepository->getMonthTitle((int)$dateArray[1]);
        $departureDate = $dateArray[2] . ' ' . $month['title'] . ' ' . $dateArray[0];

        $departureDetails = $this->ticketRepository->getDepartureDetails($ticket['id'], $tourDeparture);
        $arrivalDetails = $this->ticketRepository->getArrivalDetails($ticket['id'], $tourArrival);

        if (!$departureDetails || !$arrivalDetails) {
            return [];
        }

        $rideTime = $this->calculateTotalTravelTime(
            $ticketStops,
            $departureDetails['id'],
            $arrivalDetails['id'],
            $arrivalDetails['arrival_day'] ?? 0
        );

        $international = ($ticket['departure_city_section_id'] != $ticket['arrival_city_section_id']);

        $ticketPrice = $this->ticketRepository->getTicketPrice(
            $ticket['id'],
            $departureDetails['id'],
            $arrivalDetails['id']
        );

        return array_merge($ticket, [
            'departure_date_formatted' => $departureDate,
            'departure_details' => $departureDetails,
            'arrival_details' => $arrivalDetails,
            'ride_time' => $rideTime,
            'international' => $international,
            'ticket_price' => $ticketPrice,
            'ticket_stops' => $ticketStops
        ]);
    }

    /**
     * Расчет рекомендуемых дат
     */
    protected function calculateRecommendedDates(array $availableDays, array $months): array
    {
        $recommendedDates = [];

        foreach ($availableDays as $dayOfWeek) {
            $currentWeekDay = date('N');
            $nearestDay = ($currentWeekDay <= $dayOfWeek)
                ? ($dayOfWeek - $currentWeekDay)
                : (7 - $currentWeekDay + $dayOfWeek);

            $nearestDate = date('Y-m-d', strtotime("+$nearestDay days"));
            $date = date('d', strtotime("+$nearestDay days"));
            $monthId = date('n', strtotime($nearestDate));
            $month = $months[$monthId] ?? '';

            $recommendedDates[] = [
                'date' => $nearestDate,
                'day' => $date,
                'month' => $month
            ];
        }

        return $recommendedDates;
    }

    /**
     * Найти ближайший день недели
     */
    protected function findNearestDayOfWeek(string $startDate, array $daysOfWeek): string
    {
        $startTimestamp = strtotime($startDate);
        $startDayOfWeek = date('N', $startTimestamp);

        // Сортируем дни недели
        sort($daysOfWeek);

        // Ищем ближайший день
        foreach ($daysOfWeek as $day) {
            if ($day >= $startDayOfWeek) {
                $daysToAdd = $day - $startDayOfWeek;
                return date('Y-m-d', strtotime("+$daysToAdd days", $startTimestamp));
            }
        }

        // Если не нашли в текущей неделе, берем первый день следующей недели
        $daysToAdd = 7 - $startDayOfWeek + $daysOfWeek[0];
        return date('Y-m-d', strtotime("+$daysToAdd days", $startTimestamp));
    }

    /**
     * Расчет общего времени поездки
     */
    protected function calculateTotalTravelTime(array $stops, int $departureId, int $arrivalId, int $arrivalDay = 0): string
    {
        $departureTime = null;
        $arrivalTime = null;

        foreach ($stops as $stop) {
            if ($stop->stop_id == $departureId) {
                $departureTime = $stop->departure_time;
            }
            if ($stop->stop_id == $arrivalId) {
                $arrivalTime = $stop->arrival_time;
                break;
            }
        }

        if (!$departureTime || !$arrivalTime) {
            return '00:00';
        }

        $departure = strtotime($departureTime);
        $arrival = strtotime($arrivalTime);

        // Если прибытие на следующий день
        if ($arrivalDay > 0) {
            $arrival += (86400 * $arrivalDay); // Добавляем дни
        }

        // Если время прибытия меньше времени отправления
        if ($arrival < $departure) {
            $arrival += 86400; // Добавляем день
        }

        $diff = $arrival - $departure;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    /**
     * Страница ввода данных пассажиров
     */
    public function data(Request $request)
    {
        // Проверяем, что билет выбран
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['selected_ticket'])) {
            return redirect()->route('tickets.index')
                ->with('error', 'Пожалуйста, сначала выберите билет');
        }

        $selectedTicket = $_SESSION['selected_ticket'];
        $lang = $this->router->lang ?? 'ru';

        // Получаем информацию о выбранном билете
        $this->ticketRepository->setLanguage($lang);
        $this->cityRepository->setLanguage($lang);

        // Здесь добавьте логику для страницы данных пассажиров
        // Пока возвращаем заглушку
        return view('ticket.data', [
            'selectedTicket' => $selectedTicket,
            'lang' => $lang
        ]);
    }

    /**
     * Страница оплаты
     */
    public function payment(Request $request)
    {
        // Проверяем, что данные пассажиров введены
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['selected_ticket']) || !isset($_SESSION['passenger_data'])) {
            return redirect()->route('tickets.index')
                ->with('error', 'Пожалуйста, сначала выберите билет и введите данные пассажиров');
        }

        $selectedTicket = $_SESSION['selected_ticket'];
        $passengerData = $_SESSION['passenger_data'] ?? [];
        $lang = $this->router->lang ?? 'ru';

        // Здесь добавьте логику для страницы оплаты
        // Пока возвращаем заглушку
        return view('ticket.payment', [
            'selectedTicket' => $selectedTicket,
            'passengerData' => $passengerData,
            'lang' => $lang
        ]);
    }
}
