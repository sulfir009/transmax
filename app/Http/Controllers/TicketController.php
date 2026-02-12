<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\TicketRepository;
use App\Repository\CityRepository;
use App\Repository\Schedule\ScheduleRepository;
use App\Repository\Races\Params\TicketParams;
use App\Service\Tour\TicketService;
use App\Service\Schedule\ScheduleService;
use App\Models\Tour;
use Illuminate\Http\Request;
use App\Helpers\TicketUrlHelper;
use Illuminate\Http\JsonResponse;
use App\Helpers\LocaleHelper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    protected TicketRepository $ticketRepository;
    protected CityRepository $cityRepository;
    protected ScheduleRepository $scheduleRepository;
    protected TicketService $ticketService;
    protected ScheduleService $scheduleService;

    protected $router;
    protected $db;
    protected array $arrivalDateDebugLogged = [];

    public function __construct(
        TicketRepository $ticketRepository,
        CityRepository $cityRepository,
        ScheduleRepository $scheduleRepository,
        TicketService $ticketService,
        ScheduleService $scheduleService
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->cityRepository   = $cityRepository;
        $this->scheduleRepository = $scheduleRepository;
        $this->ticketService    = $ticketService;
        $this->scheduleService  = $scheduleService;

        global $Router, $Db;
        $this->router = $Router;
        $this->db     = $Db;
    }

    private function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Отображение страницы с билетами
     */
    public function index(Request $request)
    {
        $this->startSession();

        $lang = $this->normalizeLang($this->resolveCurrentLang());
        $this->ticketRepository->setLanguage($lang);
        $this->cityRepository->setLanguage($lang);

        if (config('app.debug') || env('TICKETS_LOG_PARAMS')) {
            Log::debug('tickets.index request params', [
                'method' => $request->method(),
                'query' => $request->query->all(),
            ]);
        }

        $hasFromInUrl      = $request->query->has('from');
        $hasToInUrl        = $request->query->has('to');
        $hasDepartureInUrl = $request->query->has('departure');
        $hasArrivalInUrl   = $request->query->has('arrival');
        
                $routeSlug = $request->route('slug');
        $rawFrom = $request->query('from', $request->query('departure'));
        $rawTo = $request->query('to', $request->query('arrival'));

        if ($request->isMethod('get') && ($hasFromInUrl || $hasDepartureInUrl) && ($hasToInUrl || $hasArrivalInUrl)) {
            $departureId = $this->resolveCityId($rawFrom, $lang);
            $arrivalId = $this->resolveCityId($rawTo, $lang);

            if ($departureId > 0 && $arrivalId > 0) {
                $expectedSlug = TicketUrlHelper::slug($departureId, $arrivalId, $lang);
                if ($expectedSlug && $routeSlug !== $expectedSlug) {
                    $redirectUrl = TicketUrlHelper::make($departureId, $arrivalId, $request->query(), $lang);

                    return redirect()->to($redirectUrl, 301);
                }
            }
        }


        $isCleanLanding = $request->isMethod('get')
            && !$hasFromInUrl
            && !$hasToInUrl
            && !$hasDepartureInUrl
            && !$hasArrivalInUrl;

        if ($isCleanLanding) {
            // чистим только фильтр — чтобы не "липли" города
            unset($_SESSION['filter']);

            $filterDeparture = null;
            $filterArrival   = null;

            $filterDate   = date('Y-m-d');
            $adults       = 1;
            $kids         = 0;

            // совместимость с твоим blade (там используются $filterAdults / $filterKids)
            $filterAdults = $adults;
            $filterKids   = $kids;

            $cities = $this->cityRepository->getCitiesForFilter($lang);

            $translationRepository = new \App\Repository\Site\TranslationRepository();
            $dictionary = $translationRepository->getDictionary($lang);

            // чтобы view не падал
            $tickets = [];
            $processedTickets = [];
            $minTicketsPrice = 0;
            $maxTicketsPrice = 1;

            $pagination = [
                'total' => 0,
                'per_page' => 6,
                'current_page' => 1,
                'from' => 0,
                'last_page' => 1,
            ];

            $recommendedDates   = [];
            $pageTitle          = '';
            $departureCityTitle = null;
            $arrivalCityTitle   = null;
            $filterMonth        = null;
            $weekDay            = date('N');
            $popularRoutes      = $this->scheduleService->getPopularRoutesForView($lang);
            $seoText            = null;
            $seoTitle           = null;

            $Router = new \App\Service\DbRouter\Router();

            return view('ticket.index', compact(
                'tickets',
                'processedTickets',
                'filterDeparture',
                'filterArrival',
                'filterDate',
                'adults',
                'kids',
                'filterAdults',
                'filterKids',
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
                'lang',
                'popularRoutes',
                'seoText',
                'seoTitle'
            ));
        }

        /**
         * POST от формы фильтра → сохраняем в сессию → redirect на GET
         */
        if ($request->isMethod('post')) {
            $rawFrom = $request->input('from', $request->input('departure'));
            $rawTo = $request->input('to', $request->input('arrival'));
            $departureId = $this->resolveCityId($rawFrom, $lang);
            $arrivalId = $this->resolveCityId($rawTo, $lang);

            if ($departureId <= 0 || $arrivalId <= 0) {
                return redirect(LocaleHelper::localizedRoute('schedule'));
            }

            $_SESSION['filter'] = [
                'departure' => $departureId,
                'arrival'   => $arrivalId,
                'date'      => $request->input('date', date('Y-m-d')),
                'adults'    => (int)$request->input('adults', 1),
                'kids'      => (int)$request->input('kids', 0),
            ];

            return redirect()->to(TicketUrlHelper::make(
                $_SESSION['filter']['departure'],
                $_SESSION['filter']['arrival'],
                [
                    'from' => $_SESSION['filter']['departure'],
                    'to' => $_SESSION['filter']['arrival'],
                    'date' => $_SESSION['filter']['date'],
                    'adults' => $_SESSION['filter']['adults'],
                    'kids' => $_SESSION['filter']['kids'],
                ],
                $lang
            ));
        }

        /**
         * GET с параметрами:
         * ВАЖНО: если параметры есть в URL — используем ТОЛЬКО их, без "подмешивания" сессии.
         * Это важно для корректной работы плейсхолдера.
         */
        $rawFrom = $request->query('from', $request->query('departure'));
        $rawTo = $request->query('to', $request->query('arrival'));

        $filterDeparture = ($hasFromInUrl || $hasDepartureInUrl)
            ? $this->resolveCityId($rawFrom, $lang)
            : 0;
        $filterArrival = ($hasToInUrl || $hasArrivalInUrl)
            ? $this->resolveCityId($rawTo, $lang)
            : 0;

        // Остальные поля — дефолты (не тянем из session, чтобы не было "магии")
        $filterDate = (string)$request->query('date', date('Y-m-d'));
        $adults     = (int)$request->query('adults', 1);
        $kids       = (int)$request->query('kids', 0);

        // совместимость с blade
        $filterAdults = $adults;
        $filterKids   = $kids;

        // Сохраняем фильтр в сессию ТОЛЬКО если выбраны оба города (иначе снова "залипание")
        if ($filterDeparture > 0 && $filterArrival > 0) {
            $_SESSION['filter'] = [
                'departure' => $filterDeparture,
                'arrival'   => $filterArrival,
                'date'      => $filterDate,
                'adults'    => $adults,
                'kids'      => $kids,
            ];
        }

        // Названия городов
        $departureCityTitle = $filterDeparture > 0 ? $this->cityRepository->getCityTitle($filterDeparture) : null;
        $arrivalCityTitle   = $filterArrival   > 0 ? $this->cityRepository->getCityTitle($filterArrival)   : null;

        // Месяц/день недели
        $filterMonth = null;
        $weekDay = date('N', time());

        if ($filterDate !== "today") {
            $weekDay = date('N', strtotime($filterDate));
            $monthId = (int)explode('-', $filterDate)[1];
            $filterMonth = $this->cityRepository->getMonthTitle($monthId);
        }

        // Цены для слайдера
        $ticketPrices = ($filterDeparture > 0 && $filterArrival > 0)
            ? $this->ticketRepository->getTicketPrices($filterDeparture, $filterArrival)
            : [];

        $minTicketsPrice = !empty($ticketPrices) ? min($ticketPrices) : 0;
        $maxTicketsPrice = !empty($ticketPrices) ? max($ticketPrices) : 1;

        // Пагинация
        $filters = [
            'departure' => $filterDeparture,
            'arrival'   => $filterArrival,
            'weekDay'   => $filterDate !== "today" ? $weekDay : null,
        ];

        $totalTickets = ($filterDeparture > 0 && $filterArrival > 0)
            ? $this->ticketRepository->countTickets($filters)
            : 0;

        $perPage = 6;
        $currentPage = (int)$request->query('page', 1);

        $pagination = [
            'total' => $totalTickets,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'from' => ($currentPage - 1) * $perPage,
            'last_page' => max(1, (int)ceil($totalTickets / $perPage)),
        ];

        // Города + словарь
        $cities = $this->cityRepository->getCitiesForFilter($lang);

        $translationRepository = new \App\Repository\Site\TranslationRepository();
        $dictionary = $translationRepository->getDictionary($lang);

        // Билеты
        $tickets = [];
        if ($filterDeparture > 0 && $filterArrival > 0) {
            $ticketParams = new TicketParams($filterDeparture, $filterArrival, $filterDate, $lang);
            $tickets = $this->ticketService->get($ticketParams);
        }

        $processedTickets = [];
        foreach ($tickets as $ticket) {
            $processedTicket = $this->processTicketData($ticket, $filterDeparture, $filterArrival, $filterDate);
            if (!empty($processedTicket)) {
                $processedTickets[] = $processedTicket;
            }
        }

        // Рекомендованные даты
        $recommendedDates = [];
        if (($filterDeparture > 0 && $filterArrival > 0) && empty($tickets)) {
            $availableDays = $this->ticketRepository->getAvailableDays($filterDeparture, $filterArrival);
            $months = $this->cityRepository->getMonths();
            $recommendedDates = $this->calculateRecommendedDates($availableDays, $months);
        }

        // Заголовок
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

        $popularRoutes = $this->scheduleService->getPopularRoutesForView($lang);
        $seoText = $this->resolveTourSeoText($filterDeparture, $filterArrival, $lang);
        $seoTitle = $this->buildSeoTitle($departureCityTitle, $arrivalCityTitle);

        $Router = new \App\Service\DbRouter\Router();

        return view('ticket.index', compact(
            'tickets',
            'processedTickets',
            'filterDeparture',
            'filterArrival',
            'filterDate',
            'adults',
            'kids',
            'filterAdults',
            'filterKids',
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
            'lang',
            'popularRoutes',
            'seoText',
            'seoTitle'
        ));
    }

    private function buildSeoTitle(?array $departureCityTitle, ?array $arrivalCityTitle): ?string
    {
        $departure = trim((string) data_get($departureCityTitle, 'title', ''));
        $arrival = trim((string) data_get($arrivalCityTitle, 'title', ''));

        if ($departure === '' || $arrival === '') {
            return null;
        }

        return 'Автобус ' . mb_strtoupper($departure . ' — ' . $arrival, 'UTF-8');
    }

    private function resolveTourSeoText(int $departureId, int $arrivalId, string $lang): ?string
    {
        if ($departureId <= 0 || $arrivalId <= 0) {
            return null;
        }

        $tour = Tour::query()
            ->where('departure', $departureId)
            ->where('arrival', $arrivalId)
            ->where('active', '1')
            ->orderBy('id')
            ->first();

        if (!$tour) {
            return null;
        }

        $seoText = $this->getTourSeoText($tour, $lang);
        if ($seoText === null) {
            return null;
        }

        $seoText = $this->sanitizeSeoHtml($seoText);

        return $seoText !== '' ? $seoText : null;
    }

    private function getTourSeoText(Tour $tour, string $lang): ?string
    {
        $normalizedLang = $this->normalizeLang($lang);
        $fallbacks = ['ru', 'uk', 'en'];
        $locales = array_values(array_unique(array_merge([$normalizedLang], $fallbacks)));

        foreach ($locales as $locale) {
            $field = 'seo_text_' . $locale;
            $value = (string) data_get($tour, $field);
            if (trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeLang(string $lang): string
    {
        $lang = strtolower($lang);

        return match ($lang) {
            'ua' => 'uk',
            default => $lang,
        };
    }

    private function resolveCurrentLang(): string
    {
        $appLocale = app()->getLocale();
        if (!empty($appLocale)) {
            return $appLocale;
        }

        return $this->router->lang ?? 'ru';
    }

    private function sanitizeSeoHtml(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        $allowedTags = '<p><br><ul><ol><li><strong><em><h2><h3><h4><h5><h6>';
        $clean = strip_tags($html, $allowedTags);

        $clean = preg_replace('/<([a-z0-9]+)(\\s[^>]*)?>/i', '<$1>', $clean);

        return trim($clean);
    }

    private function resolveCityId($value, string $lang): int
    {
        if ($value === null || $value === '') {
            return 0;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        $city = $this->scheduleRepository->getCityBySlug((string)$value, $lang);

        return $city ? (int)$city->id : 0;
    }

    /**
     * AJAX обработчик
     */
    public function ajax(Request $request, string $lang): JsonResponse
    {
        $this->startSession();

        $requestType = $request->input('request');

        return match ($requestType) {
            'remember_ticket' => $this->rememberTicket($request),
            'route_details'   => $this->getRouteDetails($request),
            'filter'          => $this->filterTickets($request),
            default           => response()->json(['error' => 'Unknown request type'], 400),
        };
    }

    protected function rememberTicket(Request $request): JsonResponse
    {
        try {
            $ticketId    = $request->input('id');
            $date        = $request->input('date');
            $passengers  = $request->input('passengers');
            $departure   = $request->input('departure');
            $arrival     = $request->input('arrival');
            $fromCity    = $request->input('fromCity');
            $toCity      = $request->input('toCity');

            $currentTime   = time();
            $departureTime = strtotime($date);

            if ($departureTime < $currentTime) {
                return response()->json(['data' => 'late']);
            }

            $_SESSION['order'] = [
                'tour_id'     => $ticketId,
                'from'        => $departure,
                'to'          => $arrival,
                'passengers'  => $passengers,
                'date'        => $date,
                'from_city'   => $fromCity,
                'to_city'     => $toCity,
            ];

            $_SESSION['selected_ticket'] = [
                'id'         => $ticketId,
                'date'       => $date,
                'passengers' => $passengers,
                'departure'  => $departure,
                'arrival'    => $arrival,
                'from_city'  => $fromCity,
                'to_city'    => $toCity,
            ];

            return response()->json(['data' => 'ok']);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    protected function getRouteDetails(Request $request): JsonResponse
    {
        try {
            $tourId      = $request->input('id');
            $departureId = $request->input('departure');
            $arrivalId   = $request->input('arrival');

            $stops = $this->ticketRepository->getTicketStops($tourId);

            $html = view('ticket.partials.route_details', [
                'stops'       => $stops,
                'tourId'      => $tourId,
                'departureId' => $departureId,
                'arrivalId'   => $arrivalId,
            ])->render();

            return response()->json(['data' => $html]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'err'], 500);
        }
    }

    protected function filterTickets(Request $request): JsonResponse
    {
        try {
            $lang = $this->router->lang ?? 'ru';
            $this->ticketRepository->setLanguage($lang);

            $filters = [
                'stops'             => $request->input('stops'),
                'departure_time'    => $request->input('departure_time', []),
                'arrival_time'      => $request->input('arrival_time', []),
                'departure_station' => $request->input('departure_station', []),
                'arrival_station'   => $request->input('arrival_station', []),
                'comfort'           => $request->input('comfort', []),
                'min_price'         => $request->input('min_price'),
                'max_price'         => $request->input('max_price'),
                'departure_city'    => (int)$request->input('departure_city'),
                'arrival_city'      => (int)$request->input('arrival_city'),
                'date'              => $request->input('date'),
                'sort_option'       => $request->input('sort_option'),
                'sort_direction'    => $request->input('sort_direction'),
            ];

            $ticketParams = new TicketParams(
                $filters['departure_city'],
                $filters['arrival_city'],
                $filters['date'],
                $lang
            );

            $tickets = $this->ticketService->getFiltered($ticketParams, $filters);

            $processedTickets = [];
            foreach ($tickets as $ticket) {
                $processedTicket = $this->processTicketData(
                    $ticket,
                    $filters['departure_city'],
                    $filters['arrival_city'],
                    $filters['date']
                );

                if (!empty($processedTicket)) {
                    $processedTickets[] = $processedTicket;
                }
            }

            $processedTickets = $this->sortTickets(
                $processedTickets,
                $filters['sort_option'],
                $filters['sort_direction']
            );

            $html = view('ticket.partials.ticket_list', [
                'tickets' => $processedTickets,
                'filterDeparture' => $filters['departure_city'],
                'filterArrival' => $filters['arrival_city'],
            ])->render();

            return response()->json($html);
        } catch (\Throwable $e) {
            return response()->json('err', 500);
        }
    }

    protected function sortTickets(array $tickets, $sortOption, $sortDirection): array
    {
        $sortField = 'ticket_price';

        switch ($sortOption) {
            case '1': $sortField = 'ticket_price'; break;
            case '2': $sortField = 'dep_time'; break;
            case '3': $sortField = 'arr_time'; break;
            case '4': $sortField = 'popularity'; break;
        }

        usort($tickets, function ($a, $b) use ($sortField, $sortDirection) {
            $av = $a[$sortField] ?? null;
            $bv = $b[$sortField] ?? null;

            if ($av == $bv) return 0;

            $result = ($av < $bv) ? -1 : 1;

            if ((string)$sortDirection === '2') {
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

        $departureAt = $this->buildDateTimeFromDateAndTime($ticketDepartureDate, (string) ($ticket['dep_time'] ?? ''));
        $arrivalAt = $this->resolveArrivalAt($ticket, $ticketDepartureDate, $departureAt);

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
            'departure_date_formatted' => $departureAt?->format('d.m.Y'),
            'arrival_date_formatted' => $arrivalAt?->format('d.m.Y'),
            'departure_details' => $departureDetails,
            'arrival_details' => $arrivalDetails,
            'ride_time' => $rideTime,
            'international' => $international,
            'ticket_price' => $ticketPrice,
            'ticket_stops' => $ticketStops
        ]);
    }

    protected function resolveArrivalAt(array $ticket, string $filterDate, ?Carbon $departureAt): ?Carbon
    {
        // A) Явная дата и время прибытия в данных билета
        foreach (['arrival_datetime', 'arrival_at', 'arrive_at', 'arriveDateTime'] as $field) {
            $value = $ticket[$field] ?? null;
            if (!empty($value)) {
                try {
                    return Carbon::parse($value);
                } catch (\Throwable $e) {
                    $this->logArrivalDebugOnce($ticket, 'invalid_arrival_datetime', ['field' => $field]);
                }
            }
        }

        // B) Длительность поездки
        $durationMinutes = $this->extractDurationMinutes($ticket);
        if ($durationMinutes !== null && $departureAt !== null) {
            return $departureAt->copy()->addMinutes($durationMinutes);
        }

        // C) Только время прибытия
        $arrivalTime = (string) ($ticket['arr_time'] ?? '');
        if ($departureAt !== null && $this->isTimeValue($arrivalTime)) {
            $arrivalAt = $this->buildDateTimeFromDateAndTime($filterDate, $arrivalTime);
            if ($arrivalAt === null) {
                return null;
            }

            if ($arrivalAt->lt($departureAt)) {
                $arrivalAt->addDay();
            }

            return $arrivalAt;
        }

        // D) Недостаточно данных
        $this->logArrivalDebugOnce($ticket, 'missing_arrival_data', [
            'has_departure_at' => $departureAt !== null,
            'has_arr_time' => !empty($arrivalTime),
        ]);

        return null;
    }

    protected function extractDurationMinutes(array $ticket): ?int
    {
        $durationSources = [
            $ticket['duration_minutes'] ?? null,
            $ticket['duration'] ?? null,
            $ticket['travel_time'] ?? null,
            $ticket['ride_time'] ?? null,
        ];

        foreach ($durationSources as $duration) {
            if ($duration === null || $duration === '') {
                continue;
            }

            if (is_numeric($duration)) {
                return (int) $duration;
            }

            $duration = trim((string) $duration);
            if (preg_match('/^(\d{1,3}):(\d{1,2})$/', $duration, $matches)) {
                return ((int) $matches[1] * 60) + (int) $matches[2];
            }

            if (preg_match('/(?:(\d+)\s*h)?\s*(?:(\d+)\s*m)?/i', $duration, $matches)) {
                $hours = isset($matches[1]) && $matches[1] !== '' ? (int) $matches[1] : 0;
                $minutes = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 0;
                if ($hours > 0 || $minutes > 0) {
                    return ($hours * 60) + $minutes;
                }
            }
        }

        return null;
    }

    protected function buildDateTimeFromDateAndTime(string $date, string $time): ?Carbon
    {
        if (empty($date) || !$this->isTimeValue($time)) {
            return null;
        }

        try {
            return Carbon::parse($date . ' ' . $time);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function isTimeValue(string $value): bool
    {
        return (bool) preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', trim($value));
    }

    protected function logArrivalDebugOnce(array $ticket, string $reason, array $context = []): void
    {
        $ticketId = $ticket['id'] ?? 'unknown';
        $key = $ticketId . ':' . $reason;

        if (isset($this->arrivalDateDebugLogged[$key])) {
            return;
        }

        $this->arrivalDateDebugLogged[$key] = true;

        Log::debug('tickets.arrival_date_resolution', array_merge([
            'ticket_id' => $ticketId,
            'reason' => $reason,
        ], $context));
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
            return redirect()->to(LocaleHelper::localizedRoute('tickets.index'))
                ->with('error', 'Пожалуйста, сначала выберите билет');
        }

        $selectedTicket = $_SESSION['selected_ticket'];
        $lang = $this->router->lang ?? 'ru';

        // Получаем информацию о выбранном билете
        $this->ticketRepository->setLanguage($lang);
        $this->cityRepository->setLanguage($lang);

$lang = $this->router->lang ?? 'ru';

$this->cityRepository->setLanguage($lang);

// filter из сессии (как в index)
$filterDeparture = $_SESSION['filter']['departure'] ?? 0;
$filterArrival   = $_SESSION['filter']['arrival'] ?? 0;
$filterDate      = $_SESSION['filter']['date'] ?? date('Y-m-d');
$adults          = $_SESSION['filter']['adults'] ?? 1;
$kids            = $_SESSION['filter']['kids'] ?? 0;

// города + словарь
$cities = $this->cityRepository->getCitiesForFilter($lang);
$translationRepository = new \App\Repository\Site\TranslationRepository();
$dictionary = $translationRepository->getDictionary($lang);

        // Здесь добавьте логику для страницы данных пассажиров
        // Пока возвращаем заглушку
return view('ticket.data', compact(
    'selectedTicket',
    'lang',
    'cities',
    'dictionary',
    'filterDeparture',
    'filterArrival',
    'filterDate',
    'adults',
    'kids'
));

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
            return redirect()->to(LocaleHelper::localizedRoute('tickets.index'))
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
