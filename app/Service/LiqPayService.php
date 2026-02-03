<?php

namespace App\Service;

use Illuminate\Support\Facades\Log;

class LiqPayService
{
    protected \LiqPay $liqpay;
    protected array $config;

    public function __construct()
    {
        $this->config = (array) config('services.liqpay', []);

        if (empty($this->config['public_key']) || empty($this->config['private_key'])) {
            throw new \RuntimeException('LiqPay keys are not configured. Please check your .env file.');
        }

        $this->liqpay = new \LiqPay(
            (string) $this->config['public_key'],
            (string) $this->config['private_key']
        );
    }

    /**
     * Создать HTML форму (если тебе нужно вернуть готовую форму)
     */
    public function createPaymentForm(array $params): string
    {
        $params = $this->mergeDefaultParams($params);

        $this->assertRequiredParams($params);

        Log::channel('payment')->debug('Creating LiqPay payment form', [
            'order_id' => $params['order_id'] ?? null,
            'amount'   => $params['amount'] ?? null,
            'currency' => $params['currency'] ?? null,
            'sandbox'  => $params['sandbox'] ?? null,
            'server_url' => $params['server_url'] ?? null,
            'result_url' => $params['result_url'] ?? null,
        ]);

        // cnb_form сам отдаёт HTML формы (POST с data + signature)
        return $this->liqpay->cnb_form($params);
    }

    /**
     * Создать data/signature для checkout (для AJAX).
     *
     * ВАЖНО: signature должен быть рассчитан от ТОЙ ЖЕ строки data, которую ты отправишь в LiqPay.
     */
    public function createPaymentData(array $params): array
    {
        $params = $this->mergeDefaultParams($params);

        $this->assertRequiredParams($params);

        $data = $this->encodeParamsToBase64($params);
        $signature = $this->signData($data);

        Log::channel('payment')->debug('Creating LiqPay payment data', [
            'order_id' => $params['order_id'] ?? null,
            'amount'   => $params['amount'] ?? null,
            'currency' => $params['currency'] ?? null,
            'sandbox'  => $params['sandbox'] ?? null,
            'server_url' => $params['server_url'] ?? null,
            'result_url' => $params['result_url'] ?? null,
            'data_len' => strlen($data),
            'sig_len'  => strlen($signature),
        ]);

        // Возвращаем и старые, и новые ключи (чтобы фронт не путался с "data: ok")
        return [
            // старый формат (если у тебя уже так на фронте ждут)
            'data' => $data,
            'signature' => $signature,

            // новый безопасный формат
            'status' => 'ok',
            'liqpay_data' => $data,
            'liqpay_signature' => $signature,
            'action_url' => 'https://www.liqpay.ua/api/3/checkout',
        ];
    }

    /**
     * Обработать callback от LiqPay.
     * LiqPay присылает POST: data, signature
     */
    public function processCallback(string $data, string $signature): ?array
    {
        Log::channel('payment')->info('=== LIQPAY PROCESS CALLBACK START ===', [
            'data_len' => strlen($data),
            'sig_len'  => strlen($signature),
        ]);

        if (!$this->verifySignature($data, $signature)) {
            Log::channel('payment')->error('=== LIQPAY SIGNATURE VERIFICATION FAILED ===', [
                'received_signature' => $signature,
                'data_len' => strlen($data),
            ]);
            return null;
        }

        $decodedJson = base64_decode($data, true);
        if ($decodedJson === false) {
            Log::channel('payment')->error('LIQPAY base64_decode failed', [
                'data_len' => strlen($data),
            ]);
            return null;
        }

        $decodedData = json_decode($decodedJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('payment')->error('LIQPAY JSON decode error', [
                'error' => json_last_error_msg(),
                'json_preview' => substr($decodedJson, 0, 800),
            ]);
            return null;
        }

        Log::channel('payment')->info('=== LIQPAY CALLBACK DATA ===', [
            'order_id' => $decodedData['order_id'] ?? null,
            'status'   => $decodedData['status'] ?? null,
            'amount'   => $decodedData['amount'] ?? null,
            'currency' => $decodedData['currency'] ?? null,
            'payment_id' => $decodedData['payment_id'] ?? null,
            'liqpay_order_id' => $decodedData['liqpay_order_id'] ?? null,
            'transaction_id' => $decodedData['transaction_id'] ?? null,
            'err_code' => $decodedData['err_code'] ?? null,
            'err_description' => $decodedData['err_description'] ?? null,
        ]);

        Log::channel('payment')->info('=== LIQPAY PROCESS CALLBACK SUCCESS ===');

        return $decodedData;
    }

    /**
     * Проверка подписи LiqPay: base64( sha1(private_key + data + private_key, true) )
     */
    public function verifySignature(string $data, string $signature): bool
    {
        $expected = $this->signData($data);
        $ok = hash_equals($expected, $signature);

        Log::channel('payment')->debug('LiqPay signature verification', [
            'match' => $ok ? 1 : 0,
        ]);

        return $ok;
    }

    /**
     * Статус платежа (через API LiqPay).
     * Осторожно: в некоторых версиях liqpay->api уже возвращает массив, а не JSON-строку.
     */
    public function getPaymentStatus(string $orderId): ?array
    {
        Log::channel('payment')->info('Getting LiqPay payment status', ['order_id' => $orderId]);

        $params = [
            'version' => (string)($this->config['version'] ?? '3'),
            'public_key' => (string)$this->config['public_key'],
            'action' => 'status',
            'order_id' => $orderId,
        ];

        try {
            $result = $this->liqpay->api('request', $params);

            // lib может вернуть array или json-string
            $decoded = is_array($result) ? $result : json_decode((string)$result, true);

            Log::channel('payment')->info('LiqPay status received', [
                'order_id' => $orderId,
                'status' => $decoded['status'] ?? null,
            ]);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Error getting LiqPay status', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Refund
     */
    public function refund(string $orderId, float $amount = null): ?array
    {
        Log::channel('payment')->info('Creating LiqPay refund', [
            'order_id' => $orderId,
            'amount' => $amount,
        ]);

        $params = [
            'version' => (string)($this->config['version'] ?? '3'),
            'public_key' => (string)$this->config['public_key'],
            'action' => 'refund',
            'order_id' => $orderId,
        ];

        if ($amount !== null) {
            $params['amount'] = (float)$amount;
        }

        try {
            $result = $this->liqpay->api('request', $params);
            $decoded = is_array($result) ? $result : json_decode((string)$result, true);

            Log::channel('payment')->info('Refund result', [
                'order_id' => $orderId,
                'status' => $decoded['status'] ?? null,
            ]);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            Log::channel('payment')->error('Error creating refund', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Подписка
     */
    public function createSubscription(array $params): array
    {
        $params = array_merge([
            'version' => (string)($this->config['version'] ?? '3'),
            'public_key' => (string)$this->config['public_key'],
            'action' => 'subscribe',
            'currency' => (string)($this->config['currency'] ?? 'UAH'),
            'language' => (string)($this->config['language'] ?? 'uk'),
            'sandbox' => !empty($this->config['sandbox']) ? 1 : 0,
            'server_url' => route('payment.callback'),
            'result_url' => route('payment.result'),
            'subscribe_periodicity' => 'month',
        ], $params);

        $this->assertRequiredParams($params, true);

        $data = $this->encodeParamsToBase64($params);
        $signature = $this->signData($data);

        return [
            'status' => 'ok',
            'liqpay_data' => $data,
            'liqpay_signature' => $signature,
            'action_url' => 'https://www.liqpay.ua/api/3/checkout',

            // на всякий
            'data' => $data,
            'signature' => $signature,
        ];
    }

    /* =========================
       Внутренние хелперы
       ========================= */

    private function mergeDefaultParams(array $params): array
    {
        $defaults = [
            'version' => (string)($this->config['version'] ?? '3'),
            'public_key' => (string)$this->config['public_key'],
            'action' => 'pay',
            'currency' => (string)($this->config['currency'] ?? 'UAH'),
            'language' => (string)($this->config['language'] ?? 'uk'),
            'sandbox' => !empty($this->config['sandbox']) ? 1 : 0,
            'server_url' => route('payment.callback'),
            'result_url' => route('payment.result'),
        ];

        // аккуратно: не даём перезаписать public_key на мусор
        $params = array_merge($defaults, $params);
        $params['public_key'] = (string)$this->config['public_key'];

        // sandbox — int
        $params['sandbox'] = !empty($params['sandbox']) ? 1 : 0;

        // amount — float (если передали)
        if (isset($params['amount'])) {
            $params['amount'] = (float)$params['amount'];
        }

        // order_id — string (liqpay так любит)
        if (isset($params['order_id'])) {
            $params['order_id'] = (string)$params['order_id'];
        }

        return $params;
    }

    private function assertRequiredParams(array $params, bool $isSubscription = false): void
    {
        $required = ['version', 'public_key', 'action', 'currency', 'order_id', 'amount', 'description'];

        if ($isSubscription) {
            // для subscribe amount/order_id/description тоже нужны, плюс subscribe_periodicity
            $required[] = 'subscribe_periodicity';
        }

        foreach ($required as $key) {
            if (!isset($params[$key]) || trim((string)$params[$key]) === '') {
                throw new \InvalidArgumentException("LiqPay param '{$key}' is required.");
            }
        }
    }

    private function encodeParamsToBase64(array $params): string
    {
        $json = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \RuntimeException('json_encode failed: ' . json_last_error_msg());
        }

        return base64_encode($json);
    }

    private function signData(string $data): string
    {
        $privateKey = (string)$this->config['private_key'];

        return base64_encode(sha1(
            $privateKey . $data . $privateKey,
            true
        ));
    }
}
