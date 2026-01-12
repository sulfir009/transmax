<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Service\LiqPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    protected LiqPayService $liqpayService;

    public function __construct(LiqPayService $liqpayService)
    {
        $this->liqpayService = $liqpayService;
    }

    /**
     * Показать страницу оплаты
     */
    public function index()
    {
        return view('payment.index');
    }

    /**
     * Создать платеж
     */
    public function create(Request $request)
    {
        Log::channel('payment')->info('=== PAYMENT CREATE START ===', [
            'user_id' => Auth::check() ? Auth::id() : null,
            'request_data' => $request->except(['password', 'token'])
        ]);
        
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        // Генерируем уникальный order_id
        $orderId = 'ORDER_' . Str::upper(Str::random(16));

        Log::channel('payment')->info('Creating payment', [
            'order_id' => $orderId,
            'amount' => $validated['amount'],
            'description' => $validated['description']
        ]);

        // Создаем запись о платеже
        $payment = Payment::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'order_id' => $orderId,
            'status' => 'created',
            'amount' => $validated['amount'],
            'currency' => config('services.liqpay.currency'),
            'description' => $validated['description'],
        ]);

        Log::channel('payment')->info('Payment record created', [
            'payment_id' => $payment->id,
            'order_id' => $orderId
        ]);

        // Параметры для LiqPay
        $params = [
            'order_id' => $orderId,
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'product_description' => $validated['description'],
        ];

        // Для AJAX запроса возвращаем данные
        if ($request->ajax()) {
            $paymentData = $this->liqpayService->createPaymentData($params);

            Log::channel('payment')->info('=== PAYMENT CREATE SUCCESS (AJAX) ===', [
                'order_id' => $orderId
            ]);

            return response()->json([
                'success' => true,
                'data' => $paymentData['data'],
                'signature' => $paymentData['signature'],
                'order_id' => $orderId,
            ]);
        }

        // Для обычного запроса возвращаем форму
        $form = $this->liqpayService->createPaymentForm($params);

        Log::channel('payment')->info('=== PAYMENT CREATE SUCCESS (FORM) ===', [
            'order_id' => $orderId
        ]);

        return view('payment.checkout', compact('form', 'payment'));
    }

    /**
     * Обработать callback от LiqPay
     */
    public function callback(Request $request)
    {
        Log::channel('payment')->info('=== PAYMENT CALLBACK RECEIVED ===', [
            'timestamp' => now()->toIso8601String(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'method' => $request->method(),
            'all_input' => $request->all(),
            'headers' => $request->headers->all()
        ]);

        $data = $request->input('data');
        $signature = $request->input('signature');

        if (!$data || !$signature) {
            Log::channel('payment')->error('=== CALLBACK FAILED: Missing data or signature ===', [
                'has_data' => !empty($data),
                'has_signature' => !empty($signature)
            ]);
            return response('Bad Request', 400);
        }

        Log::channel('payment')->info('Processing callback...', [
            'data_length' => strlen($data),
            'signature_length' => strlen($signature)
        ]);

        $result = $this->liqpayService->processCallback($data, $signature);

        if (!$result) {
            Log::channel('payment')->error('=== CALLBACK FAILED: Invalid signature ===');
            return response('Invalid signature', 400);
        }

        Log::channel('payment')->info('=== CALLBACK PROCESSED SUCCESSFULLY ===', [
            'order_id' => $result['order_id'] ?? 'N/A',
            'status' => $result['status'] ?? 'N/A',
            'payment_id' => $result['payment_id'] ?? 'N/A',
            'amount' => $result['amount'] ?? 'N/A'
        ]);

        // LiqPay ожидает любой ответ с кодом 200
        return response('OK', 200);
    }

    /**
     * Страница результата платежа
     */
    public function result(Request $request)
    {
        Log::channel('payment')->info('=== PAYMENT RESULT PAGE ===', [
            'has_data' => $request->has('data'),
            'has_signature' => $request->has('signature')
        ]);

        $data = $request->input('data');
        $signature = $request->input('signature');

        if (!$data || !$signature) {
            Log::channel('payment')->warning('Result page: missing data or signature');
            return redirect()->route('payment.index')
                ->with('error', 'Некорректные данные платежа');
        }

        // Проверяем подпись
        if (!$this->liqpayService->verifySignature($data, $signature)) {
            Log::channel('payment')->warning('Result page: invalid signature');
            return redirect()->route('payment.index')
                ->with('error', 'Неверная подпись платежа');
        }

        // Декодируем данные
        $decodedData = json_decode(base64_decode($data), true);
        $orderId = $decodedData['order_id'] ?? null;

        Log::channel('payment')->info('Result page decoded data', [
            'order_id' => $orderId,
            'status' => $decodedData['status'] ?? 'N/A'
        ]);

        if (!$orderId) {
            return redirect()->route('payment.index')
                ->with('error', 'Платеж не найден');
        }

        // Находим платеж
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            Log::channel('payment')->warning('Result page: payment not found in DB', [
                'order_id' => $orderId
            ]);
            return redirect()->route('payment.index')
                ->with('error', 'Платеж не найден');
        }

        return view('payment.result', compact('payment'));
    }

    /**
     * Проверить статус платежа
     */
    public function status($orderId)
    {
        Log::channel('payment')->info('Checking payment status', ['order_id' => $orderId]);

        $payment = Payment::where('order_id', $orderId);

        if (Auth::check()) {
            $payment->where('user_id', Auth::id());
        }

        $payment = $payment->firstOrFail();

        // Получаем актуальный статус от LiqPay
        $status = $this->liqpayService->getPaymentStatus($orderId);

        if ($status && isset($status['status'])) {
            Log::channel('payment')->info('Payment status from LiqPay', [
                'order_id' => $orderId,
                'status' => $status['status']
            ]);

            // Обновляем статус в базе
            $payment->update([
                'status' => $status['status'],
                'payment_id' => $status['payment_id'] ?? $payment->payment_id,
                'response' => json_encode($status),
                'paid_at' => $status['status'] === 'success' ? now() : $payment->paid_at,
            ]);
        }

        return response()->json([
            'success' => true,
            'payment' => [
                'order_id' => $payment->order_id,
                'status' => $payment->status,
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'is_paid' => $payment->isPaid(),
                'is_failed' => $payment->isFailed(),
                'is_pending' => $payment->isPending(),
            ],
        ]);
    }

    /**
     * Создать возврат платежа
     */
    public function refund(Request $request, $orderId)
    {
        Log::channel('payment')->info('Refund request', [
            'order_id' => $orderId,
            'amount' => $request->input('amount')
        ]);

        $payment = Payment::where('order_id', $orderId);

        if (Auth::check()) {
            $payment->where('user_id', Auth::id());
        }

        $payment = $payment->firstOrFail();

        if (!$payment->isPaid()) {
            Log::channel('payment')->warning('Refund failed: payment not paid', [
                'order_id' => $orderId,
                'status' => $payment->status
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Платеж не может быть возвращен',
            ], 400);
        }

        $amount = $request->input('amount');
        $result = $this->liqpayService->refund($orderId, $amount);

        if ($result && isset($result['status'])) {
            Log::channel('payment')->info('Refund successful', [
                'order_id' => $orderId,
                'status' => $result['status']
            ]);
            return response()->json([
                'success' => true,
                'status' => $result['status'],
                'message' => 'Запрос на возврат отправлен',
            ]);
        }

        Log::channel('payment')->error('Refund failed', [
            'order_id' => $orderId,
            'result' => $result
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка при создании возврата',
        ], 500);
    }

    /**
     * История платежей
     */
    public function history()
    {
        $payments = Payment::query();

        if (Auth::check()) {
            $payments->where('user_id', Auth::id());
        }

        $payments = $payments->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('payment.history', compact('payments'));
    }
}
