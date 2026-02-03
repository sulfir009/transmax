<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Support\Money;

class MonobankAcquiringService
{
    /**
     * Создание инвойса.
     *
     * ВАЖНО: amount должен быть int в копейках.
     * Если тебе где-то всё ещё прилетает "0.95" строкой/float — мы конвертим в копейки,
     * но ЛУЧШЕ держать контракт: контроллер всегда передаёт копейки.
     */
    public function createInvoice(array $payload): array
    {
        $base  = rtrim((string) config('services.monobank.api_base', 'https://api.monobank.ua'), '/');
        $token = (string) config('services.monobank.token');

        if ($base === '' || $token === '') {
            Log::error('[Monobank] Missing config: api_base/token', [
                'has_base'  => $base !== '',
                'has_token' => $token !== '',
            ]);
            throw new \RuntimeException('Monobank config is not set (api_base/token).');
        }

        if (!array_key_exists('amount', $payload)) {
            Log::error('[Monobank] createInvoice: payload missing amount', [
                'payload_keys' => array_keys($payload),
            ]);
            throw new \InvalidArgumentException("Monobank payload must contain 'amount'.");
        }

        // Нормализуем amount -> int копейки
        $payload['amount'] = $this->normalizeAmountToKopecks($payload['amount']);

        if ($payload['amount'] < 1) {
            Log::error('[Monobank] createInvoice: invalid amount after normalize', [
                'amount' => $payload['amount'],
            ]);
            throw new \InvalidArgumentException("Monobank 'amount' must be >= 1 (kopecks).");
        }

        // default currency
        if (!isset($payload['ccy'])) {
            $payload['ccy'] = 980;
        }
        $payload['ccy'] = (int) $payload['ccy'];

        try {
            $resp = Http::timeout(12)
                ->retry(2, 300)
                ->withHeaders([
                    'X-Token' => $token,
                    'Accept'  => 'application/json',
                ])
                ->post($base . '/api/merchant/invoice/create', $payload);
        } catch (\Throwable $e) {
            Log::error('[Monobank] invoice/create request exception', [
                'msg'     => $e->getMessage(),
                'payload' => $this->maskPayloadForLogs($payload),
            ]);
            throw $e;
        }

        if (!$resp->ok()) {
            Log::error('[Monobank] invoice/create failed', [
                'status'  => $resp->status(),
                'body'    => $resp->body(),
                'payload' => $this->maskPayloadForLogs($payload),
            ]);
            throw new \RuntimeException('Monobank invoice/create failed: ' . $resp->status());
        }

        $json = $resp->json();
        if (!is_array($json) || empty($json)) {
            Log::error('[Monobank] invoice/create returned empty/invalid json', [
                'status' => $resp->status(),
                'body'   => $resp->body(),
            ]);
            throw new \RuntimeException('Monobank invoice/create returned invalid JSON.');
        }

        return $json;
    }

    /**
     * Инвалидация инвойса до оплаты (если надо пересоздать с другой суммой)
     * POST /api/merchant/invoice/remove
     */
    public function removeInvoice(string $invoiceId): array
    {
        $base  = rtrim((string) config('services.monobank.api_base', 'https://api.monobank.ua'), '/');
        $token = (string) config('services.monobank.token');

        if ($base === '' || $token === '') {
            Log::warning('[Monobank] invoice/remove missing config', [
                'invoice_id' => $invoiceId,
                'has_base' => $base !== '',
                'has_token' => $token !== '',
            ]);

            return ['_error' => true, 'error' => 'missing_config'];
        }

        try {
            $resp = Http::timeout(10)
                ->retry(1, 200)
                ->withHeaders([
                    'X-Token' => $token,
                    'Accept'  => 'application/json',
                ])
                ->post($base . '/api/merchant/invoice/remove', [
                    'invoiceId' => $invoiceId,
                ]);
        } catch (\Throwable $e) {
            Log::warning('[Monobank] invoice/remove request exception', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return ['_error' => true, 'error' => 'request_exception', 'message' => $e->getMessage()];
        }

        if (!$resp->ok()) {
            Log::warning('[Monobank] invoice/remove failed', [
                'invoice_id' => $invoiceId,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);

            return ['_error' => true, 'error' => 'http_error', 'http_status' => $resp->status()];
        }

        $json = $resp->json();
        return is_array($json) ? $json : ['ok' => true];
    }

    public function getInvoiceStatus(string $invoiceId): array
    {
        $base  = rtrim((string) config('services.monobank.api_base', 'https://api.monobank.ua'), '/');
        $token = (string) config('services.monobank.token');

        if ($base === '' || $token === '') {
            Log::warning('[Monobank] invoice/status missing config', [
                'invoice_id' => $invoiceId,
                'has_base' => $base !== '',
                'has_token' => $token !== '',
            ]);
            return [
                '_error' => true,
                'error' => 'missing_config',
            ];
        }

        try {
            $resp = Http::timeout(6)
                ->connectTimeout(4)
                ->withHeaders(['X-Token' => $token, 'Accept' => 'application/json'])
                ->get($base . '/api/merchant/invoice/status', ['invoiceId' => $invoiceId]);
        } catch (\Throwable $e) {
            Log::warning('[Monobank] invoice/status request exception', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return [
                '_error' => true,
                'error' => 'request_exception',
                'message' => $e->getMessage(),
            ];
        }

        if (!$resp->ok()) {
            Log::warning('[Monobank] invoice/status failed', [
                'invoice_id' => $invoiceId,
                'status' => $resp->status(),
                'body' => $resp->body(),
            ]);
            return [
                '_error' => true,
                'error' => 'http_error',
                'http_status' => $resp->status(),
            ];
        }

        $json = $resp->json();
        if (!is_array($json)) {
            Log::warning('[Monobank] invoice/status invalid json', [
                'invoice_id' => $invoiceId,
                'body' => $resp->body(),
            ]);
            return [
                '_error' => true,
                'error' => 'invalid_json',
            ];
        }

        return $json;
    }

    public function verifyWebhook(string $rawBody, ?string $xSignHeader): bool
    {
        if (!$xSignHeader) return false;

        $signature = base64_decode($xSignHeader, true);
        if ($signature === false) return false;

        $publicKeyPem = $this->getMerchantPublicKeyPem();

        return openssl_verify($rawBody, $signature, $publicKeyPem, OPENSSL_ALGO_SHA256) === 1;
    }

    private function getMerchantPublicKeyPem(): string
    {
        return Cache::remember('monobank:merchant_pubkey_pem', 60 * 60 * 12, function () {
            $base  = rtrim((string) config('services.monobank.api_base', 'https://api.monobank.ua'), '/');
            $token = (string) config('services.monobank.token');

            $resp = Http::timeout(12)
                ->retry(2, 300)
                ->withHeaders(['X-Token' => $token, 'Accept' => 'application/json'])
                ->get($base . '/api/merchant/pubkey');

            if (!$resp->ok()) {
                Log::error('[Monobank] merchant/pubkey failed', [
                    'status' => $resp->status(),
                    'body' => $resp->body()
                ]);
                throw new \RuntimeException('Monobank merchant/pubkey failed: ' . $resp->status());
            }

            return (string) $resp->body(); // PEM
        });
    }

    /**
     * amount:
     * - int / "95" => 95 коп
     * - "0.95" / 0.95 => 95 коп (интерпретируем как гривны)
     */
    private function normalizeAmountToKopecks(mixed $raw): int
    {
        if (is_int($raw)) {
            return $raw; // уже копейки (строгий контракт)
        }

        if (is_string($raw)) {
            $s = trim($raw);
            if ($s === '') return 0;

            // "95" => копейки
            if (ctype_digit($s)) {
                return (int) $s;
            }

            // "0.95" / "0,95" => гривны -> копейки
            return Money::uahToKopeks($s);
        }

        if (is_float($raw)) {
            // трактуем float как гривны, избегаем float-ошибок
            return Money::uahToKopeks(number_format($raw, 2, '.', ''));
        }

        throw new \InvalidArgumentException('Invalid amount type: ' . gettype($raw));
    }

    private function maskPayloadForLogs(array $payload): array
    {
        // ничего критичного там нет, но на всякий случай ограничим объём
        $copy = $payload;

        if (isset($copy['merchantPaymInfo']) && is_array($copy['merchantPaymInfo'])) {
            foreach (['basketOrder', 'comment'] as $k) {
                if (isset($copy['merchantPaymInfo'][$k])) {
                    $copy['merchantPaymInfo'][$k] = '[masked]';
                }
            }
        }

        return $copy;
    }
}
