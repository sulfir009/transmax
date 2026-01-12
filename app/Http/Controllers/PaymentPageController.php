<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Repository\BusRepository;
use App\Repository\Order\OrderRepository;
use App\Service\LiqPayService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
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
        // Определяем константу DB_PREFIX если она не определена
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'mt');
        }
        
        // Получаем глобальные объекты (временно, пока не рефакторим полностью)
        global $Router, $Db, $User;
        $this->router = $Router;
        $this->db = $Db;
        $this->user = $User;
        
        // Инициализируем репозитории если они не переданы
        $this->busRepository = $busRepository ?: new BusRepository();
        $this->orderRepository = $orderRepository ?: new OrderRepository();
        $this->liqpayService = $liqpayService ?: new LiqPayService();
    }

    /**
     * Отображение страницы оплаты
     */
    public function index(Request $request)
    {
        // Инициализация сессии если нужно
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Проверяем, что билет выбран
        // Временно закомментировано для тестирования
        // if (!isset($_SESSION['order']['tour_id'])) {
        //     return redirect()->route('main');
        // }
        
        // Тестовые данные
        if (!isset($_SESSION['order'])) {
            $_SESSION['order'] = [
                'tour_id' => 1,
                'from' => 1,
                'to' => 2,
                'date' => date('Y-m-d'),
                'passengers' => 2
            ];
        }

        // Установка заголовков для предотвращения кеширования
        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        header("Cache-Control: no-cache, must-revalidate");
        header("Pragma: no-cache");
        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");

        $lang = $this->router->lang ?? 'ru';
        
        // Получаем информацию о билете
        $ticketInfo = $this->getTicketInfo(
            $_SESSION['order']['tour_id'],
            $_SESSION['order']['from'],
            $_SESSION['order']['to']
        );
        
        // Получаем месяц для отображения
        $monthData = $this->getMonthName($_SESSION['order']['date'], $lang);
        
        // Форматируем дату и время оплаты
        $paymentDateTime = $this->formatPaymentDateTime(
            $_SESSION['order']['date'],
            $ticketInfo['departure_time'] ?? '',
            $monthData
        );
        
        // Рассчитываем общую стоимость
        $passengers = $_SESSION['order']['passengers'] ?? 1;
        $totalPrice = $passengers * ($ticketInfo['price'] ?? 0);
        
        // Получаем опции автобуса
        $busOptions = $this->busRepository->getBusOptions($ticketInfo['bus_id'] ?? null);
        
        // Преобразуем в массивы если нужно
        if (!empty($busOptions) && is_object($busOptions[0] ?? null)) {
            $busOptions = array_map(function($item) {
                return (array) $item;
            }, $busOptions);
        }
        
        // Данные для представления
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

        $requestType = $request->input('request');

        switch ($requestType) {
            case 'order_route':
                return $this->orderRoute($request);
                
            case 'delete_order_tour_id':
                return $this->deleteOrderTourId();
                
            case 'order_mail':
                return $this->sendOrderEmail($request);
                
            default:
                return response()->json(['error' => 'Unknown request type'], 400);
        }
    }

    /**
     * Создание заказа
     */
    protected function orderRoute(Request $request): JsonResponse
    {
        try {
            $paymethod = $request->input('paymethod');
            $ticketInfo = $request->input('ticket_info');
            $order = $request->input('order');
            
            // Получаем данные пассажира из сессии
            $passengerData = $_SESSION['passenger_data'] ?? [];
            
            // Создаем запись заказа в базе данных
            $orderId = $this->createOrder($order, $ticketInfo, $passengerData, $paymethod);
            
            if (!$orderId) {
                return response()->json(['data' => 'error'], 500);
            }
            
            // Сохраняем ID заказа в сессию
            $_SESSION['last_order_id'] = $orderId;
            
            return response()->json(['data' => 'ok']);
            
        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage());
            return response()->json(['data' => 'error'], 500);
        }
    }

    /**
     * Создание платежа через LiqPay (для legacy совместимости)
     */
    public function createLegacyPayment(Request $request): JsonResponse
    {
        try {
            $ticketInfo = $request->input('ticket_info');
            $order = $request->input('order');
            $totalPrice = $request->input('total_price');
            
            // Генерируем уникальный order_id
            $orderId = 'TICKET_' . time() . '_' . rand(1000, 9999);
            
            // Формируем описание платежа
            $description = sprintf(
                "Оплата билета: %s - %s, %s",
                $ticketInfo['departure_city'] ?? '',
                $ticketInfo['arrival_city'] ?? '',
                $order['date'] ?? ''
            );
            
            // Создаем данные для LiqPay
            $paymentData = $this->liqpayService->createPaymentData([
                'order_id' => $orderId,
                'amount' => $totalPrice,
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
            $ticketInfo = $request->input('ticket_info');
            $order = $request->input('order');
            
            // Получаем данные пассажира из сессии
            $passengerData = $_SESSION['passenger_data'] ?? [];
            
            if (empty($passengerData['email'])) {
                return response()->json('no_email', 400);
            }
            
            // Подготавливаем данные для письма
            $emailData = [
                'ticketInfo' => $ticketInfo,
                'order' => $order,
                'passengerData' => $passengerData,
                'totalPrice' => $order['passengers'] * $ticketInfo['price']
            ];
            
            // Отправляем письмо
            Mail::send('emails.order_confirmation', $emailData, function($message) use ($passengerData) {
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
            
            // Подготавливаем данные для вставки
            $orderData = [
                'tour_id' => (int)$order['tour_id'],
                'from_stop' => (int)$order['from'],
                'to_stop' => (int)$order['to'],
                'tour_date' => $order['date'],
                'passengers_count' => (int)$order['passengers'],
                'price' => $ticketInfo['price'],
                'total_price' => $order['passengers'] * $ticketInfo['price'],
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
            
            // Вставляем запись в БД
            if ($this->db) {
                return $this->db->insert("{$prefix}_orders", $orderData);
            } else {
                // Laravel DB fallback
                return DB::table("{$prefix}_orders")->insertGetId($orderData);
            }
            
        } catch (\Exception $e) {
            Log::error('Order creation DB error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Получение информации о билете
     */
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
        } else {
            // Laravel DB fallback
            $result = DB::selectOne($sql, [(int)$tourId, (int)$fromStop, (int)$toStop]);
            $result = $result ? (array) $result : null;
        }
        
        return $result ?? [];
    }

    /**
     * Получение названия месяца
     */
    protected function getMonthName($date, $lang = 'ru'): array
    {
        $prefix = DB_PREFIX;
        $month = (int)explode('-', $date)[1];
        
        if ($this->db) {
            return $this->db->getOne(
                "SELECT title_{$lang} AS title FROM `{$prefix}_months` WHERE id = ?",
                [$month]
            ) ?? [];
        } else {
            $result = DB::selectOne(
                "SELECT title_{$lang} as title FROM `{$prefix}_months` WHERE id = ?",
                [$month]
            );
            return $result ? (array) $result : [];
        }
    }

    /**
     * Форматирование даты и времени оплаты
     */
    protected function formatPaymentDateTime($date, $departureTime, $monthData): string
    {
        $day = (int)explode('-', $date)[2];
        $monthName = $monthData['title'] ?? '';
        $time = date('H:i', strtotime($departureTime));
        
        return "{$day} {$monthName} {$time}";
    }
    
    /**
     * Страница благодарности после оплаты
     */
    public function thankYou(Request $request)
    {
        return view('payment.thank-you');
    }
}
