<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\ClientRepository;
use App\Repository\BusRepository;
use App\Repository\PhoneCodeRepository;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Client;
use App\Services\BonusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Models\Order;

class BookingController extends Controller
{
    protected $router;
    protected $db;
    protected $user;
    protected $clientRepository;
    protected $busRepository;
    protected $phoneCodeRepository;
    protected $bonusService;

    // Массив для хранения логов, которые улетят в браузер
    protected $debugLog = [];

    public function __construct(
        ClientRepository $clientRepository = null,
        BusRepository $busRepository = null,
        PhoneCodeRepository $phoneCodeRepository = null,
        BonusService $bonusService = null
        
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
        $this->bonusService = $bonusService ?: app(BonusService::class);
    }

    // Вспомогательная функция для записи логов
    private function addLog($msg) {
        $this->debugLog[] = date('H:i:s') . " | " . $msg;
    }

    public function index(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['order']['tour_id'])) {
            return redirect()->route('main');
        }

        $lang = $this->router->lang ?? 'ru';
        $ticketInfo = $this->getTicketInfo($_SESSION['order']['tour_id'], $_SESSION['order']['from'], $_SESSION['order']['to']);

        $clientInfo = [];
        $bonusBalanceCents = 0;
        $bonusBalanceFormatted = null;
        $bonusEligible = false;
        if ($this->user && $this->user->id) {
            $clientInfo = $this->clientRepository->getClientInfo($this->user->id);
                        $clientModel = Client::find((int) $this->user->id);
            if ($clientModel) {
                $bonusEligible = true;
                $bonusBalanceCents = (int) $clientModel->bonus_balance_cents;
                $bonusBalanceFormatted = $this->bonusService->formatToUah($bonusBalanceCents);
            }
        }

        $phoneCodes = $this->phoneCodeRepository->getActiveCodes();
        $firstPhoneData = $this->getFirstPhoneData($clientInfo, $phoneCodes);
        $busOptions = $this->busRepository->getBusOptions($ticketInfo['bus_id'] ?? null);

        $passengers = $_SESSION['order']['passengers'] ?? 1;
        $totalPrice = $passengers * ($ticketInfo['price'] ?? 0);
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
                        'bonusBalanceCents' => $bonusBalanceCents,
            'bonusBalanceFormatted' => $bonusBalanceFormatted,
            'bonusEligible' => $bonusEligible,
            'Router' => $this->router,
            'lang' => $lang
        ];

        return view('booking.index', $viewData);
    }

    public function applyBonuses(Request $request, Order $order): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!$this->user || !$this->user->id) {
            return response()->json(['error' => 'unauthorized'], 403);
        }

        if ((int) $order->client_id !== (int) $this->user->id) {
            return response()->json(['error' => 'forbidden'], 403);
        }

        $data = $request->validate([
            'use_bonus' => 'nullable|boolean',
            'payable_cents' => 'required|integer|min:0',
        ]);

        $useBonus = !empty($data['use_bonus']);
        $payableCents = (int) ($data['payable_cents'] ?? 0);

        $client = Client::find((int) $order->client_id);
        if (!$client) {
            return response()->json(['error' => 'client_not_found'], 404);
        }

        $balanceCents = (int) $client->bonus_balance_cents;
        $redeemCents = $useBonus ? $this->bonusService->calculateMaxRedeemCents($balanceCents, $payableCents) : 0;

        $order->bonus_use_requested = $useBonus ? 1 : 0;
        $order->bonus_redeemed_cents = $redeemCents;
        $order->save();

        $_SESSION['order']['use_bonus'] = $useBonus ? 1 : 0;
        $_SESSION['order']['bonus_redeem_cents_preview'] = $redeemCents;

        return response()->json([
            'redeem_cents' => $redeemCents,
            'pay_cents' => max($payableCents - $redeemCents, 0),
            'balance_cents' => $balanceCents,
        ]);
    }

public function ajax(Request $request, string $lang = 'ru'): JsonResponse
    {
        die("REAL_FILE_PATH: " . __FILE__);
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
     * Проверка билета (ИСПРАВЛЕНО: ЗАЩИТА ОТ ПЕРЕЗАПИСИ СЕССИИ)
     */
    protected function checkOrderTicket(Request $request): JsonResponse
    {
        try {
            // Читаем дату из сессии, пока она открыта
            $orderDate = $_SESSION['order']['date'] ?? null;

            // !!! ВАЖНО !!!
            // Закрываем сессию для записи НЕМЕДЛЕННО.
            // Это предотвратит перезапись количества пассажиров старым значением,
            // если параллельно выполняется remember_private_data.
            session_write_close(); 

            // Дальше работаем только с локальными переменными
            if ($orderDate) {
                $currentTime = time();
                $departureTime = strtotime($orderDate);

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
     * Сохранение данных (ИСПРАВЛЕНО: ПРИНУДИТЕЛЬНАЯ ЗАПИСЬ)
     */
    /**
     * Сохранение данных пассажиров (ФИНАЛ: ПРИОРИТЕТ ЦИФРЫ)
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
                'passengers' => 'nullable|array',
                // Разрешаем принимать явное число
                'passengers_count' => 'nullable|integer', 
            ]);

            // 1. Формируем массив пассажиров (на всякий случай чистим)
            $rawPassengers = $request->input('passengers', []);
            if (!is_array($rawPassengers)) {
                $rawPassengers = [];
            }

            $cleanPassengers = [];
            foreach ($rawPassengers as $p) {
                if (!is_array($p)) continue;
                $fName = trim($p['family_name'] ?? '');
                $name = trim($p['name'] ?? '');

                if ($fName !== '' || $name !== '') {
                    $cleanPassengers[] = [
                        'family_name' => $fName,
                        'name' => $name,
                        'patronymic' => trim($p['patronymic'] ?? ''),
                        'birth_date' => trim($p['birthdate'] ?? $p['birth_date'] ?? ''),
                    ];
                }
            }

            // 2. Сохраняем данные в сессию
            $_SESSION['passenger_data'] = [
                'family_name' => $validated['family_name'],
                'name' => $validated['name'],
                'patronymic' => $validated['patronymic'] ?? '',
                'birth_date' => $validated['birthDate'] ?? null,
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'phone_code' => $validated['phone_code'],
                'passengers' => $cleanPassengers
            ];

            // 3. ОПРЕДЕЛЯЕМ ИТОГОВОЕ КОЛИЧЕСТВО (САМОЕ ВАЖНОЕ)
            
            // Сначала считаем по массиву (1 главный + доп)
            $pc = 1 + count($cleanPassengers);

            // НО! Если фронтенд прислал явную цифру — верим ей. 
            // Это обходит проблему с кэшем JS, когда поля не очищаются.
            if ($request->has('passengers_count')) {
                $explicitCount = (int)$request->input('passengers_count');
                if ($explicitCount > 0 && $explicitCount <= 10) {
                    $pc = $explicitCount;
                }
            }
            
            // Финальная страховка
            $pc = max(1, min(10, $pc));

            // 4. Записываем в сессию
            $_SESSION['order']['passengers'] = $pc;

            // Корректируем массив доп. пассажиров в сессии под новое число
            if (isset($_SESSION['order']['passengers_extra']) && is_array($_SESSION['order']['passengers_extra'])) {
                // Оставляем (Всего - 1) пассажиров
                $keepExtra = max(0, $pc - 1);
                $_SESSION['order']['passengers_extra'] = array_slice($_SESSION['order']['passengers_extra'], 0, $keepExtra);
            }

            if ($this->user && $this->user->id && $request->input('save_data')) {
                $this->clientRepository->updateClientData($this->user->id, $_SESSION['passenger_data']);
            }

            session_write_close();

            return response()->json(['data' => 'ok']);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ВСПОМОГАТЕЛЬНЫЕ МЕТОДЫ (ОСТАВЛЯЕМ КАК БЫЛИ В ВАШЕМ ФАЙЛЕ)
    protected function getTicketInfo($tourId, $fromStop, $toStop): array
    {
        $lang = $this->router->lang ?? 'ru';
        $prefix = DB_PREFIX;
        $sql = "SELECT from_stop.departure_time AS departure_time, from_stop.arrival_time AS departure_arrival_time, from_city.title_{$lang} AS departure_city, from_city.title_{$lang} AS departure_station, to_stop.arrival_time AS arrival_time, to_stop.departure_time AS arrival_departure_time, to_city.title_{$lang} AS arrival_city, to_city.title_{$lang} AS arrival_station, bus.title_{$lang} AS bus, bus.id AS bus_id, prices.price AS price FROM `{$prefix}_tours_stops` AS from_stop JOIN `{$prefix}_cities` AS from_city ON from_stop.stop_id = from_city.id JOIN `{$prefix}_tours` AS tours ON from_stop.tour_id = tours.id JOIN `{$prefix}_tours_stops` AS to_stop ON from_stop.tour_id = to_stop.tour_id JOIN `{$prefix}_cities` AS to_city ON to_stop.stop_id = to_city.id JOIN `{$prefix}_buses` AS bus ON tours.bus = bus.id JOIN `{$prefix}_tours_stops_prices` AS prices ON prices.tour_id = from_stop.tour_id AND prices.from_stop = from_stop.stop_id AND prices.to_stop = to_stop.stop_id WHERE from_stop.tour_id = ? AND from_stop.stop_id = ? AND to_stop.stop_id = ?";
        if ($this->db) { $result = $this->db->getOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]); } else { $result = DB::selectOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]); $result = $result ? (array) $result : null; }
        if ($result) {
            if ($this->db) {
                $fromCityInfo = $this->db->getOne("SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?", [(int)$fromStop]);
                $toCityInfo = $this->db->getOne("SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?", [(int)$toStop]);
            } else {
                $fromCityInfo = DB::selectOne("SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?", [(int)$fromStop]);
                $toCityInfo = DB::selectOne("SELECT station, section_id FROM `{$prefix}_cities` WHERE id = ?", [(int)$toStop]);
                $fromCityInfo = $fromCityInfo ? (array) $fromCityInfo : null; $toCityInfo = $toCityInfo ? (array) $toCityInfo : null;
            }
            if ($fromCityInfo && $fromCityInfo['station'] == 1 && $fromCityInfo['section_id'] > 0) {
                if ($this->db) { $parentCity = $this->db->getOne("SELECT title_{$lang} FROM `{$prefix}_cities` WHERE id = ? AND station = 0", [(int)$fromCityInfo['section_id']]); } else { $parentCity = DB::selectOne("SELECT title_{$lang} as title FROM `{$prefix}_cities` WHERE id = ? AND station = 0", [(int)$fromCityInfo['section_id']]); $parentCity = $parentCity ? (array) $parentCity : null; }
                if ($parentCity) { $result['departure_city'] = $parentCity['title'] ?? $parentCity["title_{$lang}"] ?? $result['departure_city']; }
            }
            if ($toCityInfo && $toCityInfo['station'] == 1 && $toCityInfo['section_id'] > 0) {
                if ($this->db) { $parentCity = $this->db->getOne("SELECT title_{$lang} FROM `{$prefix}_cities` WHERE id = ? AND station = 0", [(int)$toCityInfo['section_id']]); } else { $parentCity = DB::selectOne("SELECT title_{$lang} as title FROM `{$prefix}_cities` WHERE id = ? AND station = 0", [(int)$toCityInfo['section_id']]); $parentCity = $parentCity ? (array) $parentCity : null; }
                if ($parentCity) { $result['arrival_city'] = $parentCity['title'] ?? $parentCity["title_{$lang}"] ?? $result['arrival_city']; }
            }
        }
        return $result ?? [];
    }

    protected function getFirstPhoneData($clientInfo, $phoneCodes): array
    {
        $firstPhoneExample = ''; $firstPhoneMask = '';
        if (!empty($clientInfo['phone_code'])) { $phoneData = $this->phoneCodeRepository->getPhoneCodeById($clientInfo['phone_code']); if ($phoneData) { $firstPhoneExample = $phoneData['phone_example']; $firstPhoneMask = $phoneData['phone_mask']; } }
        if (empty($firstPhoneExample) && !empty($phoneCodes)) { $firstCode = reset($phoneCodes); $firstPhoneExample = $firstCode->phone_example ?? ''; $firstPhoneMask = $firstCode->phone_mask ?? ''; }
        return ['example' => $firstPhoneExample, 'mask' => $firstPhoneMask];
    }

    protected function formatDateForDisplay($date, $lang = 'ru'): string
    {
        $prefix = DB_PREFIX; $timestamp = strtotime($date); $day = date('d', $timestamp); $month = (int)date('m', $timestamp); $year = date('Y', $timestamp);
        if ($this->db) { $monthData = $this->db->getOne("SELECT title_{$lang} FROM `{$prefix}_months` WHERE id = ?", [$month]); $monthName = $monthData["title_{$lang}"] ?? ''; } else { $monthData = DB::selectOne("SELECT title_{$lang} as title FROM `{$prefix}_months` WHERE id = ?", [$month]); $monthName = $monthData ? $monthData->title : ''; }
        return "{$day} {$monthName} {$year}";
    }
}
