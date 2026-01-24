<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Service\LiqPayService;
use App\Service\Order\CallbackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Обязательно для работы с mt_orders
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
     * Создать платеж и предварительно сохранить заказ в БД
     */
    public function create(Request $request)
    {
        // 1. Инициализация сессии для доступа к legacy данным
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        Log::channel('payment')->info('--------------------------------------------------');
        Log::channel('payment')->info('1. START: PaymentController@create. IP: ' . $request->ip());

        // 2. Проверка данных в сессии
        // Если $_SESSION['order'] пустой, значит пользователь потерял сессию
        if (empty($_SESSION['order']) || empty($_SESSION['passenger_data'])) {
            Log::channel('payment')->error('CRITICAL: Session data missing', [
                'order_in_session' => $_SESSION['order'] ?? 'MISSING',
                'passenger_in_session' => $_SESSION['passenger_data'] ?? 'MISSING'
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Время сессии истекло. Обновите страницу.'], 400);
            }
            return redirect()->route('main')->with('error', 'Время сессии истекло. Пожалуйста, начните бронирование заново.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        // 3. Генерация ID заказа
        // uniqId должен быть уникальным. Используем time() + rand() для надежности
        $uniqId = time() . rand(100, 999);
        $orderId = 'ORDER_' . $uniqId;

        Log::channel('payment')->info("2. Generated Order ID: {$orderId}");

        // 4. СОХРАНЕНИЕ ЗАКАЗА В mt_orders
        // Это тот самый шаг, которого не хватало. Без него TicketService не найдет заказ.
        try {
            $clientData = $_SESSION['passenger_data'];
            $orderData = $_SESSION['order'];

            Log::channel('payment')->info('3. Saving to mt_orders...', [
                'tour_id' => $orderData['tour_id'],
                'client' => $clientData['name'] . ' ' . $clientData['family_name']
            ]);

            // Вставка заказа
            DB::table('mt_orders')->insert([
                'uniqId'         => $orderId,           // Ключ для связи с LiqPay
                'tour_id'        => $orderData['tour_id'],
                'tour_date'      => $orderData['date'],
                'date'           => date('Y-m-d H:i:s'),
                'from_stop'      => $orderData['from'],
                'to_stop'        => $orderData['to'],
                'passagers'      => $orderData['passengers'],
                'client_name'    => $clientData['name'],
                'client_surname' => $clientData['family_name'],
                'client_phone'   => $clientData['phone'],
                'client_email'   => $clientData['email'],
                'client_comment' => '',
                'payment_status' => 1,                  // 1 = Ожидает оплаты
                'lang'           => app()->getLocale(),
                'ip'             => $request->ip()
            ]);

            // Вставка пассажиров
            $passengersList = $clientData['passengers'] ?? [];
            
            // Если массив пассажиров пуст (покупатель едет сам), создаем запись из данных покупателя
            if (empty($passengersList)) {
                $passengersList[] = [
                    'name' => $clientData['name'],
                    'family_name' => $clientData['family_name'],
                    'birthDate' => $clientData['birth_date'] ?? null
                ];
            }

            foreach ($passengersList as $pass) {
                DB::table('mt_orders_passangers')->insert([
                    'order_id'    => $orderId, // Связь по текстовому ID
                    'name'        => $pass['name'] ?? '',
                    'second_name' => $pass['family_name'] ?? ($pass['second_name'] ?? ''),
                    'birth_date'  => $pass['birthDate'] ?? null,
                ]);
            }

            Log::channel('payment')->info('4. DB Insert Success (Order & Passengers)');

        } catch (\Exception $e) {
            // Если здесь ошибка, процесс нужно остановить и показать её
            Log::channel('payment')->error('!!! DB INSERT FAILED !!!', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Ошибка базы данных: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Ошибка при создании заказа. Свяжитесь с администратором.');
        }

        // 5. Создаем техническую запись в таблице payments (Laravel)
        // Это для истории транзакций внутри админки Laravel (если она используется)
        $payment = Payment::create([
            'user_id' => Auth::check() ? Auth::id() : null,
            'order_id' => $orderId,
            'status' => 'created',
            'amount' => $validated['amount'],
            'currency' => config('services.liqpay.currency'),
            'description' => $validated['description'],
        ]);

        // 6. Формируем данные для LiqPay
        $params = [
            'order_id' => $orderId,
            'amount' => $validated['amount'],
            'description' => $validated['description'],
            'product_description' => $validated['description'],
        ];

        Log::channel('payment')->info('5. Sending to LiqPay', ['order_id' => $orderId]);

        // Ответ для AJAX (если используется JS на фронте)
        if ($request->ajax()) {
            $paymentData = $this->liqpayService->createPaymentData($params);
            return response()->json([
                'success' => true,
                'data' => $paymentData['data'],
                'signature' => $paymentData['signature'],
                'order_id' => $orderId,
            ]);
        }

        // Ответ для обычной формы (если JS отключен или другая логика)
        $form = $this->liqpayService->createPaymentForm($params);
        return view('payment.checkout', compact('form', 'payment'));
    }

    /**
     * Обработать callback от LiqPay
     */
    public function callback(Request $request, CallbackService $callbackService)
    {
        Log::channel('payment')->info('=== PAYMENT CALLBACK RECEIVED ===', [
            'ip' => $request->ip(),
            'has_data' => $request->has('data')
        ]);

        // Передаем управление в сервис.
        // Он проверит подпись, статус 'success' и вызовет TicketService
        $success = $callbackService->handle($request->all());

        if (!$success) {
            Log::channel('payment')->error('Callback processing failed in service');
            return response('Error processing', 400);
        }

        Log::channel('payment')->info('Callback processed successfully');
        return response('OK', 200);
    }

    /**
     * Страница результата платежа
     */
    public function result(Request $request)
    {
        Log::channel('payment')->info('=== PAYMENT RESULT PAGE ===');

        $data = $request->input('data');
        $signature = $request->input('signature');

        if (!$data || !$signature) {
            return redirect()->route('payment.index')->with('error', 'Нет данных от платежной системы');
        }

        if (!$this->liqpayService->verifySignature($data, $signature)) {
            Log::channel('payment')->warning('Result page: Invalid signature');
            return redirect()->route('payment.index')->with('error', 'Ошибка проверки подписи');
        }

        $decodedData = json_decode(base64_decode($data), true);
        $orderId = $decodedData['order_id'] ?? null;

        Log::channel('payment')->info('Result page for order: ' . $orderId, ['status' => $decodedData['status'] ?? 'unknown']);

        // Пытаемся найти платеж
        $payment = Payment::where('order_id', $orderId)->first();

        return view('payment.result', compact('payment'));
    }

    /**
     * Проверить статус платежа (API)
     */
    public function status($orderId)
    {
        $payment = Payment::where('order_id', $orderId);

        if (Auth::check()) {
            $payment->where('user_id', Auth::id());
        }

        $payment = $payment->firstOrFail();

        // Запрашиваем статус в LiqPay
        $status = $this->liqpayService->getPaymentStatus($orderId);

        if ($status && isset($status['status'])) {
            $payment->update([
                'status' => $status['status'],
                'paid_at' => $status['status'] === 'success' ? now() : $payment->paid_at,
            ]);
        }

        return response()->json([
            'success' => true,
            'payment' => $payment
        ]);
    }

    /**
     * Создать возврат платежа
     */
    public function refund(Request $request, $orderId)
    {
        Log::channel('payment')->info('Refund request', ['order_id' => $orderId]);

        $payment = Payment::where('order_id', $orderId)->firstOrFail();

        if (!$payment->isPaid()) {
            return response()->json(['success' => false, 'message' => 'Платеж не оплачен'], 400);
        }

        $amount = $request->input('amount');
        $result = $this->liqpayService->refund($orderId, $amount);

        if ($result && isset($result['status'])) {
            return response()->json([
                'success' => true,
                'status' => $result['status'],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Ошибка возврата'], 500);
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
        $payments = $payments->orderBy('created_at', 'desc')->paginate(20);
        return view('payment.history', compact('payments'));
    }
}