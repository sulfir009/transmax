<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MonobankAcquiringService
{
    public function createInvoice(array $payload): array
{
    $base  = rtrim((string) config('services.monobank.api_base'), '/');
    $token = (string) config('services.monobank.token');

    if ($base === '' || $token === '') {
        Log::error('[Monobank] Missing config: api_base/token', [
            'has_base'  => $base !== '',
            'has_token' => $token !== '',
        ]);
        throw new \RuntimeException('Monobank config is not set (api_base/token).');
    }

    // ---------------------------------------------------------------------
    // 1) Normalization & validation
    // Monobank expects:
    // - amount: integer in kopecks (minimal units)
    // - ccy: 980 for UAH
    // ---------------------------------------------------------------------

    // amount is required
    if (!array_key_exists('amount', $payload)) {
        Log::error('[Monobank] createInvoice: payload missing amount', ['payload_keys' => array_keys($payload)]);
        throw new \InvalidArgumentException("Monobank payload must contain 'amount'.");
    }

    $rawAmount = $payload['amount'];

    // Helper: parse "2500", "2500.00", "2 500,50" into float uah
    $parseMoneyToFloat = function ($v): float {
        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }

        if (is_string($v)) {
            $s = trim($v);

            // remove spaces and non-breaking spaces
            $s = str_replace(["\xC2\xA0", ' '], '', $s);

            // if contains comma but no dot => comma decimal
            if (str_contains($s, ',') && !str_contains($s, '.')) {
                $s = str_replace(',', '.', $s);
            } else {
                // if both present, assume comma is thousands separator
                $s = str_replace(',', '', $s);
            }

            if (!is_numeric($s)) {
                throw new \InvalidArgumentException("Invalid amount format: '{$v}'");
            }

            return (float) $s;
        }

        throw new \InvalidArgumentException('Invalid amount type: ' . gettype($v));
    };

    // Heuristic:
    // - If raw is int and looks like already kopecks (>= 1000 and no decimals), keep.
    // - If raw is float/string with decimals -> treat as UAH and convert to kopecks.
    // - If raw is small int (e.g. 2500) it is ambiguous:
    //   In your project total is often in UAH (e.g. 2500). For Monobank we need kopecks => 250000.
    //   We'll treat ALL non-kopeck-safe values as UAH and convert.
    $amountKop = null;

    if (is_int($rawAmount)) {
        // In most of your flows totals are UAH integers (2500), Monobank needs kopecks (250000).
        // So we interpret int as UAH and convert to kopecks.
        $amountKop = (int) round(((float) $rawAmount) * 100);
    } elseif (is_float($rawAmount)) {
        $amountKop = (int) round($rawAmount * 100);
    } else {
        // string / other -> parse to float UAH, then to kopecks
        $uah = $parseMoneyToFloat($rawAmount);
        $amountKop = (int) round($uah * 100);
    }

    if (!is_int($amountKop) || $amountKop < 1) {
        Log::error('[Monobank] createInvoice: invalid normalized amount', [
            'raw_amount'   => $rawAmount,
            'amount_kop'   => $amountKop,
            'payload_part' => array_intersect_key($payload, array_flip(['ccy', 'merchantPaymInfo', 'redirectUrl', 'successUrl', 'failUrl'])),
        ]);
        throw new \InvalidArgumentException("Monobank 'amount' must be >= 1 and in kopecks (integer).");
    }

    $payload['amount'] = $amountKop;

    // default currency if not set
    if (!isset($payload['ccy'])) {
        $payload['ccy'] = 980; // UAH
    }

    // Validate ccy
    if (!is_int($payload['ccy']) && !(is_string($payload['ccy']) && ctype_digit($payload['ccy']))) {
        throw new \InvalidArgumentException("Monobank 'ccy' must be integer (e.g., 980).");
    }
    $payload['ccy'] = (int) $payload['ccy'];

    // merchantPaymInfo.reference is very useful for mapping invoice -> order
    if (isset($payload['merchantPaymInfo']) && is_array($payload['merchantPaymInfo'])) {
        if (isset($payload['merchantPaymInfo']['reference'])) {
            $payload['merchantPaymInfo']['reference'] = (string) $payload['merchantPaymInfo']['reference'];
        }
    }

    // ---------------------------------------------------------------------
    // 2) Request
    // ---------------------------------------------------------------------
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
            'payload' => $payload,
        ]);
        throw $e;
    }

    if (!$resp->ok()) {
        Log::error('[Monobank] invoice/create failed', [
            'status'  => $resp->status(),
            'body'    => $resp->body(),
            'payload' => $payload, // уже нормализованный amount
        ]);
        throw new \RuntimeException('Monobank invoice/create failed: ' . $resp->status());
    }

    $json = $resp->json();

    // Минимальная sanity-проверка ответа
    if (!is_array($json) || empty($json)) {
        Log::error('[Monobank] invoice/create returned empty/invalid json', [
            'status' => $resp->status(),
            'body'   => $resp->body(),
        ]);
        throw new \RuntimeException('Monobank invoice/create returned invalid JSON.');
    }

    return $json;
}

    public function getInvoiceStatus(string $invoiceId): array
    {
        $base  = rtrim(config('services.monobank.api_base'), '/');
        $token = config('services.monobank.token');

        $resp = Http::timeout(12)
            ->retry(2, 300)
            ->withHeaders(['X-Token' => $token, 'Accept' => 'application/json'])
            ->get($base.'/api/merchant/invoice/status', ['invoiceId'=>$invoiceId]);

        if (!$resp->ok()) {
            Log::error('[Monobank] invoice/status failed', ['status'=>$resp->status(),'body'=>$resp->body()]);
            throw new \RuntimeException('Monobank invoice/status failed: '.$resp->status());
        }

        return $resp->json();
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
        return Cache::remember('monobank:merchant_pubkey_pem', 60*60*12, function () {
            $base  = rtrim(config('services.monobank.api_base'), '/');
            $token = config('services.monobank.token');

            $resp = Http::timeout(12)
                ->retry(2, 300)
                ->withHeaders(['X-Token' => $token, 'Accept' => 'application/json'])
                ->get($base.'/api/merchant/pubkey');

            if (!$resp->ok()) {
                Log::error('[Monobank] merchant/pubkey failed', ['status'=>$resp->status(),'body'=>$resp->body()]);
                throw new \RuntimeException('Monobank merchant/pubkey failed: '.$resp->status());
            }

            return (string) $resp->body(); // PEM
        });
    }
}
