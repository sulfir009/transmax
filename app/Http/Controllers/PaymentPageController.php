<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\BusRepository;
use App\Repository\Order\OrderRepository;
use App\Service\LiqPayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

        $this->busRepository   = $busRepository ?: new BusRepository();
        $this->orderRepository = $orderRepository ?: new OrderRepository();
        $this->liqpayService   = $liqpayService ?: new LiqPayService();
    }

    /* ============================================================
       TABLE HELPERS
       ============================================================ */

    protected function ordersTable(): string
    {
        return DB_PREFIX . '_orders';
    }

    protected function eventsTable(): string
    {
        return DB_PREFIX . '_order_events';
    }

    /**
     * Возвращает список колонок таблицы mt_orders (и кэширует).
     */
    protected function getOrdersColumns(): array
    {
        static $cols = null;
        if ($cols !== null) return $cols;

        $table = $this->ordersTable();

        if ($this->db) {
            $rows = $this->db->getAll("SHOW COLUMNS FROM `{$table}`");
            $cols = [];
            foreach (($rows ?? []) as $r) {
                $name = null;
                if (is_array($r)) $name = $r['Field'] ?? null;
                if (is_object($r)) $name = $r->Field ?? null;
                if ($name) $cols[] = $name;
            }
            return $cols;
        }

        $rows = DB::select("SHOW COLUMNS FROM `{$table}`");
        $cols = [];
        foreach ($rows as $r) {
            $cols[] = $r->Field;
        }
        return $cols;
    }

    /**
     * Оставляет только те поля, которые реально есть в таблице mt_orders.
     */
    protected function filterOrderDataByExistingColumns(array $data): array
    {
        $cols = $this->getOrdersColumns();
        if (!$cols) return [];

        $allowed = array_flip($cols);
        return array_intersect_key($data, $allowed);
    }

    /**
     * Универсальный update по id (через твой $this->db или через Laravel DB)
     */
    protected function updateOrderById(int $id, array $data): void
    {
        $table = $this->ordersTable();
        $data  = $this->filterOrderDataByExistingColumns($data);
        if (!$data) return;

        try {
            if ($this->db) {
                $this->db->where('id', $id);
                $this->db->update($table, $data);
                return;
            }

            DB::table($table)->where('id', $id)->update($data);
        } catch (\Throwable $e) {
            Log::error("updateOrderById failed id={$id}: " . $e->getMessage());
        }
    }

    /**
     * Безопасно получить заказ по id
     */
    protected function getOrderRowById(int $orderId): ?array
    {
        $table = $this->ordersTable();

        try {
            if ($this->db) {
                $row = $this->db->getOne("SELECT * FROM `{$table}` WHERE id = ? LIMIT 1", [$orderId]);
                return $row ? (is_array($row) ? $row : (array)$row) : null;
            }

            $obj = DB::table($table)->where('id', $orderId)->first();
            return $obj ? (array)$obj : null;
        } catch (\Throwable $e) {
            Log::error("getOrderRowById failed id={$orderId}: " . $e->getMessage());
            return null;
        }
    }

    /* ============================================================
       EVENTS LOGGING
       ============================================================ */

    protected function orderEvent(int $orderId, string $type, string $message, array $payload = []): void
    {
        try {
            $table = $this->eventsTable();

            $row = [
                'order_id'   => $orderId,
                'type'       => $type,
                'message'    => $message,
                'payload'    => $payload ? json_encode($payload, JSON_UNESCAPED_UNICODE) : null,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->db) {
                $this->db->insert($table, $row);
            } else {
                DB::table($table)->insert($row);
            }
        } catch (\Throwable $e) {
            // не валим процесс из-за логов
            Log::warning('orderEvent failed: ' . $e->getMessage());
        }
    }

    protected function getOrderEvents(int $orderId, int $afterId = 0, int $limit = 50): array
    {
        $table = $this->eventsTable();

        try {
            if ($this->db) {
                $limit = max(1, min(200, (int)$limit));
                $sql = "SELECT * FROM `{$table}` WHERE order_id = ? AND id > ? ORDER BY id ASC LIMIT {$limit}";
                $rows = $this->db->getAll($sql, [$orderId, $afterId]) ?: [];
                return array_map(function ($r) {
                    return is_array($r) ? $r : (array)$r;
                }, $rows);
            }

            $rows = DB::table($table)
                ->where('order_id', $orderId)
                ->where('id', '>', $afterId)
                ->orderBy('id', 'asc')
                ->limit(max(1, min(200, (int)$limit)))
                ->get();

            return $rows ? $rows->map(function ($r) { return (array)$r; })->all() : [];
        } catch (\Throwable $e) {
            Log::warning("getOrderEvents failed order_id={$orderId}: " . $e->getMessage());
            return [];
        }
    }

    /* ============================================================
       EMAIL
       ============================================================ */

    protected function sendTicketEmailByOrderId(int $orderId): void
    {
        $row = $this->getOrderRowById($orderId);

        if (!$row) {
            Log::warning("sendTicketEmailByOrderId: order not found id={$orderId}");
            $this->orderEvent($orderId, 'email_failed', 'Order not found', []);
            return;
        }

        $email = (string)($row['client_email'] ?? '');
        if ($email === '') {
            Log::warning("sendTicketEmailByOrderId: empty email order_id={$orderId}");
            $this->orderEvent($orderId, 'email_failed', 'Empty client_email', []);
            return;
        }

        $ticketInfo = $this->getTicketInfo(
            (int)($row['tour_id'] ?? 0),
            (int)($row['from_stop'] ?? 0),
            (int)($row['to_stop'] ?? 0)
        );

        $passengers = (int)($row['passengers_count'] ?? $row['passagers'] ?? 1);
        $passengers = max(1, min(10, $passengers));

        $passengerData = [
            'name'        => $row['client_name'] ?? '',
            'family_name' => $row['client_surname'] ?? '',
            'patronymic'  => $row['client_patronymic'] ?? '',
            'birth_date'  => $row['client_birth_date'] ?? '',
            'email'       => $email,
            'phone'       => $row['client_phone'] ?? '',
            'phone_code'  => $row['client_phone_code'] ?? '',
            'passengers'  => json_decode((string)($row['passengers_data'] ?? '[]'), true) ?: [],
        ];

        // total_price у тебя нет — считаем “на лету”
        $totalPrice = (int)round($passengers * (float)($ticketInfo['price'] ?? 0));

        $emailData = [
            'ticketInfo'    => $ticketInfo,
            'order'         => $row,
            'passengerData' => $passengerData,
            'totalPrice'    => $totalPrice,
        ];

        $this->orderEvent($orderId, 'email_send_try', 'Trying to send email', ['to' => $email]);

        try {
            Mail::send('emails.order_confirmation', $emailData, function ($message) use ($email) {
                $message->to($email)->subject('Подтверждение заказа билета');
            });

            $this->orderEvent($orderId, 'email_sent', 'Email sent', ['to' => $email]);
        } catch (\Throwable $e) {
            Log::error("Email send failed order_id={$orderId}: " . $e->getMessage());
            $this->orderEvent($orderId, 'email_failed', 'Email send failed', [
                'to' => $email,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ============================================================
       MONOBANK WEBHOOK
       ============================================================ */

    public function monobankWebhook(Request $request): Response
    {
        try {
            $rawBody = (string)$request->getContent();
            $xSign   = (string)$request->header('X-Sign');

            if ($rawBody === '' || $xSign === '') {
                Log::warning('Mono webhook: empty body or missing X-Sign');
                // orderId неизвестен — в mt_order_events не запишем
                return response('ok', 200);
            }

            $pubKeyB64 = $this->getMonobankPubKeyBase64();
            if (!$pubKeyB64) {
                Log::error('Mono webhook: cannot fetch pubkey');
                return response('ok', 200);
            }

            $pubPem = "-----BEGIN PUBLIC KEY-----\n"
                . chunk_split($pubKeyB64, 64, "\n")
                . "-----END PUBLIC KEY-----\n";

            $signature = base64_decode($xSign, true);
            if ($signature === false) {
                Log::warning('Mono webhook: X-Sign is not base64');
                return response('ok', 200);
            }

            $ok = openssl_verify($rawBody, $signature, $pubPem, OPENSSL_ALGO_SHA256);
            if ($ok !== 1) {
                Log::warning('Mono webhook: signature invalid');
                return response('ok', 200);
            }

            $payload = json_decode($rawBody, true);
            if (!is_array($payload)) {
                Log::warning('Mono webhook: invalid json');
                return response('ok', 200);
            }

            $invoiceId = (string)($payload['invoiceId'] ?? '');
            $status    = (string)($payload['status'] ?? '');
            $modified  = (int)($payload['modifiedDate'] ?? 0);

            if ($invoiceId === '') {
                Log::warning('Mono webhook: missing invoiceId');
                return response('ok', 200);
            }

            // Находим заказ по mono_invoice_id
            $table = $this->ordersTable();
            $orderRow = null;

            if ($this->db) {
                $tmp = $this->db->getOne("SELECT * FROM `{$table}` WHERE mono_invoice_id = ? LIMIT 1", [$invoiceId]);
                $orderRow = $tmp ? (is_array($tmp) ? $tmp : (array)$tmp) : null;
            } else {
                $obj = DB::table($table)->where('mono_invoice_id', $invoiceId)->first();
                $orderRow = $obj ? (array)$obj : null;
            }

            if (!$orderRow || empty($orderRow['id'])) {
                Log::warning('Mono webhook: order not found for invoiceId=' . $invoiceId);
                return response('ok', 200);
            }

            $orderId = (int)$orderRow['id'];

            $this->orderEvent($orderId, 'mono_webhook_received', 'Webhook received', [
                'invoiceId' => $invoiceId,
                'status' => $status,
                'modifiedDate' => $modified,
            ]);

            // если уже success — не трогаем
            if ((string)($orderRow['mono_status'] ?? '') === 'success') {
                $this->orderEvent($orderId, 'mono_webhook_ok', 'Already success ранее, пропускаем', [
                    'current_mono_status' => (string)($orderRow['mono_status'] ?? ''),
                ]);
                return response('ok', 200);
            }

            $update = [
                'mono_status' => $status,
            ];

            if ($status === 'success') {
                $update['payment_status'] = 5; // paid
                $update['paid_at'] = date('Y-m-d H:i:s');

                $this->updateOrderById($orderId, $update);

                $this->orderEvent($orderId, 'payment_success', 'Payment success. Order updated', $update);

                // письмо после оплаты
                $this->sendTicketEmailByOrderId($orderId);
            } else {
                // промежуточные/ошибочные статусы
                if ($status === 'processing') $update['payment_status'] = 4;
                if ($status === 'failure' || $status === 'expired') $update['payment_status'] = 9;

                $this->updateOrderById($orderId, $update);

                $this->orderEvent($orderId, 'mono_status_updated', 'Mono status updated (non-success)', $update);
            }

            return response('ok', 200);

        } catch (\Throwable $e) {
            Log::error('Mono webhook error: ' . $e->getMessage());
            return response('ok', 200);
        }
    }

    /**
     * Получение pubkey (base64) мерчанта.
     * Делаем максимально "живуче":
     *  - если ответ JSON: берем key/pubkey
     *  - если ответ просто строка base64: используем как есть
     */
    protected function getMonobankPubKeyBase64(): ?string
    {
        static $cached = null;
        if ($cached !== null) return $cached;

        $token = config('services.monobank.token') ?: env('MONOBANK_TOKEN');
        if (!$token) return null;

        $ch = curl_init('https://api.monobank.ua/api/merchant/pubkey');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['X-Token: ' . $token],
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $res  = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);

        if ($code !== 200 || !$res) {
            Log::error("getMonobankPubKeyBase64 failed http={$code} err={$err}");
            return null;
        }

        $res = trim((string)$res);
        if ($res === '') return null;

        // Попробуем JSON
        $j = json_decode($res, true);
        if (is_array($j)) {
            $key = $j['key'] ?? $j['pubkey'] ?? null;
            if (is_string($key) && trim($key) !== '') {
                $cached = trim($key);
                return $cached;
            }
        }

        // Иначе считаем, что это уже base64 строка
        $cached = $res;
        return $cached;
    }

    /* ============================================================
       PAGES
       ============================================================ */

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

        // Заголовки против кеша
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");

        $lang = $this->router->lang ?? 'ru';

        // синхронизация количества пассажиров
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
            'ticketInfo'      => $ticketInfo,
            'monthData'       => $monthData,
            'paymentDateTime' => $paymentDateTime,
            'totalPrice'      => $totalPrice,
            'busOptions'      => $busOptions,
            'passengers'      => $passengers,
            'order'           => $_SESSION['order'],
            'tourDate'        => $_SESSION['order']['date'] ?? date('Y-m-d'),
            'Router'          => $this->router,
            'lang'            => $lang,
            'dictionary'      => $GLOBALS['dictionary'] ?? []
        ];

        return view('payment.index', $viewData);
    }

    public function thankYou(Request $request)
    {
        return view('payment.thank-you');
    }

    /* ============================================================
       AJAX: order_events
       ============================================================ */

protected function ajaxOrderEvents(Request $request, string $lang = 'ru'): JsonResponse
{
    $orderId = (int)$request->input('order_id', 0);
    $afterId = (int)$request->input('after_id', 0);

    if ($orderId <= 0) {
        return response()->json([
            'lang'  => $lang,
            'data'  => 'err',
            'ok'    => false,
            'error' => 'order_id_required',
        ], 400);
    }

    $events = $this->getOrderEvents($orderId, $afterId, 100);

    // - data:"ok" (legacy)
    // - ok:true (новый стиль)
    // - events (реальные данные)
    return response()->json([
        'lang'         => $lang,
        'data'         => 'ok',
        'ok'           => true,
        'order_id'     => $orderId,
        'after_id'     => $afterId,
        'events'       => $events,
        'events_count' => count($events),

        '__marker'     => 'ajaxOrderEvents_v3_20260129',
    ]);
}



    /**
     * AJAX обработчики
     */
    public function ajax(Request $request, string $lang = 'ru'): JsonResponse
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

$requestType = $this->detectRequestType($request);



        switch ($requestType) {
            case 'order_route':
                return $this->orderRoute($request);

            case 'order_events':
                return $this->ajaxOrderEvents($request, $lang);

            case 'delete_order_tour_id':
                return $this->deleteOrderTourId();

            case 'order_mail':
                return $this->sendOrderEmail($request);

            default:
    Log::warning('AJAX unknown request type', [
        'requestType' => $requestType,
        'payload' => $request->all(),
    ]);

    return response()->json([
        'data' => 'err',
        'lang' => $lang,
        'error' => 'Unknown request type',
        'requestType' => $requestType,
    ], 400);

        }
    }
    
    protected function detectRequestType(Request $request): string
{
    // что угодно мог прислать фронт
    $t = (string)(
        $request->input('request')
        ?: $request->input('action')
        ?: $request->input('type')
        ?: $request->input('requestType')
        ?: $request->input('r')
        ?: ''
    );

    $t = trim($t);

    // ✅ авто-детект: если requestType не прислали, но есть order_id/after_id — это order_events
    if ($t === '') {
        $orderId = (int)$request->input('order_id', 0);
        if ($orderId > 0 && $request->has('after_id')) {
            return 'order_events';
        }
    }

    return $t;
}


    /* ============================================================
       ORDER ROUTE
       ============================================================ */

    protected function orderRoute(Request $request): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json(['data' => 'error', 'error' => 'no_order_in_session'], 400);
            }

            $this->syncPassengersFromPassengerData();

            $paymethod = (string)$request->input('paymethod', '');
            if ($paymethod === '') $paymethod = 'monobank';

            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)($order['tour_id'] ?? 0),
                (int)($order['from'] ?? 0),
                (int)($order['to'] ?? 0)
            );

            $passengerData = $_SESSION['passenger_data'] ?? [];

            // Идемпотентность: если в сессии уже есть last_order_id и он существует — вернем его
            $existingId = (int)($_SESSION['last_order_id'] ?? 0);
            if ($existingId > 0) {
                $ex = $this->getOrderRowById($existingId);
                if ($ex && (int)($ex['id'] ?? 0) === $existingId) {
                    $passengers = (int)($ex['passengers_count'] ?? $ex['passagers'] ?? 1);
                    $passengers = max(1, min(10, $passengers));

                    $this->orderEvent($existingId, 'order_route_reuse', 'Reusing existing order from session', [
                        'paymethod' => $paymethod,
                    ]);

                    return response()->json([
                        'data'           => 'ok',
                        'mode'           => 'exists',
                        'order_db_id'    => $existingId,
                        'uniqid'         => (string)($ex['uniqid'] ?? ($_SESSION['last_order_uniqid'] ?? '')),
                        'passengers'     => $passengers,
                        'payment_status' => (int)($ex['payment_status'] ?? 0),
                        'price'          => (int)round((float)($ticketInfo['price'] ?? 0)),
                    ]);
                }
            }

            $orderId = $this->createOrder($order, $ticketInfo, $passengerData, $paymethod);

            if (!$orderId) {
                return response()->json([
                    'data' => 'err',
                    'code' => 'order_route_failed',
                    'message' => 'create_order_failed',
                ], 500);
            }

            $_SESSION['last_order_id'] = $orderId;

            $passengers = (int)($_SESSION['order']['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $paymentStatus = ($paymethod === 'cash') ? 1 : 3;

            return response()->json([
                'data'           => 'ok',
                'mode'           => 'created',
                'order_db_id'    => $orderId,
                'uniqid'         => (string)($_SESSION['last_order_uniqid'] ?? ''),
                'passengers'     => $passengers,
                'payment_status' => $paymentStatus,
                'price'          => (int)round((float)($ticketInfo['price'] ?? 0)),
            ]);

        } catch (\Throwable $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json([
                'data' => 'err',
                'code' => 'order_route_failed',
                'message' => $e->getMessage(),
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

    /* ============================================================
       EMAIL (manual request)
       ============================================================ */

    protected function sendOrderEmail(Request $request): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json('no_order', 400);
            }

            $this->syncPassengersFromPassengerData();

            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)($order['tour_id'] ?? 0),
                (int)($order['from'] ?? 0),
                (int)($order['to'] ?? 0)
            );

            $passengerData = $_SESSION['passenger_data'] ?? [];
            if (empty($passengerData['email'])) {
                return response()->json('no_email', 400);
            }

            $passengers = (int)($order['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $emailData = [
                'ticketInfo'    => $ticketInfo,
                'order'         => $order,
                'passengerData' => $passengerData,
                'totalPrice'    => (int)round($passengers * (float)($ticketInfo['price'] ?? 0)),
            ];

            Mail::send('emails.order_confirmation', $emailData, function ($message) use ($passengerData) {
                $message->to($passengerData['email'])
                    ->subject('Подтверждение заказа билета');
            });

            return response()->json('ok');

        } catch (\Throwable $e) {
            Log::error('Email sending error: ' . $e->getMessage());
            return response()->json('error', 500);
        }
    }

    /* ============================================================
       LEGACY LIQPAY
       ============================================================ */

    public function createLegacyPayment(Request $request): JsonResponse
    {
        try {
            if (!isset($_SESSION['order']['tour_id'])) {
                return response()->json(['success' => false, 'error' => 'no_order_in_session'], 400);
            }

            $this->syncPassengersFromPassengerData();

            $order = $_SESSION['order'];

            $ticketInfo = $this->getTicketInfo(
                (int)($order['tour_id'] ?? 0),
                (int)($order['from'] ?? 0),
                (int)($order['to'] ?? 0)
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

        } catch (\Throwable $e) {
            Log::error('Legacy payment creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* ============================================================
       CREATE ORDER
       ============================================================ */

    protected function createOrder($order, $ticketInfo, $passengerData, $paymethod): ?int
    {
        try {
            $table = $this->ordersTable();

            $passengers = (int)($order['passengers'] ?? 1);
            $passengers = max(1, min(10, $passengers));

            $uniqid = 'order_' . uniqid('', true);
            $now = date('Y-m-d H:i:s');

            $rawPhone = (string)($passengerData['phone'] ?? '');
            $phoneDigits = preg_replace('/\D+/', '', $rawPhone);
            if (strlen($phoneDigits) > 20) {
                $phoneDigits = substr($phoneDigits, 0, 20);
            }

            $orderData = [
                'active'           => 1,
                'client_id'        => (int)($this->user->id ?? 0),

                'tour_id'          => (int)($order['tour_id'] ?? 0),
                'from_stop'        => (int)($order['from'] ?? 0),
                'to_stop'          => (int)($order['to'] ?? 0),
                'tour_date'        => $order['date'] ?? date('Y-m-d'),

                'passengers_count' => $passengers,
                'passagers'        => $passengers,

                'document'         => 0,
                'date'             => $now,
                'created_at'       => $now,

                'ticket_return'    => 0,

                'client_name'      => (string)($passengerData['name'] ?? ''),
                'client_surname'   => (string)($passengerData['family_name'] ?? ''),
                'client_patronymic'=> (string)($passengerData['patronymic'] ?? ''),
                'client_birth_date'=> (string)($passengerData['birth_date'] ?? ''),

                'client_email'     => (string)($passengerData['email'] ?? ''),
                'client_phone'     => $phoneDigits ?: (string)($passengerData['phone'] ?? ''),
                'client_phone_code'=> (string)($passengerData['phone_code'] ?? ''),

                'passengers_data'  => json_encode($passengerData['passengers'] ?? [], JSON_UNESCAPED_UNICODE),

                'uniqid'           => $uniqid,

                'payment_status'   => ($paymethod === 'cash') ? 1 : 3,
                'mono_status'      => ($paymethod === 'monobank') ? 'created' : null,
            ];

            $orderData = $this->filterOrderDataByExistingColumns($orderData);
            if (!$orderData) {
                Log::error('createOrder: no valid columns to insert (check mt_orders structure)');
                return null;
            }

            $newId = null;

            if ($this->db) {
                $newId = $this->db->insert($table, $orderData);
            } else {
                $newId = DB::table($table)->insertGetId($orderData);
            }

            if (!$newId) {
                Log::error('createOrder: insert failed');
                return null;
            }

            $_SESSION['last_order_id'] = (int)$newId;
            $_SESSION['last_order_uniqid'] = $uniqid;

            $this->orderEvent((int)$newId, 'order_created', 'Order inserted into mt_orders', [
                'paymethod' => $paymethod,
                'uniqid' => $uniqid,
                'tour_id' => (int)($order['tour_id'] ?? 0),
                'from' => (int)($order['from'] ?? 0),
                'to' => (int)($order['to'] ?? 0),
                'tour_date' => $order['date'] ?? null,
                'passengers' => $passengers,
            ]);

            return (int)$newId;

        } catch (\Throwable $e) {
            Log::error('Order creation DB error: ' . $e->getMessage());
            return null;
        }
    }

    /* ============================================================
       DATA QUERIES
       ============================================================ */

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

        try {
            if ($this->db) {
                $result = $this->db->getOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]);
                return $result ? (is_array($result) ? $result : (array)$result) : [];
            }

            $result = DB::selectOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]);
            return $result ? (array)$result : [];
        } catch (\Throwable $e) {
            Log::error('getTicketInfo error: ' . $e->getMessage());
            return [];
        }
    }

    protected function getMonthName($date, $lang = 'ru'): array
    {
        $prefix = DB_PREFIX;
        $month = (int)explode('-', (string)$date)[1];

        try {
            if ($this->db) {
                $r = $this->db->getOne(
                    "SELECT title_{$lang} AS title FROM `{$prefix}_months` WHERE id = ?",
                    [$month]
                );
                return $r ? (is_array($r) ? $r : (array)$r) : [];
            }

            $result = DB::selectOne(
                "SELECT title_{$lang} as title FROM `{$prefix}_months` WHERE id = ?",
                [$month]
            );
            return $result ? (array)$result : [];
        } catch (\Throwable $e) {
            Log::error('getMonthName error: ' . $e->getMessage());
            return [];
        }
    }

    protected function formatPaymentDateTime($date, $departureTime, $monthData): string
    {
        $parts = explode('-', (string)$date);
        $day = isset($parts[2]) ? (int)$parts[2] : (int)date('d');
        $monthName = $monthData['title'] ?? '';
        $time = $departureTime ? date('H:i', strtotime((string)$departureTime)) : '00:00';

        return "{$day} {$monthName} {$time}";
    }

    /* ============================================================
       PASSENGERS SYNC
       ============================================================ */

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
