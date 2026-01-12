<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\TicketRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Service\LiqPayService;
use App\Services\LocalizationService;
use App\Services\EmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class OplataController extends Controller
{
    private TicketRepositoryInterface $ticketRepository;
    private OrderRepositoryInterface $orderRepository;
    private LiqPayService $liqpayService;
    private LocalizationService $localization;
    private EmailService $emailService;

    public function __construct(
        TicketRepositoryInterface $ticketRepository,
        OrderRepositoryInterface $orderRepository,
        LiqPayService $liqpayService,
        LocalizationService $localization,
        EmailService $emailService
    ) {
        $this->ticketRepository = $ticketRepository;
        $this->orderRepository = $orderRepository;
        $this->liqpayService = $liqpayService;
        $this->localization = $localization;
        $this->emailService = $emailService;
    }

    /**
     * Отображение страницы оплаты
     */
    public function index(Request $request)
    {
        
        // Проверяем наличие данных о заказе в сессии
        if (!isset($_SESSION['order']['tour_id'])) {
            return redirect()->route('main')
                ->with('error', $this->localization->get('ORDER_NOT_FOUND', 'Заказ не найден'));
        }

        $orderData = $_SESSION['order'];
        $currentLang = $this->localization->getCurrentLang();

        // Получаем информацию о билете
        $ticketInfo = $this->ticketRepository->getTicketInfo($orderData, $currentLang);

        if (!$ticketInfo) {
            return redirect()->route('main')
                ->with('error', $this->localization->get('TICKET_INFO_NOT_FOUND', 'Информация о билете не найдена'));
        }

        // Получаем информацию о месяце
        $monthNumber = (int)explode('-', $orderData['date'])[1];
        $month = $this->ticketRepository->getMonthInfo($monthNumber, $currentLang);

        // Формируем дату и время платежа
        $paymentDateTime = $this->formatPaymentDateTime($orderData['date'], $ticketInfo['departure_time'], $month);

        // Вычисляем общую стоимость
        $totalPrice = $this->ticketRepository->calculateTotalPrice($orderData);

        // Получаем опции автобуса
        $busOptions = [];
        if (isset($ticketInfo['bus_id'])) {
            $busOptions = $this->ticketRepository->getBusOptions((int)$ticketInfo['bus_id'], $currentLang);
        }

        // Получаем переводы
        $translations = $this->localization->all();

        // Передаем данные в view
        return view('oplata.index', compact(
            'ticketInfo',
            'paymentDateTime',
            'totalPrice',
            'busOptions',
            'month',
            'orderData',
            'translations',
            'currentLang'
        ));
    }

    /**
     * Создание заказа
     */
    public function createOrder(Request $request)
    {
        try {
            // Валидация входящих данных
            $validated = $request->validate([
                'paymethod' => 'required|in:cardpay,cash',
                'ticket_info' => 'required|array',
                'order' => 'required|array'
            ]);

            $paymethod = $validated['paymethod'];
            $ticketInfo = $validated['ticket_info'];
            $orderSessionData = $validated['order'];

            // Подготавливаем данные для создания заказа в соответствии с существующей структурой БД
            $orderData = [
                'tour_id' => (int)$orderSessionData['tour_id'],
                'from_stop' => (int)$orderSessionData['from'],
                'to_stop' => (int)$orderSessionData['to'],
                'date' => $orderSessionData['date'],
                'passengers' => (int)$orderSessionData['passengers'],
                'paymethod' => $paymethod,
                'client_id' => 0, // Гостевой заказ
                'client_name' => $orderSessionData['client_name'] ?? '',
                'client_surname' => $orderSessionData['client_surname'] ?? '',
                'email' => $orderSessionData['email'] ?? '',
                'phone' => $orderSessionData['phone'] ?? '',
                'document' => 1 // По умолчанию паспорт
            ];

            // Создаем заказ в базе данных
            $order = $this->orderRepository->create($orderData);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'error' => $this->localization->get('MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE', 'Не удалось создать заказ')
                ], 500);
            }

            // Сохраняем ID заказа в сессии
            $_SESSION['last_order_id'] = $order->id;

            // Если выбрана оплата наличными, отправляем email
            if ($paymethod === 'cash') {
                $this->emailService->sendOrderConfirmation($order, $ticketInfo);
            }

            return response()->json([
                'success' => true,
                'data' => 'ok',
                'order_id' => $order->id,
                'paymethod' => $paymethod
            ]);

        } catch (\Exception $e) {
            Log::error('Order creation error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $this->localization->get('MSG_MSG_PAYMENT_PAGE_NE_UDALOSI_OFORMITI_ZAKAZ_POPROBUJTE_POZZHE', 'Ошибка при создании заказа')
            ], 500);
        }
    }

    /**
     * Создание платежа через LiqPay
     */
    public function createPayment(Request $request)
    {
        try {
            $validated = $request->validate([
                'ticket_info' => 'required|array',
                'order' => 'required|array',
                'total_price' => 'required|numeric|min:0.01'
            ]);

            $ticketInfo = $validated['ticket_info'];
            $orderData = $validated['order'];
            $totalPrice = $validated['total_price'];

            // Генерируем уникальный order_id для LiqPay
            $orderId = 'TICKET_' . time() . '_' . Str::random(8);

            // Формируем описание платежа
            $description = sprintf(
                "%s %s - %s, %s, %s: %d",
                $this->localization->get('TICKET', 'Билет'),
                $ticketInfo['departure_city'] ?? '',
                $ticketInfo['arrival_city'] ?? '',
                $orderData['date'] ?? '',
                $this->localization->get('MSG_MSG_PAYMENT_PAGE_PASAZHIRIV', 'пассажиров'),
                $orderData['passengers'] ?? 1
            );

            // Параметры для LiqPay
            $params = [
                'order_id' => $orderId,
                'amount' => $totalPrice,
                'description' => $description,
                'product_description' => $description,
            ];

            // Создаем данные для платежа
            $paymentData = $this->liqpayService->createPaymentData($params);

            return response()->json([
                'success' => true,
                'data' => $paymentData['data'],
                'signature' => $paymentData['signature'],
                'payment_url' => config('services.liqpay.checkout_url'),
                'order_id' => $orderId
            ]);

        } catch (\Exception $e) {
            Log::error('Payment creation error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => $this->localization->get('PAYMENT_ERROR', 'Ошибка создания платежа')
            ], 500);
        }
    }

    /**
     * Удаление ID тура из сессии
     */
    public function deleteOrderTourId()
    {
        unset($_SESSION['order']['tour_id']);

        return response()->json([
            'status' => 'ok',
            'message' => $this->localization->get('ORDER_CLEARED', 'Данные заказа очищены')
        ]);
    }

    /**
     * Форматирование даты и времени платежа
     */
    private function formatPaymentDateTime(string $date, string $departureTime, ?array $month): string
    {
        $day = (int)explode('-', $date)[2];
        $time = date('H:i', strtotime($departureTime));
        $monthTitle = $month['title'] ?? '';

        return $day . ' ' . $monthTitle . ' ' . $time;
    }

    /**
     * Получить информацию о заказе для API
     */
    public function getOrderInfo(Request $request)
    {
        $orderId = $request->input('order_id');

        if (!$orderId) {
            return response()->json([
                'success' => false,
                'error' => $this->localization->get('ORDER_ID_REQUIRED', 'ID заказа обязателен')
            ], 400);
        }

        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            return response()->json([
                'success' => false,
                'error' => $this->localization->get('ORDER_NOT_FOUND', 'Заказ не найден')
            ], 404);
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'status' => $this->mapPaymentStatusToString($order->payment_status),
                'paymethod' => $order->payment_status == 1 ? 'pending' : 'completed',
                'total_price' => $order->passagers * ($order->tour->price ?? 0),
                'created_at' => $order->date
            ]
        ]);
    }

    /**
     * Обновить статус заказа
     */
    public function updateOrderStatus(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|integer',
            'status' => 'required|string|in:pending,completed,cancelled,failed'
        ]);

        $updated = $this->orderRepository->updateStatus($validated['order_id'], $validated['status']);

        return response()->json([
            'success' => $updated,
            'message' => $updated
                ? $this->localization->get('STATUS_UPDATED', 'Статус обновлен')
                : $this->localization->get('STATUS_UPDATE_FAILED', 'Не удалось обновить статус')
        ]);
    }

    /**
     * Преобразовать payment_status в строку
     */
    private function mapPaymentStatusToString(int $paymentStatus): string
    {
        switch ($paymentStatus) {
            case 1:
                return 'pending';
            case 2:
                return 'completed';
            case 3:
                return 'failed';
            case 4:
                return 'cancelled';
            default:
                return 'unknown';
        }
    }
}
