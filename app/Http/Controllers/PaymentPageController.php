<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\BusRepository;
use App\Repository\Order\OrderRepository;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Client;
use App\Service\LiqPayService;
use App\Service\TicketService;
use App\Services\Payments\MonobankAcquiringService;
use App\Services\Payments\PaymentFinalizer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

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

        if (!isset($_SESSION['order']['tour_id'])) {
            return redirect()->route('main');
        }

        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");

        $lang = $this->router->lang ?? 'ru';

        $this->syncPassengersFromPassengerData();

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

        $correlationId = (string)($request->input('correlation_id') ?: $request->header('X-Correlation-Id') ?: Str::uuid());
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

            $this->syncPassengersFromPassengerData();

            $paymethod = $request->input('paymethod');

            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)$order['tour_id'],
                (int)$order['from'],
                (int)$order['to']
            );

            $passengerData = $_SESSION['passenger_data'] ?? [];

            $orderResult = $this->createOrder($order, $ticketInfo, $passengerData, $paymethod);

            if (!$orderResult || !isset($orderResult['id'])) {
                return response()->json(['data' => 'error'], 500);
            }

            $orderId = (int) $orderResult['id'];
            $orderUniqId = (string) ($orderResult['uniqid'] ?? '');

            $_SESSION['last_order_id'] = $orderId;
            $_SESSION['last_order_uniqid'] = $orderUniqId;
            $_SESSION['last_payment_method'] = $paymethod;
            if ($paymethod === 'cash') {
                $this->dispatchCashTicketsOnce($orderId, $correlationId);
            }

            $response = response()->json([
                'data' => 'ok',
                'order_id' => $orderId,
                'uniqid' => $orderUniqId,
                'payment_method' => $paymethod,
            ]);
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

    protected function orderEvents(Request $request, string $requestCorrelationId): JsonResponse
    {
        $orderId = $request->input('order_id');
        $uniqid = $request->input('uniqid');
        $poll = (int) $request->input('poll', 0);

        $checkRemote = (int) $request->input('check_remote', 0) === 1;
        if ($poll >= 6) {
            $checkRemote = true;
        }

        $debugEnabled = $this->isDebugRequest($request);

        if (!$orderId && !$uniqid) {
            Log::warning('[order_events] missing order_id/uniqid', [
                'correlation_id' => $requestCorrelationId,
                'payload' => $request->all(),
            ]);

            $response = response()->json([
                'state' => 0,
                'error' => 'missing_order_id/uniqid',
            ], 400);

            if ($debugEnabled) {
                $payload = $response->getData(true);
                $payload['_debug'] = [
                    'order_id' => $orderId,
                    'uniqid_request' => $uniqid,
                    'poll' => $poll,
                ];
                $response = response()->json($payload, $response->getStatusCode());
            }

            return $response;
        }

        Log::info('[order_events] incoming', [
            'correlation_id' => $requestCorrelationId,
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
                'correlation_id' => $requestCorrelationId,
                'order_id' => $orderId,
                'uniqid' => $uniqid,
            ]);

            $response = response()->json([
                'status' => 'error',
                'message' => 'order_not_found',
            ], 404);

            if ($debugEnabled) {
                $payload = $response->getData(true);
                $payload['_debug'] = [
                    'order_id' => $orderId,
                    'uniqid_request' => $uniqid,
                    'poll' => $poll,
                    'check_remote' => $checkRemote,
                ];
                $response = response()->json($payload, $response->getStatusCode());
            }

            return $response;
        }

        $legacyOrderId = (string) ($order->uniqid ?: ($order->uniqId ?? null) ?: ('ORDER_' . $order->id));

        $requestPaymentProvider = (string) ($request->input('payment_provider') ?: $request->query('payment_provider') ?: '');
        $paymentProvider = strtolower((string) ($order->payment_provider ?? $requestPaymentProvider));
        $requestPaymentMethod = (string) ($request->input('payment_method') ?: $request->query('payment_method') ?: '');
        $paymentMethod = strtolower((string) ($order->payment_method ?? ($order->paymethod ?? $requestPaymentMethod)));

        $invoiceId = $order->mono_invoice_id ?? null;
        $paymentCorrelationId = $requestCorrelationId;
        if ($paymentCorrelationId === '') {
            $paymentCorrelationId = PaymentFinalizer::buildCorrelationId((int) $order->id, $legacyOrderId, $invoiceId);
        }

        $invoiceLinkedFromPayment = false;

        // Если у заказа нет mono_invoice_id, пробуем подтянуть из таблицы payments
        if ($paymentProvider === 'monobank' && !$invoiceId) {
            $payment = Payment::where('order_id', $legacyOrderId)->first();

            if ($payment && $payment->payment_id) {
                $invoiceId = (string) $payment->payment_id;
                $invoiceLinkedFromPayment = true;

                Log::info('[order_events] linked invoice from payment', [
                    'correlation_id' => $paymentCorrelationId,
                    'order_id' => $order->id,
                    'uniqid' => $legacyOrderId,
                    'invoice_id' => $invoiceId,
                ]);

                if (array_key_exists('mono_invoice_id', $order->getAttributes())) {
                    $order->mono_invoice_id = $invoiceId;
                    try {
                        $order->save();
                    } catch (\Throwable $e) {
                        Log::warning('[order_events] failed to persist mono_invoice_id', [
                            'correlation_id' => $paymentCorrelationId,
                            'order_id' => $order->id,
                            'uniqid' => $legacyOrderId,
                            'invoice_id' => $invoiceId,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($requestCorrelationId === '') {
                    $paymentCorrelationId = PaymentFinalizer::buildCorrelationId((int) $order->id, $legacyOrderId, $invoiceId);
                }
            }
        }

        $bonusDebug = null;
        if ($debugEnabled && $order->client_id) {
            $client = Client::find((int) $order->client_id);
            if ($client) {
                $bonusDebug = [
                    'client_id' => (int) $client->id,
                    'bonus_balance_cents' => (int) $client->bonus_balance_cents,
                ];
            }
        }

        $debugInfo = [
            'request_correlation_id' => $requestCorrelationId,
            'payment_correlation_id' => $paymentCorrelationId,
            'order_id' => $order->id,
            'uniqid_request' => $uniqid,
            'uniqid_db' => $legacyOrderId,
            'invoiceId' => $invoiceId,
            'poll' => $poll,
            'check_remote' => $checkRemote,
            'payment_provider' => $paymentProvider,
            'invoice_linked_from_payment' => $invoiceLinkedFromPayment,
            'payment_status_db' => (int) ($order->payment_status ?? 0),
            'mono_status_db' => (string) ($order->mono_status ?? ''),
            'payment_method' => $paymentMethod,
            'order_bonus' => [
                'bonus_redeemed_cents' => (int) ($order->bonus_redeemed_cents ?? 0),
                'bonus_cashback_cents' => (int) ($order->bonus_cashback_cents ?? 0),
                'bonus_use_requested' => (int) ($order->bonus_use_requested ?? 0),
            ],
            'client_bonus' => $bonusDebug,
        ];

        // Если уже оплачено — просто ok (не шлем тикеты повторно, чтобы не задублировать)
        if ((int) $order->payment_status === 2) {
            Log::info('[order_events] already paid in DB', [
                'correlation_id' => $paymentCorrelationId,
                'order_id' => $order->id,
                'uniqid' => $legacyOrderId,
                'payment_status' => (int) $order->payment_status,
                'mono_status' => (string) ($order->mono_status ?? ''),
            ]);

            $paymentPayloadForTicket = [
                'status' => 'success',
                'payment_provider' => $paymentProvider,
                'order_id' => $legacyOrderId,
                'invoiceId' => (string) ($invoiceId ?? ''),
                'paid_at' => $order->paid_at ?? null,
                'payment_hint' => 'already_paid_poll',
            ];

            $this->dispatchTicketsOnce($order, $legacyOrderId, $paymentPayloadForTicket, $paymentCorrelationId);

            $response = response()->json([
                'status' => 'ok',
                'payment_status' => 2,
                'finalized' => true,
            ]);

            if ($debugEnabled) {
                $payload = $response->getData(true);
                $payload['_debug'] = $debugInfo;
                $response = response()->json($payload, $response->getStatusCode());
            }

            return $response;
        }

         if ($paymentMethod === 'cash') {
            Log::info('[order_events] cash booking', [
                'correlation_id' => $paymentCorrelationId,
                'order_id' => $order->id,
                'uniqid' => $legacyOrderId,
            ]);

            $this->dispatchCashTicketsOnce((int) $order->id, $paymentCorrelationId);

            $response = response()->json([
                'status' => 'ok',
                'payment_status' => (int) ($order->payment_status ?? 1),
                'payment_method' => 'cash',
                'finalized' => true,
            ]);

            if ($debugEnabled) {
                $payload = $response->getData(true);
                $payload['_debug'] = $debugInfo;
                $response = response()->json($payload, $response->getStatusCode());
            }

            return $response;
        }


        $alreadyFinalized = ((int) $order->payment_status === 2)
            || ((string) ($order->mono_status ?? '') === 'success');

        $remoteCheckAllowed = false;
        $remoteStatus = null;
        $finalizeResult = null;

        if ($checkRemote && $paymentProvider === 'monobank' && $invoiceId && !$alreadyFinalized) {
            $throttleSeconds = (int) config('services.monobank.status_poll_seconds', 25);
            $cacheKey = 'mono:status_check:' . $invoiceId;

            $remoteCheckAllowed = Cache::add($cacheKey, 1, $throttleSeconds);

            if ($remoteCheckAllowed) {
                /** @var MonobankAcquiringService $monoService */
                $monoService = app(MonobankAcquiringService::class);
                $remoteStatus = $monoService->getInvoiceStatus((string) $invoiceId);

                Log::info('[order_events] monobank remote status', [
                    'correlation_id' => $paymentCorrelationId,
                    'invoice_id' => $invoiceId,
                    'remote_status' => $remoteStatus,
                ]);

                if (is_array($remoteStatus) && ($remoteStatus['status'] ?? null) === 'success') {
                    /** @var PaymentFinalizer $finalizer */
                    $finalizer = app(PaymentFinalizer::class);

                    $finalizeResult = $finalizer->finalizeMonobankPaidIfNeeded($order, $remoteStatus, 'polling');
                    $order->refresh();

                    // ✅ КЛЮЧЕВАЯ ЧАСТЬ: после paid запускаем генерацию билета + email (как в LiqPay flow)
                    if ((int) $order->payment_status === 2) {
                        $paymentPayloadForTicket = [
                            'status' => 'success',
                            'payment_provider' => 'monobank',
                            'order_id' => $legacyOrderId,
                            'invoiceId' => (string) ($remoteStatus['invoiceId'] ?? $invoiceId),
                            'payMethod' => $remoteStatus['payMethod'] ?? null,
                            'amount' => $remoteStatus['amount'] ?? null,
                            'ccy' => $remoteStatus['ccy'] ?? null,
                            'finalAmount' => $remoteStatus['finalAmount'] ?? null,
                            'paid_at' => $order->paid_at ?? null,
                            'remote_status' => $remoteStatus,
                        ];

                        $this->dispatchTicketsOnce($order, $legacyOrderId, $paymentPayloadForTicket, $paymentCorrelationId);
                    }

                    Log::info('[order_events] paid after remote check', [
                        'correlation_id' => $paymentCorrelationId,
                        'order_id' => $order->id,
                        'uniqid' => $legacyOrderId,
                        'invoice_id' => $invoiceId,
                        'finalize_result' => $finalizeResult,
                    ]);
                }
            } else {
                Log::info('[order_events] monobank remote check throttled', [
                    'correlation_id' => $paymentCorrelationId,
                    'invoice_id' => $invoiceId,
                    'throttle_seconds' => $throttleSeconds,
                ]);
            }
        }

        if ((int) $order->payment_status === 2) {
            $response = response()->json([
                'status' => 'ok',
                'payment_status' => 2,
                'payment_method' => $paymentMethod,
                'finalized' => true,
            ]);

            if ($debugEnabled) {
                $payload = $response->getData(true);
                $payload['_debug'] = array_merge($debugInfo, [
                    'remote_check_allowed' => $remoteCheckAllowed,
                    'remote_status' => $remoteStatus,
                    'finalize_result' => $finalizeResult,
                    'errors' => [
                        'remote' => is_array($remoteStatus) ? ($remoteStatus['_error'] ?? null) : null,
                        'finalize' => is_array($finalizeResult) ? ($finalizeResult['error'] ?? null) : null,
                    ],
                ]);
                $response = response()->json($payload, $response->getStatusCode());
            }

            return $response;
        }

        Log::info('[order_events] pending', [
            'correlation_id' => $paymentCorrelationId,
            'order_id' => $order->id,
            'uniqid' => $legacyOrderId,
            'payment_status' => (int) ($order->payment_status ?? 0),
        ]);

        $response = response()->json([
            'status' => 'pending',
            'payment_status' => (int) ($order->payment_status ?? 0),
            'payment_method' => $paymentMethod,
        ]);

        if ($debugEnabled) {
            $payload = $response->getData(true);
            $payload['_debug'] = array_merge($debugInfo, [
                'remote_check_allowed' => $remoteCheckAllowed,
                'remote_status' => $remoteStatus,
                'finalize_result' => $finalizeResult,
                'errors' => [
                    'remote' => is_array($remoteStatus) ? ($remoteStatus['_error'] ?? null) : null,
                    'finalize' => is_array($finalizeResult) ? ($finalizeResult['error'] ?? null) : null,
                ],
            ]);
            $response = response()->json($payload, $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Отправляем билеты для оплаты наличными один раз, чтобы не слать дубликаты.
     */
    /**
     * Отправляем билеты для оплаты наличными один раз, чтобы не слать дубликаты.
     */
    private function dispatchCashTicketsOnce(int $orderId, string $correlationId): void
    {
        $lockKey = 'tickets:sent:' . $orderId;
        $ttlSeconds = 86400;

        if (!Cache::add($lockKey, 1, $ttlSeconds)) {
            Log::info('[tickets] cash dispatch skipped (lock exists)', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
            ]);
            return;
        }

        try {
            /** @var TicketService $ticketService */
            $ticketService = app(TicketService::class);

            Log::info('[tickets] cash dispatch start', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
            ]);

            $ticketService->sendCashOrderTickets($orderId, [
                'payment_method' => 'cash',
                'payment_provider' => 'cash',
                'order_id' => $orderId,
                'payment_hint' => 'cash_booking',
            ], $correlationId);

            Log::info('[tickets] cash dispatch done', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
            ]);
        } catch (Throwable $e) {
            Cache::forget($lockKey);

            Log::error('[tickets] cash dispatch failed', [
                'correlation_id' => $correlationId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
    /**
     * Запускаем генерацию PDF + email ОДИН РАЗ на оплаченный заказ.
     * Нужен lock, потому что order_events дергается polling-ом много раз.
     */
    private function dispatchTicketsOnce(Order $order, string $legacyOrderId, array $paymentPayload, string $paymentCorrelationId): void
    {
        // lock на сутки, чтобы не дублировать письма на повторных поллах/рефрешах
        $lockKey = 'tickets:sent:' . (int) $order->id;
        $ttlSeconds = 86400;

        if (!Cache::add($lockKey, 1, $ttlSeconds)) {
            Log::info('[tickets] dispatch skipped (lock exists)', [
                'correlation_id' => $paymentCorrelationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);
            return;
        }

        try {
            /** @var TicketService $ticketService */
            $ticketService = app(TicketService::class);

            Log::info('[tickets] dispatch start', [
                'correlation_id' => $paymentCorrelationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);

            // Это твой существующий LiqPay-флоу: генерация PDF + отправка email
            $ticketService->processSuccessfulPayment($legacyOrderId, $paymentPayload, $paymentCorrelationId);

            Log::info('[tickets] dispatch done', [
                'correlation_id' => $paymentCorrelationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
            ]);
        } catch (Throwable $e) {
            // если упало — снимаем lock, чтобы можно было повторить при следующем poll/refresh
            Cache::forget($lockKey);

            Log::error('[tickets] dispatch failed', [
                'correlation_id' => $paymentCorrelationId,
                'order_id' => (int) $order->id,
                'legacy_order_id' => $legacyOrderId,
                'error' => $e->getMessage(),
            ]);
        }
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

    /**
     * Создание платежа через LiqPay (legacy)
     */
    public function createLegacyPayment(Request $request): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json(['success' => false, 'error' => 'no_order_in_session'], 400);
            }

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
    protected function createOrder($order, $ticketInfo, $passengerData, $paymethod): ?array
    {
        try {
            $prefix = DB_PREFIX;

            $passengers = (int)($order['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $price = (float)($ticketInfo['price'] ?? 0);
            $total = (int)round($passengers * $price);
            $uniqid = uniqid('order_', true);

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
                'uniqid' => $uniqid,
                'created_at' => date('Y-m-d H:i:s')
            ];

            if ($this->db) {
                $insertId = $this->db->insert("{$prefix}_orders", $orderData);
                return $insertId ? ['id' => (int) $insertId, 'uniqid' => $uniqid] : null;
            }

            $insertId = DB::table("{$prefix}_orders")->insertGetId($orderData);
            return $insertId ? ['id' => (int) $insertId, 'uniqid' => $uniqid] : null;

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
     */
    protected function syncPassengersFromPassengerData(): void
    {
        if (!isset($_SESSION['order'])) {
            return;
        }

        $extra = 0;

        if (isset($_SESSION['passenger_data']['passengers']) && is_array($_SESSION['passenger_data']['passengers'])) {
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

        if ($computed !== $current) {
            $_SESSION['order']['passengers'] = $computed;
        }
    }
}
