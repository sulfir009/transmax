<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Service\LiqPayService;
use App\Service\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class LegacyPaymentController extends Controller
{
    protected LiqPayService $liqpayService;
    protected TicketService $ticketService;

    public function __construct(LiqPayService $liqpayService, TicketService $ticketService)
    {
        $this->liqpayService = $liqpayService;
        $this->ticketService = $ticketService;
    }

    /**
     * Создать платеж из legacy страницы
     */
    public function createFromLegacy(Request $request)
    {
        Log::channel('payment')->info('=== CREATE LEGACY PAYMENT START ===');
        
        try {
            // Получаем данные из сессии
            session_start();
            
            Log::channel('payment')->info('Session status check', [
                'session_id' => session_id(),
                'has_order' => isset($_SESSION['order']),
                'session_data' => $_SESSION ?? []
            ]);
            
            if (!isset($_SESSION['order'])) {
                Log::channel('payment')->error('Session order not found');
                return response()->json([
                    'success' => false,
                    'error' => 'Сессия заказа не найдена'
                ], 400);
            }

            $order = $_SESSION['order'];
            $ticketInfo = $request->input('ticket_info');
            $totalPrice = $request->input('total_price');

            Log::channel('payment')->info('Order data from session', [
                'order' => $order,
                'ticket_info' => $ticketInfo,
                'total_price' => $totalPrice
            ]);

            // Генерируем уникальный order_id для LiqPay
            $orderId = 'ORDER_' . $order['order_id'] . '_' . time();

            // Формируем описание
            $description = sprintf(
                'Билет на маршрут %s %s - %s %s на %s, %d пассажиров. Покупатель: %s %s %s %s',
                $ticketInfo['departure_city'],
                $ticketInfo['departure_station'],
                $ticketInfo['arrival_city'],
                $ticketInfo['arrival_station'],
                $order['date'],
                $order['passengers'],
                $order['email'],
                $order['name'],
                $order['family_name'],
                $order['phone']
            );

            Log::channel('payment')->info('Creating payment record', [
                'order_id' => $orderId,
                'amount' => $totalPrice,
                'description' => $description
            ]);

            // Создаем запись о платеже
            $payment = Payment::create([
                'user_id' => null, // legacy система без авторизации
                'order_id' => $orderId,
                'status' => 'created',
                'amount' => $totalPrice,
                'currency' => 'UAH',
                'description' => $description,
            ]);

            Log::channel('payment')->info('Payment record created', [
                'payment_id' => $payment->id,
                'order_id' => $payment->order_id
            ]);

            // Параметры для LiqPay
            $params = [
                'order_id' => $orderId,
                'amount' => $totalPrice,
                'description' => $description,
                'product_description' => $description,
                'result_url' => 'https://maxtransltd.com/dyakuyu-za-bronyuvannya-biletu/',
                'server_url' => route('payment.legacy.callback'),
            ];

            Log::channel('payment')->info('LiqPay params', $params);

            // Создаем данные для формы
            $paymentData = $this->liqpayService->createPaymentData($params);

            Log::channel('payment')->info('=== CREATE LEGACY PAYMENT SUCCESS ===', [
                'order_id' => $orderId,
                'data_length' => strlen($paymentData['data']),
                'signature_length' => strlen($paymentData['signature'])
            ]);

            return response()->json([
                'success' => true,
                'payment_url' => 'https://www.liqpay.ua/api/3/checkout',
                'data' => $paymentData['data'],
                'signature' => $paymentData['signature'],
                'order_id' => $orderId,
            ]);

        } catch (\Exception $e) {
            Log::channel('payment')->error('=== CREATE LEGACY PAYMENT ERROR ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Обработать callback для legacy платежей
     */
    public function callback(Request $request)
    {
        Log::channel('payment')->info('=== LEGACY CALLBACK RECEIVED ===', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'all_input' => $request->all(),
            'headers' => $request->headers->all(),
            'method' => $request->method()
        ]);

        $data = $request->input('data');
        $signature = $request->input('signature');

        Log::channel('payment')->info('Callback raw data', [
            'has_data' => !empty($data),
            'has_signature' => !empty($signature),
            'data_length' => strlen($data ?? ''),
            'signature_length' => strlen($signature ?? ''),
            'data_preview' => substr($data ?? '', 0, 100) . '...'
        ]);

        if (!$data || !$signature) {
            Log::channel('payment')->error('=== CALLBACK FAILED: Missing data or signature ===');
            return response('Bad Request', 400);
        }

        Log::channel('payment')->info('Processing callback with LiqPayService...');
        
        $result = $this->liqpayService->processCallback($data, $signature);

        if (!$result) {
            Log::channel('payment')->error('=== CALLBACK FAILED: Invalid signature ===', [
                'data' => $data,
                'signature' => $signature
            ]);
            return response('Invalid signature', 400);
        }

        Log::channel('payment')->info('Callback processed successfully', [
            'result' => $result,
            'order_id' => $result['order_id'] ?? 'N/A',
            'status' => $result['status'] ?? 'N/A',
            'payment_id' => $result['payment_id'] ?? 'N/A',
            'amount' => $result['amount'] ?? 'N/A'
        ]);

        // Если платеж успешный, обрабатываем билеты
        // В продакшене обрабатываем только реальные успешные платежи
        $allowedStatuses = ['success'];
        if (config('services.liqpay.sandbox')) {
            $allowedStatuses[] = 'sandbox';
        }
        
        Log::channel('payment')->info('Checking payment status', [
            'received_status' => $result['status'] ?? 'N/A',
            'allowed_statuses' => $allowedStatuses,
            'is_sandbox' => config('services.liqpay.sandbox'),
            'status_allowed' => in_array($result['status'] ?? '', $allowedStatuses)
        ]);
        
        if (in_array($result['status'], $allowedStatuses)) {
            Log::channel('payment')->info('=== PAYMENT STATUS OK, PROCESSING TICKETS ===');
            
            // Извлекаем оригинальный order_id из нашего составного ID
            // Формат: ORDER_{original_order_id}_{timestamp}
            $orderId = $result['order_id'];
            $originalOrderId = $orderId;

            Log::channel('payment')->info('Extracting order_id', [
                'raw_order_id' => $orderId
            ]);

            // Убираем префикс ORDER_ и суффикс с timestamp
            if (strpos($orderId, 'ORDER_') === 0) {
                $orderId = substr($orderId, 6); // Убираем 'ORDER_'

                // Находим последнее подчеркивание (перед timestamp)
                $lastUnderscore = strrpos($orderId, '_');
                if ($lastUnderscore !== false) {
                    $orderId = substr($orderId, 0, $lastUnderscore);
                }

                // Не убираем 'order_' - это часть ID в legacy системе
            }

            Log::channel('payment')->info('Order ID extraction complete', [
                'original' => $originalOrderId,
                'extracted' => $orderId
            ]);

            // Обрабатываем успешный платеж
            try {
                Log::channel('payment')->info('=== CALLING TICKET SERVICE ===', [
                    'order_id' => $orderId
                ]);
                
                $ticketResult = $this->ticketService->processSuccessfulPayment($orderId, $result);
                
                Log::channel('payment')->info('=== TICKET SERVICE COMPLETED ===', [
                    'order_id' => $orderId,
                    'result' => $ticketResult ? 'SUCCESS' : 'FAILED'
                ]);
                
            } catch (\Exception $e) {
                Log::channel('payment')->error('=== TICKET SERVICE EXCEPTION ===', [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]);
            }
        } else {
            Log::channel('payment')->warning('=== PAYMENT STATUS NOT ALLOWED ===', [
                'status' => $result['status'] ?? 'N/A',
                'order_id' => $result['order_id'] ?? 'N/A'
            ]);
        }

        Log::channel('payment')->info('=== LEGACY CALLBACK COMPLETE ===');
        
        return response('OK', 200);
    }
}
