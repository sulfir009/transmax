<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\BusRepository;
use App\Repository\Order\OrderRepository;
use App\Models\Order;
use App\Service\LiqPayService;
use App\Service\TicketService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentPageController extends Controller
{
    protected $router;
    protected $db;
    protected $user;
    protected $busRepository;
    protected $orderRepository;
    protected $liqpayService;

    public function __construct(
        BusRepository $busRepository = null,
        OrderRepository $orderRepository = null,
        LiqPayService $liqpayService = null
    ) {
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'mt');
        }

        global $Router, $Db, $User;
        $this->router = $Router;
        $this->db = $Db;
        $this->user = $User;

        $this->busRepository = $busRepository ?: new BusRepository();
        $this->orderRepository = $orderRepository ?: new OrderRepository();
        $this->liqpayService = $liqpayService ?: new LiqPayService();
    }

    /**
     * Отображение страницы оплаты
     */
    public function index(Request $request)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // ✅ НИКАКИХ "тестовых данных" на проде.
        // Если заказа нет — возвращаем на главный шаг.
        if (!isset($_SESSION['order']['tour_id'])) {
            return redirect()->route('main');
        }

        // Заголовки против кеша
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");

        $lang = $this->router->lang ?? 'ru';

        // ✅ СИНХРОНИЗАЦИЯ passengers на входе в оплату
        // Если на шаге 2 ты удалил пассажира, но order.passengers "залип" — мы выравниваем.
        $this->syncPassengersFromPassengerData();

        // Инфа о билете — всегда из БД по данным из сессии (не из клиента)
        $ticketInfo = $this->getTicketInfo(
            (int)($_SESSION['order']['tour_id'] ?? 0),
            (int)($_SESSION['order']['from'] ?? 0),
            (int)($_SESSION['order']['to'] ?? 0)
        );

        $monthData = $this->getMonthName($_SESSION['order']['date'] ?? date('Y-m-d'), $lang);

        $paymentDateTime = $this->formatPaymentDateTime(
            $_SESSION['order']['date'] ?? date('Y-m-d'),
            $ticketInfo['departure_time'] ?? '',
            $monthData
        );

        $passengers = (int)($_SESSION['order']['passengers'] ?? 1);
        $passengers = max(1, min(10, $passengers));

        $pricePer = (float)($ticketInfo['price'] ?? 0);
        $totalPrice = (int)round($passengers * $pricePer);

        $busOptions = $this->busRepository->getBusOptions($ticketInfo['bus_id'] ?? null);

        if (!empty($busOptions) && is_object($busOptions[0] ?? null)) {
            $busOptions = array_map(function ($item) {
                return (array)$item;
            }, $busOptions);
        }

        $viewData = [
            'ticketInfo' => $ticketInfo,
            'monthData' => $monthData,
            'paymentDateTime' => $paymentDateTime,
            'totalPrice' => $totalPrice,
            'busOptions' => $busOptions,
            'passengers' => $passengers,
            'order' => $_SESSION['order'],
            'tourDate' => $_SESSION['order']['date'] ?? date('Y-m-d'),
            'Router' => $this->router,
            'lang' => $lang,
            'dictionary' => $GLOBALS['dictionary'] ?? []
        ];

        return view('payment.index', $viewData);
    }

    /**
     * AJAX обработчики
     */
    public function ajax(Request $request, string $lang = 'ru'): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $correlationId = (string) Str::uuid();
        $requestType = $request->input('request');

        switch ($requestType) {
            case 'order_route':
                return $this->withCorrelationId($this->orderRoute($request, $correlationId), $correlationId);

            case 'delete_order_tour_id':
                return $this->withCorrelationId($this->deleteOrderTourId(), $correlationId);

            case 'order_mail':
                return $this->withCorrelationId($this->sendOrderEmail($request), $correlationId);

            case 'order_events':
                return $this->withCorrelationId($this->orderEvents($request, $correlationId), $correlationId);

            case 'order_events':
                return $this->orderEvents($request);

            default:
                return $this->withCorrelationId(response()->json(['error' => 'Unknown request type'], 400), $correlationId);
        }
    }

    /**
     * Создание заказа
     */
    protected function orderRoute(Request $request, string $correlationId): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json(['data' => 'error', 'error' => 'no_order_in_session'], 400);
            }

            // ✅ На всякий случай синхронизируем passengers ещё раз
            $this->syncPassengersFromPassengerData();

            $paymethod = $request->input('paymethod');

            // ✅ ticketInfo / order — НЕ берём из клиента, берём из сессии и БД
            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)$order['tour_id'],
                (int)$order['from'],
                (int)$order['to']
            );

            $passengerData = $_SESSION['passenger_data'] ?? [];

            $orderId = $this->createOrder($order, $ticketInfo, $passengerData, $paymethod);

            if (!$orderId) {
                return response()->json(['data' => 'error'], 500);
            }

            $_SESSION['last_order_id'] = $orderId;

            $response = response()->json(['data' => 'ok']);
            if ($this->isDebugRequest($request)) {
                $response = $this->withDebugMeta($response, [
                    'handled_by' => 'PaymentPageController@ajax',
                    'route' => '/ajax/payment/{lang}',
                    'correlation_id' => $correlationId,
                    'request' => 'order_route',
                ]);
            }
            return $response;

        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json(['data' => 'error'], 500);
        }
    }

    protected function orderEvents(Request $request, string $correlationId): JsonResponse
    {
        $orderId = $request->input('order_id');
        $uniqid = $request->input('uniqid');

        Log::info('[order_events] incoming', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'uniqid' => $uniqid,
            'payload' => $request->all(),
        ]);

        $order = null;
        if ($orderId) {
            $order = Order::find($orderId);
        }

        if (!$order && $uniqid) {
            $order = Order::where('uniqId', (string) $uniqid)
                ->orWhere('uniqid', (string) $uniqid)
                ->first();
        }

        if (!$order) {
            Log::warning('[order_events] order not found', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'uniqid' => $uniqid,
            ]);

            $response = response()->json([
                'status' => 'error',
                'message' => 'order_not_found',
            ], 404);
            if ($this->isDebugRequest($request)) {
                $response = $this->withDebugMeta($response, [
                    'handled_by' => 'PaymentPageController@ajax',
                    'route' => '/ajax/payment/{lang}',
                    'correlation_id' => $correlationId,
                    'request' => 'order_events',
                ]);
            }
            return $response;
        }

        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));

        if ((int) $order->payment_status === 2) {
            $alreadyFinalized = $this->isOrderFinalized($order->getTable(), $order->id);

            if (!$alreadyFinalized) {
                try {
                    /** @var TicketService $ticketService */
                    $ticketService = app(TicketService::class);
                    Cache::put($this->finalizeAttemptCacheKey($order->id), [
                        'started_at' => now()->toIso8601String(),
                        'source' => 'order_events',
                        'correlation_id' => $correlationId,
                    ], now()->addDay());
                    $ticketService->processSuccessfulPayment($legacyOrderId, [
                        'source' => 'order_events',
                        'order_id' => $orderId,
                        'uniqid' => $uniqid,
                    ]);
                } catch (\Throwable $e) {
                    Cache::put($this->finalizeErrorCacheKey($order->id), [
                        'error' => $e->getMessage(),
                        'failed_at' => now()->toIso8601String(),
                        'source' => 'order_events',
                        'correlation_id' => $correlationId,
                    ], now()->addDay());
                    Log::error('[order_events] finalization failed', [
                        'correlation_id' => $correlationId,
                        'order_id' => $orderId,
                        'uniqid' => $uniqid,
                        'error' => $e->getMessage(),
                    ]);

                    $response = response()->json([
                        'status' => 'error',
                        'message' => 'finalization_failed',
                    ], 500);
                    if ($this->isDebugRequest($request)) {
                        $response = $this->withDebugMeta($response, [
                            'handled_by' => 'PaymentPageController@ajax',
                            'route' => '/ajax/payment/{lang}',
                            'correlation_id' => $correlationId,
                            'request' => 'order_events',
                            'notes' => ['finalization_failed'],
                        ]);
                    }
                    return $response;
                }
            }

            Log::info('[order_events] processed', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'uniqid' => $uniqid,
                'finalized' => !$alreadyFinalized,
            ]);

            $response = response()->json([
                'status' => 'ok',
                'payment_status' => 2,
                'finalized' => !$alreadyFinalized,
            ]);
            if ($this->isDebugRequest($request)) {
                $response = $this->withDebugMeta($response, [
                    'handled_by' => 'PaymentPageController@ajax',
                    'route' => '/ajax/payment/{lang}',
                    'correlation_id' => $correlationId,
                    'request' => 'order_events',
                ]);
            }
            return $response;
        }

        Log::info('[order_events] pending', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'uniqid' => $uniqid,
            'payment_status' => $order->payment_status,
        ]);

        $response = response()->json([
            'status' => 'pending',
            'payment_status' => (int) $order->payment_status,
        ]);
        if ($this->isDebugRequest($request)) {
            $response = $this->withDebugMeta($response, [
                'handled_by' => 'PaymentPageController@ajax',
                'route' => '/ajax/payment/{lang}',
                'correlation_id' => $correlationId,
                'request' => 'order_events',
            ]);
        }
        return $response;
    }

    private function isOrderFinalized(string $table, int $orderId): bool
    {
        $orderRow = DB::table($table)->where('id', $orderId)->first();
        if (!$orderRow) {
            return false;
        }

        $onlineIndicators = [
            'payment_type',
            'pay_type',
            'payment_method',
            'pay_method',
            'payment_provider',
            'provider',
            'payment_form',
            'type_pay',
            'payment_way',
            'payment_kind',
        ];

        foreach ($onlineIndicators as $column) {
            if (!Schema::hasColumn($table, $column)) {
                continue;
            }

            $value = $orderRow->{$column} ?? null;
            if (is_numeric($value) && (int) $value === 2) {
                return true;
            }

            if (is_string($value) && in_array(strtolower($value), ['online', 'monobank', 'liqpay'], true)) {
                return true;
            }
        }

        if (Schema::hasColumn($table, 'paid_online')) {
            return (int) ($orderRow->paid_online ?? 0) === 1;
        }

        return false;
    }

    private function isDebugRequest(Request $request): bool
    {
        $debugEnabled = (string) $request->query('debug') === '1';
        $token = (string) $request->header('X-Debug-Token');
        $expected = (string) env('PAYMENT_DEBUG_TOKEN');

        if (!$debugEnabled) {
            return false;
        }

        if (app()->environment('local')) {
            return true;
        }

        return $expected !== '' && hash_equals($expected, $token);
    }

    private function withDebugMeta(JsonResponse $response, array $meta): JsonResponse
    {
        $payload = $response->getData(true);
        $payload['debug_meta'] = $meta;
        return response()->json($payload, $response->getStatusCode());
    }

    private function withCorrelationId(JsonResponse $response, string $correlationId): JsonResponse
    {
        return $response->header('X-Correlation-Id', $correlationId);
    }

    private function finalizeErrorCacheKey(int $orderId): string
    {
        return 'payment_debug:last_finalize_error:' . $orderId;
    }

    private function finalizeAttemptCacheKey(int $orderId): string
    {
        return 'payment_debug:last_finalize_attempt:' . $orderId;
    }

    /**
     * Создание платежа через LiqPay (legacy)
     */
    public function createLegacyPayment(Request $request): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json(['success' => false, 'error' => 'no_order_in_session'], 400);
            }

            // ✅ Не доверяем total_price из клиента — считаем заново
            $this->syncPassengersFromPassengerData();

            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)$order['tour_id'],
                (int)$order['from'],
                (int)$order['to']
            );

            $passengers = (int)($order['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $amount = (float)($ticketInfo['price'] ?? 0) * $passengers;
            $amount = (int)round($amount);

            $liqpayOrderId = 'TICKET_' . time() . '_' . rand(1000, 9999);

            $description = sprintf(
                "Оплата билета: %s - %s, %s",
                $ticketInfo['departure_city'] ?? '',
                $ticketInfo['arrival_city'] ?? '',
                $order['date'] ?? ''
            );

            $paymentData = $this->liqpayService->createPaymentData([
                'order_id' => $liqpayOrderId,
                'amount' => $amount,
                'description' => $description,
                'product_description' => $description,
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => 'https://www.liqpay.ua/api/3/checkout',
                'data' => $paymentData['data'],
                'signature' => $paymentData['signature']
            ]);

        } catch (\Exception $e) {
            Log::error('Legacy payment creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удаление tour_id из сессии
     */
    protected function deleteOrderTourId(): JsonResponse
    {
        unset($_SESSION['order']['tour_id']);
        return response()->json(['data' => 'ok']);
    }

    /**
     * Отправка email с информацией о заказе
     */
    protected function sendOrderEmail(Request $request): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json('no_order', 400);
            }

            $this->syncPassengersFromPassengerData();

            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)$order['tour_id'],
                (int)$order['from'],
                (int)$order['to']
            );

            $passengerData = $_SESSION['passenger_data'] ?? [];
            if (empty($passengerData['email'])) {
                return response()->json('no_email', 400);
            }

            $passengers = (int)($order['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $emailData = [
                'ticketInfo' => $ticketInfo,
                'order' => $order,
                'passengerData' => $passengerData,
                'totalPrice' => (int)round($passengers * (float)($ticketInfo['price'] ?? 0)),
            ];

            Mail::send('emails.order_confirmation', $emailData, function ($message) use ($passengerData) {
                $message->to($passengerData['email'])
                    ->subject('Подтверждение заказа билета');
            });

            return response()->json('ok');

        } catch (\Exception $e) {
            Log::error('Email sending error: ' . $e->getMessage());
            return response()->json('error', 500);
        }
    }

    /**
     * Создание записи заказа в БД
     */
    protected function createOrder($order, $ticketInfo, $passengerData, $paymethod): ?int
    {
        try {
            $prefix = DB_PREFIX;

            $passengers = (int)($order['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $price = (float)($ticketInfo['price'] ?? 0);
            $total = (int)round($passengers * $price);

            $orderData = [
                'tour_id' => (int)($order['tour_id'] ?? 0),
                'from_stop' => (int)($order['from'] ?? 0),
                'to_stop' => (int)($order['to'] ?? 0),
                'tour_date' => $order['date'] ?? date('Y-m-d'),
                'passengers_count' => $passengers,
                'price' => $price,
                'total_price' => $total,
                'payment_method' => $paymethod,
                'status' => $paymethod === 'cash' ? 'pending' : 'waiting_payment',
                'client_name' => $passengerData['name'] ?? '',
                'client_surname' => $passengerData['family_name'] ?? '',
                'client_patronymic' => $passengerData['patronymic'] ?? '',
                'client_email' => $passengerData['email'] ?? '',
                'client_phone' => $passengerData['phone'] ?? '',
                'client_phone_code' => $passengerData['phone_code'] ?? '',
                'passengers_data' => json_encode($passengerData['passengers'] ?? []),
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($this->db) {
                return $this->db->insert("{$prefix}_orders", $orderData);
            }

            return DB::table("{$prefix}_orders")->insertGetId($orderData);

        } catch (\Exception $e) {
            Log::error('Order creation DB error: ' . $e->getMessage());
            return null;
        }
    }

    protected function getTicketInfo($tourId, $fromStop, $toStop): array
    {
        $lang = $this->router->lang ?? 'ru';
        $prefix = DB_PREFIX;

        $sql = "SELECT
            from_stop.departure_time AS departure_time,
            from_city.title_{$lang} AS departure_station,
            departure_city.title_{$lang} AS departure_city,
            to_stop.arrival_time AS arrival_time,
            to_city.title_{$lang} AS arrival_station,
            arrival_city.title_{$lang} AS arrival_city,
            bus.title_{$lang} AS bus,
            bus.id AS bus_id,
            prices.price AS price
        FROM `{$prefix}_tours_stops` AS from_stop
            JOIN `{$prefix}_cities` AS from_city ON from_stop.stop_id = from_city.id
            JOIN `{$prefix}_tours` AS tours ON from_stop.tour_id = tours.id
            JOIN `{$prefix}_cities` AS departure_city ON departure_city.id = tours.departure
            JOIN `{$prefix}_tours_stops` AS to_stop ON from_stop.tour_id = to_stop.tour_id
            JOIN `{$prefix}_cities` AS to_city ON to_stop.stop_id = to_city.id
            JOIN `{$prefix}_cities` AS arrival_city ON arrival_city.id = tours.arrival
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
            return $result ?? [];
        }

        $result = DB::selectOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]);
        return $result ? (array)$result : [];
    }

    protected function getMonthName($date, $lang = 'ru'): array
    {
        $prefix = DB_PREFIX;
        $month = (int)explode('-', $date)[1];

        if ($this->db) {
            return $this->db->getOne(
                "SELECT title_{$lang} AS title FROM `{$prefix}_months` WHERE id = ?",
                [$month]
            ) ?? [];
        }

        $result = DB::selectOne(
            "SELECT title_{$lang} as title FROM `{$prefix}_months` WHERE id = ?",
            [$month]
        );
        return $result ? (array)$result : [];
    }

    protected function formatPaymentDateTime($date, $departureTime, $monthData): string
    {
        $parts = explode('-', $date);
        $day = isset($parts[2]) ? (int)$parts[2] : (int)date('d');
        $monthName = $monthData['title'] ?? '';
        $time = $departureTime ? date('H:i', strtotime($departureTime)) : '00:00';

        return "{$day} {$monthName} {$time}";
    }

    public function thankYou(Request $request)
    {
        return view('payment.thank-you');
    }

    /**
     * Выравниваем order.passengers по passenger_data.passengers, если оно есть.
     * Это лечит "залипание" 2 после удаления пассажира на шаге 2.
     */
    protected function syncPassengersFromPassengerData(): void
    {
        if (!isset($_SESSION['order'])) {
            return;
        }

        $extra = 0;

        if (isset($_SESSION['passenger_data']['passengers']) && is_array($_SESSION['passenger_data']['passengers'])) {
            // считаем только реально заполненные строки, а не пустые болванки
            foreach ($_SESSION['passenger_data']['passengers'] as $row) {
                if (!is_array($row)) continue;

                $hasAny = false;
                foreach ($row as $v) {
                    if (is_array($v)) continue;
                    if (trim((string)$v) !== '') { $hasAny = true; break; }
                }
                if ($hasAny) $extra++;
            }
        }

        $computed = 1 + $extra;
        $computed = max(1, min(10, $computed));

        $current = (int)($_SESSION['order']['passengers'] ?? 1);
        $current = max(1, min(10, $current));

        // Если computed=1, а в order залипло 2 — выравниваем
        if ($computed !== $current) {
            $_SESSION['order']['passengers'] = $computed;
        }
    }
}
