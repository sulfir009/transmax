<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\ClientRepository;
use App\Repository\BusRepository;
use App\Repository\PhoneCodeRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class BookingController extends Controller
{
    protected $router;
    protected $db;
    protected $user;
    protected $clientRepository;
    protected $busRepository;
    protected $phoneCodeRepository;

    public function __construct(
        ClientRepository $clientRepository = null,
        BusRepository $busRepository = null,
        PhoneCodeRepository $phoneCodeRepository = null
    ) {
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'mt');
        }

        global $Router, $Db, $User;
        $this->router = $Router;
        $this->db = $Db;
        $this->user = $User;

        $this->clientRepository = $clientRepository ?: new ClientRepository();
        $this->busRepository = $busRepository ?: new BusRepository();
        $this->phoneCodeRepository = $phoneCodeRepository ?: new PhoneCodeRepository();
    }

    /**
     * Отображение страницы оформления билета
     */
    public function index(Request $request)
    {
        // Инициализация сессии если нужно
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Проверяем, что билет выбран
        if (!isset($_SESSION['order']['tour_id'])) {
            return redirect()->route('main');
        }

        $lang = $this->router->lang ?? 'ru';

        // Получаем информацию о билете
        $ticketInfo = $this->getTicketInfo(
            $_SESSION['order']['tour_id'],
            $_SESSION['order']['from'],
            $_SESSION['order']['to']
        );

        // Получаем информацию о клиенте
        $clientInfo = [];
        if ($this->user && $this->user->id) {
            $clientInfo = $this->clientRepository->getClientInfo($this->user->id);
        }

        // Получаем телефонные коды
        $phoneCodes = $this->phoneCodeRepository->getActiveCodes();

        // Получаем первый телефонный код
        $firstPhoneData = $this->getFirstPhoneData($clientInfo, $phoneCodes);

        // Получаем опции автобуса
        $busOptions = $this->busRepository->getBusOptions($ticketInfo['bus_id'] ?? null);

        // Рассчитываем общую стоимость
        $passengers = $_SESSION['order']['passengers'] ?? 1;
        $totalPrice = $passengers * ($ticketInfo['price'] ?? 0);

        // Форматируем дату для отображения
        $tourDate = $_SESSION['order']['date'] ?? date('Y-m-d');
        $formattedDate = $this->formatDateForDisplay($tourDate, $lang);
        $_SESSION['order']['departure_time'] = $ticketInfo['departure_time'] ?? '';

        $viewData = [
            'ticketInfo' => $ticketInfo,
            'clientInfo' => $clientInfo,
            'phoneCodes' => $phoneCodes,
            'firstPhoneExample' => $firstPhoneData['example'],
            'firstPhoneMask' => $firstPhoneData['mask'],
            'busOptions' => $busOptions,
            'passengers' => $passengers,
            'totalPrice' => $totalPrice,
            'order' => $_SESSION['order'],
            'tourDate' => $tourDate,
            'formattedDate' => $formattedDate,
            'Router' => $this->router,
            'lang' => $lang
        ];

        return view('booking.index', $viewData);
    }

    /**
     * AJAX обработчики
     */
    public function ajax(Request $request, string $lang = 'ru'): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $requestType = $request->input('request');

        switch ($requestType) {
            case 'check_OrderTicket':
                return $this->checkOrderTicket($request);

            case 'remember_private_data':
                return $this->rememberPrivateData($request);

            default:
                return response()->json(['error' => 'Unknown request type'], 400);
        }
    }

    /**
     * Проверка доступности билета
     */
  protected function checkOrderTicket(Request $request): JsonResponse
{
    try {
        // -----------------------------
        // 1) СИНХРОНИЗАЦИЯ КОЛ-ВА ПАССАЖИРОВ В СЕССИИ
        // Поддержка разных имен параметра, чтобы не ломать фронт:
        // passengers_count / passengers
        // -----------------------------
        $pcRaw = $request->input('passengers_count', $request->input('passengers', null));

        if ($pcRaw !== null) {
            $pc = (int) $pcRaw;
            $pc = max(1, min(10, $pc));

            $oldPc = (int) ($_SESSION['order']['passengers'] ?? 1);
            $oldPc = max(1, min(10, $oldPc));

            $_SESSION['order']['passengers'] = $pc;

            // Если пассажиров стало меньше — чистим хвост доп.пассажиров в сессии
            if ($pc < $oldPc) {
                $keepExtra = max(0, $pc - 1);

                // 1) passenger_data.passengers (заполняется remember_private_data)
                if (isset($_SESSION['passenger_data']['passengers']) && is_array($_SESSION['passenger_data']['passengers'])) {
                    $_SESSION['passenger_data']['passengers'] = array_slice($_SESSION['passenger_data']['passengers'], 0, $keepExtra);
                    if ($keepExtra === 0) {
                        $_SESSION['passenger_data']['passengers'] = [];
                    }
                }

                // 2) order.passengers_extra (если где-то у тебя такое используется)
                if (isset($_SESSION['order']['passengers_extra']) && is_array($_SESSION['order']['passengers_extra'])) {
                    $_SESSION['order']['passengers_extra'] = array_slice($_SESSION['order']['passengers_extra'], 0, $keepExtra);
                    if ($keepExtra === 0) {
                        $_SESSION['order']['passengers_extra'] = [];
                    }
                }
            }
        }

        // -----------------------------
        // 2) ТВОЯ ПРОВЕРКА ДАТЫ (как было)
        // -----------------------------
        if (isset($_SESSION['order']['date'])) {
            $currentTime = time();
            $departureTime = strtotime($_SESSION['order']['date']);

            if ($departureTime < $currentTime) {
                return response()->json('late');
            }
        }

        return response()->json('ok');

    } catch (\Exception $e) {
        return response()->json('error', 500);
    }
}


    /**
     * Сохранение данных пассажиров
     */
 /**
 * Сохранение данных пассажиров
 */
protected function rememberPrivateData(Request $request): JsonResponse
{
    try {
        $validated = $request->validate([
            'family_name' => 'required|string|max:255',
            'name' => 'required|string|max:255',
            'patronymic' => 'nullable|string|max:255',
            'birthDate' => 'nullable|date',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'phone_code' => 'required|integer',
            'save_data' => 'nullable|boolean',

            // массив доп.пассажиров
            'passengers' => 'nullable|array',

            // ДОБАВЛЯЕМ (не обязательно, но очень полезно)
            // если фронт может отправить число пассажиров явно
            'passengers_count' => 'nullable|integer|min:1|max:10',
        ]);

        // -----------------------------
        // 1) Сохраняем пассажирские данные (как было)
        // -----------------------------
        $_SESSION['passenger_data'] = [
            'family_name' => $validated['family_name'],
            'name' => $validated['name'],
            'patronymic' => $validated['patronymic'] ?? '',
            'birth_date' => $validated['birthDate'] ?? null,
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'phone_code' => $validated['phone_code'],
            'passengers' => $validated['passengers'] ?? []
        ];

        // -----------------------------
        // 2) НОРМАЛИЗАЦИЯ КОЛ-ВА ПАССАЖИРОВ В order
        // Логика:
        // - если пришёл passengers_count => это “истина”
        // - иначе считаем по фактически заполненным доп.пассажирам
        // -----------------------------
        $countFilledRows = function ($arr) {
            if (!is_array($arr)) return 0;
            $cnt = 0;

            foreach ($arr as $row) {
                if (!is_array($row)) continue;

                $hasAny = false;
                foreach ($row as $v) {
                    if (is_array($v)) continue;
                    $s = trim((string)$v);
                    if ($s !== '') { $hasAny = true; break; }
                }

                if ($hasAny) $cnt++;
            }

            return $cnt;
        };

        // extra пассажиры по факту заполнения
        $extraFilled = $countFilledRows($_SESSION['passenger_data']['passengers'] ?? []);

        // кандидат на итог
        if (isset($validated['passengers_count']) && $validated['passengers_count'] !== null) {
            $pc = (int) $validated['passengers_count'];
        } else {
            $pc = 1 + $extraFilled;
        }

        $pc = max(1, min(10, $pc));

        // важно: если pc = 1, то доп.пассажиров быть не должно
        $keepExtra = max(0, $pc - 1);

        if ($keepExtra === 0) {
            $_SESSION['passenger_data']['passengers'] = [];
            if (isset($_SESSION['order']['passengers_extra'])) {
                $_SESSION['order']['passengers_extra'] = [];
            }
        } else {
            // обрезаем на всякий случай
            if (isset($_SESSION['passenger_data']['passengers']) && is_array($_SESSION['passenger_data']['passengers'])) {
                $_SESSION['passenger_data']['passengers'] = array_slice($_SESSION['passenger_data']['passengers'], 0, $keepExtra);
            }
            if (isset($_SESSION['order']['passengers_extra']) && is_array($_SESSION['order']['passengers_extra'])) {
                $_SESSION['order']['passengers_extra'] = array_slice($_SESSION['order']['passengers_extra'], 0, $keepExtra);
            }
        }

        // записываем в order — вот это и решает твою проблему на оплате
        $_SESSION['order']['passengers'] = $pc;

        // -----------------------------
        // 3) Если пользователь авторизован и хочет сохранить
        // -----------------------------
        if ($this->user && $this->user->id && $request->input('save_data')) {
            $this->clientRepository->updateClientData(
                $this->user->id,
                $_SESSION['passenger_data']
            );
        }

        return response()->json(['data' => 'ok']);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}


    /**
     * Получение информации о билете
     */
    protected function getTicketInfo($tourId, $fromStop, $toStop): array
    {
        $lang = $this->router->lang ?? 'ru';
        $prefix = DB_PREFIX;

        // Исправленный запрос: правильно получаем города для конкретных остановок
        $sql = "SELECT
            from_stop.departure_time AS departure_time,
            from_stop.arrival_time AS departure_arrival_time,
            from_city.title_{$lang} AS departure_city,
            from_city.title_{$lang} AS departure_station,
            to_stop.arrival_time AS arrival_time,
            to_stop.departure_time AS arrival_departure_time,
            to_city.title_{$lang} AS arrival_city,
            to_city.title_{$lang} AS arrival_station,
            bus.title_{$lang} AS bus,
            bus.id AS bus_id,
            prices.price AS price
            FROM `{$prefix}_tours_stops` AS from_stop
            JOIN `{$prefix}_cities` AS from_city ON from_stop.stop_id = from_city.id
            JOIN `{$prefix}_tours` AS tours ON from_stop.tour_id = tours.id
            JOIN `{$prefix}_tours_stops` AS to_stop ON from_stop.tour_id = to_stop.tour_id
            JOIN `{$prefix}_cities` AS to_city ON to_stop.stop_id = to_city.id
            JOIN `{$prefix}_buses` AS bus ON tours.bus = bus.id
            JOIN `{$prefix}_tours_stops_prices` AS prices ON
                prices.tour_id = from_stop.tour_id AND
                prices.from_stop = from_stop.stop_id AND
                prices.to_stop = to_stop.stop_id
            WHERE from_stop.tour_id = ?
            AND from_stop.stop_id = ?
            AND to_stop.stop_id = ?";

        if ($this->db) {
            $result = $this->db->getOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]);
        } else {
            // Laravel DB fallback
            $result = DB::selectOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]);
            $result = $result ? (array) $result : null;
        }

        // Если города совпадают с остановками, добавляем дополнительную информацию
        if ($result) {
            // Проверяем, является ли остановка станцией или городом
            // Если station = 1, то это станция в городе, нужно добавить название города
            if ($this->db) {
                $fromCityInfo = $this->db->getOne(
                    "SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?",
                    [(int)$fromStop]
                );
                $toCityInfo = $this->db->getOne(
                    "SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?",
                    [(int)$toStop]
                );
            } else {
                $fromCityInfo = DB::selectOne(
                    "SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?",
                    [(int)$fromStop]
                );
                $toCityInfo = DB::selectOne(
                    "SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?",
                    [(int)$toStop]
                );
                $fromCityInfo = $fromCityInfo ? (array) $fromCityInfo : null;
                $toCityInfo = $toCityInfo ? (array) $toCityInfo : null;
            }

            // Если это станция (station = 1), получаем название города из section
            if ($fromCityInfo && $fromCityInfo['station'] == 1 && $fromCityInfo['section_id'] > 0) {
                if ($this->db) {
                    $parentCity = $this->db->getOne(
                        "SELECT title_{$lang} FROM `{$prefix}_cities` WHERE id = ? AND station = 0",
                        [(int)$fromCityInfo['section_id']]
                    );
                } else {
                    $parentCity = DB::selectOne(
                        "SELECT title_{$lang} as title FROM `{$prefix}_cities` WHERE id = ? AND station = 0",
                        [(int)$fromCityInfo['section_id']]
                    );
                    $parentCity = $parentCity ? (array) $parentCity : null;
                }
                if ($parentCity) {
                    $result['departure_city'] = $parentCity['title'] ?? $parentCity["title_{$lang}"] ?? $result['departure_city'];
                    $result['departure_station'] = $result['departure_station']; // Остановка остается как есть
                }
            }

            if ($toCityInfo && $toCityInfo['station'] == 1 && $toCityInfo['section_id'] > 0) {
                if ($this->db) {
                    $parentCity = $this->db->getOne(
                        "SELECT title_{$lang} FROM `{$prefix}_cities` WHERE id = ? AND station = 0",
                        [(int)$toCityInfo['section_id']]
                    );
                } else {
                    $parentCity = DB::selectOne(
                        "SELECT title_{$lang} as title FROM `{$prefix}_cities` WHERE id = ? AND station = 0",
                        [(int)$toCityInfo['section_id']]
                    );
                    $parentCity = $parentCity ? (array) $parentCity : null;
                }
                if ($parentCity) {
                    $result['arrival_city'] = $parentCity['title'] ?? $parentCity["title_{$lang}"] ?? $result['arrival_city'];
                    $result['arrival_station'] = $result['arrival_station']; // Остановка остается как есть
                }
            }
        }

        return $result ?? [];
    }

    /**
     * Получение данных первого телефонного кода
     */
    protected function getFirstPhoneData($clientInfo, $phoneCodes): array
    {
        $firstPhoneExample = '';
        $firstPhoneMask = '';

        if (!empty($clientInfo['phone_code'])) {
            $phoneData = $this->phoneCodeRepository->getPhoneCodeById($clientInfo['phone_code']);
            if ($phoneData) {
                $firstPhoneExample = $phoneData['phone_example'];
                $firstPhoneMask = $phoneData['phone_mask'];
            }
        }

        // Если нет сохраненного кода, берем первый из списка
        if (empty($firstPhoneExample) && !empty($phoneCodes)) {
            $firstCode = reset($phoneCodes);
            $firstPhoneExample = $firstCode->phone_example ?? '';
            $firstPhoneMask = $firstCode->phone_mask ?? '';
        }

        return [
            'example' => $firstPhoneExample,
            'mask' => $firstPhoneMask
        ];
    }

    /**
     * Форматирование даты для отображения
     */
    protected function formatDateForDisplay($date, $lang = 'ru'): string
    {
        $prefix = DB_PREFIX;
        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = (int)date('m', $timestamp);
        $year = date('Y', $timestamp);

        // Получаем название месяца из базы
        if ($this->db) {
            $monthData = $this->db->getOne(
                "SELECT title_{$lang} FROM `{$prefix}_months` WHERE id = ?",
                [$month]
            );
            $monthName = $monthData["title_{$lang}"] ?? '';
        } else {
            $monthData = DB::selectOne(
                "SELECT title_{$lang} as title FROM `{$prefix}_months` WHERE id = ?",
                [$month]
            );
            $monthName = $monthData ? $monthData->title : '';
        }

        return "{$day} {$monthName} {$year}";
    }
}
