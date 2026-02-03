<?php

namespace App\Service;

use App\Models\Payment;
use Illuminate\Support\Facades\Log;

class LiqPayService
{
    protected \LiqPay $liqpay;
    protected array $config;

    public function __construct()
    {
        $this->config = config('services.liqpay');

        // Проверяем наличие ключей
        if (empty($this->config['public_key']) || empty($this->config['private_key'])) {
            throw new \Exception('LiqPay keys are not configured. Please check your .env file.');
        }

        $this->liqpay = new \LiqPay(
            $this->config['public_key'],
            $this->config['private_key']
        );
    }

    /**
     * Создать форму для оплаты
     */
    public function createPaymentForm(array $params): string
    {
        $defaultParams = [
            'version' => $this->config['version'] ?? '3',
            'public_key' => $this->config['public_key'],
            'action' => 'pay',
            'currency' => $this->config['currency'],
            'language' => $this->config['language'],
            'sandbox' => $this->config['sandbox'] ? '1' : '0',
            'server_url' => route('payment.callback'),
            'result_url' => route('payment.result'),
        ];

        $params = array_merge($defaultParams, $params);

        Log::channel('payment')->debug('Creating payment form', [
            'params' => array_diff_key($params, ['public_key' => 1]) // Не логируем ключи
        ]);

        return $this->liqpay->cnb_form($params);
    }

    /**
     * Создать данные для оплаты (для AJAX)
     */
    public function createPaymentData(array $params): array
    {
        $defaultParams = [
            'version' => $this->config['version'] ?? '3',
            'public_key' => $this->config['public_key'],
            'action' => 'pay',
            'currency' => $this->config['currency'],
            'language' => $this->config['language'],
            'sandbox' => $this->config['sandbox'] ? '1' : '0',
            'server_url' => route('payment.callback'),
            'result_url' => route('payment.result'),
        ];

        $params = array_merge($defaultParams, $params);

        Log::channel('payment')->debug('Creating payment data', [
            'order_id' => $params['order_id'] ?? 'N/A',
            'amount' => $params['amount'] ?? 'N/A',
            'sandbox' => $params['sandbox'],
            'server_url' => $params['server_url'],
            'result_url' => $params['result_url']
        ]);

        $data = base64_encode(json_encode($params));
        $signature = $this->liqpay->cnb_signature($params);

        return [
            'data' => $data,
            'signature' => $signature,
        ];
    }

    /**
     * Обработать callback от LiqPay
     */
    public function processCallback(string $data, string $signature): ?array
    {
        Log::channel('payment')->info('=== LIQPAY PROCESS CALLBACK START ===');
        
        // Проверяем подпись
        Log::channel('payment')->info('Verifying signature...');
        
        if (!$this->verifySignature($data, $signature)) {
            Log::channel('payment')->error('=== SIGNATURE VERIFICATION FAILED ===', [
                'received_signature' => $signature,
                'data_length' => strlen($data)
            ]);
            return null;
        }

        Log::channel('payment')->info('Signature verified successfully');

        // Декодируем данные
        $decodedJson = base64_decode($data);
        
        Log::channel('payment')->info('Decoded base64 data', [
            'json_length' => strlen($decodedJson),
            'json_preview' => substr($decodedJson, 0, 500)
        ]);
        
        $decodedData = json_decode($decodedJson, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::channel('payment')->error('JSON decode error', [
                'error' => json_last_error_msg(),
                'json' => $decodedJson
            ]);
            return null;
        }

        // Логируем полные данные callback
        Log::channel('payment')->info('=== LIQPAY CALLBACK DATA ===', [
            'order_id' => $decodedData['order_id'] ?? 'N/A',
            'status' => $decodedData['status'] ?? 'N/A',
            'payment_id' => $decodedData['payment_id'] ?? 'N/A',
            'amount' => $decodedData['amount'] ?? 'N/A',
            'currency' => $decodedData['currency'] ?? 'N/A',
            'sender_phone' => $decodedData['sender_phone'] ?? 'N/A',
            'sender_card_mask' => $decodedData['sender_card_mask2'] ?? 'N/A',
            'sender_card_bank' => $decodedData['sender_card_bank'] ?? 'N/A',
            'sender_card_type' => $decodedData['sender_card_type'] ?? 'N/A',
            'action' => $decodedData['action'] ?? 'N/A',
            'version' => $decodedData['version'] ?? 'N/A',
            'type' => $decodedData['type'] ?? 'N/A',
            'public_key' => substr($decodedData['public_key'] ?? '', 0, 10) . '...',
            'acq_id' => $decodedData['acq_id'] ?? 'N/A',
            'transaction_id' => $decodedData['transaction_id'] ?? 'N/A',
            'liqpay_order_id' => $decodedData['liqpay_order_id'] ?? 'N/A',
            'description' => $decodedData['description'] ?? 'N/A',
            'create_date' => $decodedData['create_date'] ?? 'N/A',
            'end_date' => $decodedData['end_date'] ?? 'N/A',
            'err_code' => $decodedData['err_code'] ?? 'N/A',
            'err_description' => $decodedData['err_description'] ?? 'N/A',
            'full_data' => $decodedData // Полные данные для отладки
        ]);

        Log::channel('payment')->info('=== LIQPAY PROCESS CALLBACK SUCCESS ===');

        return $decodedData;
    }

    /**
     * Проверить подпись
     */
    public function verifySignature(string $data, string $signature): bool
    {
        $expectedSignature = base64_encode(sha1(
            $this->config['private_key'] . $data . $this->config['private_key'],
            true
        ));

        $isValid = $signature === $expectedSignature;
        
        Log::channel('payment')->debug('Signature verification', [
            'received' => $signature,
            'expected' => $expectedSignature,
            'match' => $isValid
        ]);

        return $isValid;
    }

    /**
     * Получить статус платежа
     */
    public function getPaymentStatus(string $orderId): ?array
    {
        Log::channel('payment')->info('Getting payment status', ['order_id' => $orderId]);
        
        $params = [
            'version' => $this->config['version'] ?? '3',
            'public_key' => $this->config['public_key'],
            'action' => 'status',
            'order_id' => $orderId,
        ];

        try {
            $result = $this->liqpay->api('request', $params);
            $decoded = json_decode($result, true);
            
            Log::channel('payment')->info('Payment status received', [
                'order_id' => $orderId,
                'status' => $decoded['status'] ?? 'N/A',
                'result' => $decoded
            ]);
            
            return $decoded;
        } catch (\Exception $e) {
            Log::channel('payment')->error('Error getting payment status', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Создать возврат платежа
     */
    public function refund(string $orderId, float $amount = null): ?array
    {
        Log::channel('payment')->info('Creating refund', [
            'order_id' => $orderId,
            'amount' => $amount
        ]);
        
        $params = [
            'version' => $this->config['version'] ?? '3',
            'public_key' => $this->config['public_key'],
            'action' => 'refund',
            'order_id' => $orderId,
        ];

        if ($amount !== null) {
            $params['amount'] = $amount;
        }

        try {
            $result = $this->liqpay->api('request', $params);
            $decoded = json_decode($result, true);
            
            Log::channel('payment')->info('Refund result', [
                'order_id' => $orderId,
                'result' => $decoded
            ]);
            
            return $decoded;
        } catch (\Exception $e) {
            Log::channel('payment')->error('Error creating refund', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Создать подписку
     */
    public function createSubscription(array $params): array
    {
        $defaultParams = [
            'version' => $this->config['version'] ?? '3',
            'public_key' => $this->config['public_key'],
            'action' => 'subscribe',
            'currency' => $this->config['currency'],
            'language' => $this->config['language'],
            'sandbox' => $this->config['sandbox'] ? '1' : '0',
            'server_url' => route('payment.callback'),
            'result_url' => route('payment.result'),
            'subscribe_periodicity' => 'month',
        ];

        $params = array_merge($defaultParams, $params);

        $data = base64_encode(json_encode($params));
        $signature = $this->liqpay->cnb_signature($params);

        return [
            'data' => $data,
            'signature' => $signature,
        ];
    }
}
