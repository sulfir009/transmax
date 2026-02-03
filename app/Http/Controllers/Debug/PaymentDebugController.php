<?php

namespace App\Http\Controllers\Debug;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PaymentDebugController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        if (!$this->isDebugAllowed($request)) {
            return response()->json(['error' => 'debug_not_allowed'], 403);
        }

        $correlationId = (string) Str::uuid();
        $orderId = $request->query('order_id');
        $uniqid = $request->query('uniqid');

        $order = null;
        if ($orderId) {
            $order = Order::find($orderId);
        }

        if (!$order && $uniqid) {
            $order = Order::where('uniqId', (string) $uniqid)
                ->orWhere('uniqid', (string) $uniqid)
                ->first();
        }

        $orderFound = $order !== null;
        $orderTable = $order?->getTable();
        $ticketFiles = [];

        if ($orderFound) {
            $pattern = storage_path('app/tickets/ticket_' . $order->id . '*.pdf');
            $ticketFiles = glob($pattern) ?: [];
        }

        $mailDriver = config('mail.default');
        $lastFinalizeError = $orderFound ? Cache::get($this->finalizeErrorKey($order->id)) : null;
        $lastFinalizeAttempt = $orderFound ? Cache::get($this->finalizeAttemptKey($order->id)) : null;
        $lastWebhook = $orderFound ? Cache::get($this->webhookKey($order->id)) : null;

        $adminStatus = [
            'order_in_online_list' => false,
            'payment_status' => null,
        ];

        if ($orderFound) {
            $adminStatus['payment_status'] = (int) $order->payment_status;
            $adminStatus['order_in_online_list'] = $this->isOnlineOrder($orderTable, $order->id);
        }

        $response = [
            'route_info' => [
                'handled_by' => 'PaymentDebugController@status',
                'correlation_id' => $correlationId,
            ],
            'order_found' => $orderFound,
            'order_fields' => $orderFound ? [
                'id' => $order->id,
                'uniqid' => $order->uniqid,
                'paymethod' => $order->paymethod ?? null,
                'payment_status' => $order->payment_status,
                'ticket_return' => $order->ticket_return ?? null,
                'mono_invoice_id' => $order->mono_invoice_id ?? null,
                'mono_status' => $order->mono_status ?? null,
            ] : null,
            'ticket_status' => [
                'files_found' => count($ticketFiles),
                'files' => $ticketFiles,
            ],
            'email_status' => [
                'mail_driver' => $mailDriver,
                'last_finalize_error' => $lastFinalizeError,
            ],
            'admin_status' => $adminStatus,
            'webhook_seen' => $lastWebhook,
            'last_finalize_attempt' => $lastFinalizeAttempt,
        ];

        Log::info('[payment_debug] status', [
            'correlation_id' => $correlationId,
            'order_id' => $orderId,
            'uniqid' => $uniqid,
            'order_found' => $orderFound,
        ]);

        return response()->json($response)->header('X-Correlation-Id', $correlationId);
    }

    private function isDebugAllowed(Request $request): bool
    {
        $debugEnabled = (string) $request->query('debug') === '1';
        $token = (string) $request->header('X-Debug-Token');
        $expected = (string) env('PAYMENT_DEBUG_TOKEN');

        if (!$debugEnabled) {
            return false;
        }

        if (app()->environment('local')) {
            return true;
        }

        return $expected !== '' && hash_equals($expected, $token);
    }

    private function isOnlineOrder(string $table, int $orderId): bool
    {
        $orderRow = DB::table($table)->where('id', $orderId)->first();
        if (!$orderRow) {
            return false;
        }

        if (Schema::hasColumn($table, 'payment_status')) {
            if ((int) $orderRow->payment_status === 2) {
                return true;
            }
        }

        return false;
    }

    private function finalizeErrorKey(int $orderId): string
    {
        return 'payment_debug:last_finalize_error:' . $orderId;
    }

    private function finalizeAttemptKey(int $orderId): string
    {
        return 'payment_debug:last_finalize_attempt:' . $orderId;
    }

    private function webhookKey(int $orderId): string
    {
        return 'payment_debug:last_webhook:' . $orderId;
    }
}
