<?php

namespace App\Service;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Mpdf\Mpdf;
use Throwable;

class TicketService
{
    protected string $dbPrefix;

    public function __construct()
    {
        // На всякий случай, чтобы не падать, если legacy.php не defines DB_PREFIX
        if (!defined('DB_PREFIX')) {
            define('DB_PREFIX', 'mt');
        }

        // Если у тебя реально есть legacy-конфиг, можешь подключить:
        // require_once config_path('legacy.php');

        $this->dbPrefix = (string) DB_PREFIX;
    }

    /**
     * Генерация и отправка билетов после успешной оплаты.
     *
     * ВАЖНО:
     * - $orderIdOrUniq может быть как numeric order_id (1000981),
     *   так и legacy uniqid ("order_....").
     * - paymentData — массив статуса/колбека (Monobank/LiqPay/и т.д.)
     *
     * @param int|string $orderIdOrUniq
     */
    public function processSuccessfulPayment($orderIdOrUniq, $paymentData = [], ?string $correlationId = null): bool
    {
        $paymentData = is_array($paymentData) ? $paymentData : (array)$paymentData;
        $correlationId = $correlationId ?: (string)($paymentData['correlation_id'] ?? '');

        Log::channel('payment')->info('========================================');
        Log::channel('payment')->info('=== TICKET SERVICE: START PROCESSING ===');
        Log::channel('payment')->info('========================================', [
            'correlation_id' => $correlationId,
            'order_input'    => $orderIdOrUniq,
            'payment_data'   => $paymentData,
            'timestamp'      => now()->toIso8601String(),
            'db_prefix'      => $this->dbPrefix,
        ]);

        try {
            $orderInfo = $this->findOrder($orderIdOrUniq);

            if (!$orderInfo) {
                Log::channel('payment')->error('=== TICKET SERVICE: FAILED - ORDER NOT FOUND ===', [
                    'correlation_id' => $correlationId,
                    'order_input'    => $orderIdOrUniq,
                ]);
                return false;
            }

            $orderIdNumeric = (int)($orderInfo->id ?? 0);
            $legacyUniq     = $this->getLegacyUniqId($orderInfo, $orderIdOrUniq);

            Log::channel('payment')->info('=== ORDER FOUND ===', [
                'correlation_id' => $correlationId,
                'order_id'       => $orderIdNumeric,
                'uniqId'         => $legacyUniq,
                'tour_id'        => $orderInfo->tour_id ?? null,
                'tour_date'      => $orderInfo->tour_date ?? null,
                'from_stop'      => $orderInfo->from_stop ?? null,
                'to_stop'        => $orderInfo->to_stop ?? null,
                'passagers'      => $orderInfo->passagers ?? null,
                'client_email'   => $orderInfo->client_email ?? null,
                'payment_status' => $orderInfo->payment_status ?? null,
            ]);

            /**
             * 1) Обновление статуса оплаты (idempotent)
             *    - Если уже payment_status=2, affected_rows будет 0.
             *    - Это НОРМАЛЬНО: билеты всё равно можно отправлять, но
             *      tickets_buy мы не инкрементим.
             */
            $paymentProvider = $this->detectPaymentProvider($paymentData);
            $legacyUpdate = ['payment_status' => 2];

            $legacyUpdate = array_merge($legacyUpdate, $this->buildLegacyPaymentFieldsUpdate($paymentProvider));
            $legacyUpdate = array_merge($legacyUpdate, $this->buildLegacyPaidDateUpdate());

            Log::channel('payment')->info('Updating legacy order payment fields', [
                'correlation_id' => $correlationId,
                'where'          => ['id' => $orderIdNumeric, 'uniqId' => $legacyUniq],
                'update'         => $legacyUpdate,
                'provider'       => $paymentProvider,
                'label'          => $this->detectPaymentLabel($paymentData),
            ]);

            // Обновляем по id, а если вдруг 0 — пробуем по uniqId/uniqid
            $updatedRows = DB::table($this->dbPrefix . '_orders')
                ->where('id', $orderIdNumeric)
                ->where(function ($q) {
                    $q->whereNull('payment_status')->orWhere('payment_status', '<>', 2);
                })
                ->update($legacyUpdate);

            if ($updatedRows === 0 && is_string($legacyUniq) && $legacyUniq !== '') {
                $updatedRows = DB::table($this->dbPrefix . '_orders')
                    ->where(function ($q) use ($legacyUniq) {
                        $q->where('uniqId', $legacyUniq)->orWhere('uniqid', $legacyUniq);
                    })
                    ->where(function ($q) {
                        $q->whereNull('payment_status')->orWhere('payment_status', '<>', 2);
                    })
                    ->update($legacyUpdate);
            }

            Log::channel('payment')->info('Payment status update result', [
                'correlation_id' => $correlationId,
                'affected_rows'  => $updatedRows,
            ]);

            /**
             * 2) tickets_buy инкрементим ТОЛЬКО если мы реально первыми перевели в paid (updatedRows > 0)
             */
            $passengersCount = $this->getPassengersCountFromOrder($orderInfo);

            if ($updatedRows > 0) {
                Log::channel('payment')->info('Increment tickets_buy in tours_sales', [
                    'correlation_id' => $correlationId,
                    'tour_id'        => $orderInfo->tour_id ?? null,
                    'tour_date'      => $orderInfo->tour_date ?? null,
                    'increment_by'   => $passengersCount,
                ]);

                try {
                    DB::table($this->dbPrefix . '_tours_sales')
                        ->where('tour_id', (int)$orderInfo->tour_id)
                        ->where('tour_date', (string)$orderInfo->tour_date)
                        ->increment('tickets_buy', $passengersCount);
                } catch (Throwable $e) {
                    Log::channel('payment')->warning('Failed increment tickets_buy (non-fatal)', [
                        'correlation_id' => $correlationId,
                        'error'          => $e->getMessage(),
                    ]);
                }
            } else {
                Log::channel('payment')->info('Skip tickets_buy increment (already paid earlier)', [
                    'correlation_id' => $correlationId,
                    'payment_status' => (int)($orderInfo->payment_status ?? 0),
                ]);
            }

            /**
             * 3) Инфа о билете
             */
            $ticketInfo = $this->getTicketInfo($orderInfo);
            if (!$ticketInfo) {
                Log::channel('payment')->error('=== TICKET INFO NOT FOUND ===', [
                    'correlation_id' => $correlationId,
                    'tour_id'        => $orderInfo->tour_id ?? null,
                    'from_stop'      => $orderInfo->from_stop ?? null,
                    'to_stop'        => $orderInfo->to_stop ?? null,
                ]);
                return false;
            }

            /**
             * 4) Пассажиры
             *    ВАЖНО: order_id в mt_orders_passangers обычно = numeric mt_orders.id.
             *    Но на всякий пробуем и legacy uniqId.
             */
            $passengers = $this->loadPassengers($orderIdNumeric, $legacyUniq);

            Log::channel('payment')->info('Passengers retrieved', [
                'correlation_id' => $correlationId,
                'count'          => $passengers->count(),
            ]);

            /**
             * 5) PDF
             */
            Log::channel('payment')->info('=== GENERATING PDF TICKETS ===', [
                'correlation_id' => $correlationId,
            ]);

            $pdfFiles = $this->generateTickets($orderInfo, $ticketInfo, $passengers);

            Log::channel('payment')->info('PDF tickets generated', [
                'correlation_id' => $correlationId,
                'count'          => count($pdfFiles),
                'files'          => $pdfFiles,
            ]);

            /**
             * 6) Email (ВАЖНО: не логируем "успешно", пока реально не ушло)
             */
            Log::channel('payment')->info('=== SENDING EMAILS ===', [
                'correlation_id' => $correlationId,
                'client_email'   => $orderInfo->client_email ?? 'N/A',
            ]);

            $emailOk = $this->sendTicketsEmail($orderInfo, $ticketInfo, $passengers, $pdfFiles, $paymentData);

            if ($emailOk) {
                // ✅ Cleanup только если реально отправилось
                foreach ($pdfFiles as $file) {
                    if (is_string($file) && $file !== '' && file_exists($file)) {
                        @unlink($file);
                    }
                }

                // Если у заказа есть флаг tickets_sent_at — отметим (НЕ обязателен)
                $this->markTicketsSentIfPossible($orderIdNumeric);

                Log::channel('payment')->info('========================================');
                Log::channel('payment')->info('=== TICKET SERVICE: SUCCESS ===');
                Log::channel('payment')->info('========================================', [
                    'correlation_id' => $correlationId,
                    'order_id'       => $orderIdNumeric,
                    'uniqId'         => $legacyUniq,
                ]);

                return true;
            }

            // ❗ Не удаляем PDF, чтобы можно было переслать/добить проблему
            Log::channel('payment')->warning('Email not sent - keep PDF files for retry', [
                'correlation_id' => $correlationId,
                'files'          => $pdfFiles,
                'client_email'   => $orderInfo->client_email ?? null,
            ]);

            Log::channel('payment')->error('========================================');
            Log::channel('payment')->error('=== TICKET SERVICE: FAILED (EMAIL) ===');
            Log::channel('payment')->error('========================================', [
                'correlation_id' => $correlationId,
                'order_id'       => $orderIdNumeric,
                'uniqId'         => $legacyUniq,
            ]);

            return false;

        } catch (Throwable $e) {
            Log::channel('payment')->error('========================================');
            Log::channel('payment')->error('=== TICKET SERVICE: EXCEPTION ===');
            Log::channel('payment')->error('========================================', [
                'correlation_id' => $correlationId,
                'error'          => $e->getMessage(),
                'file'           => $e->getFile(),
                'line'           => $e->getLine(),
                'trace'          => $e->getTraceAsString(),
            ]);
            return false;
        }
    }

    /**
     * Поиск заказа по id/uniqId/uniqid
     */
    private function findOrder($orderIdOrUniq)
    {
        $table = $this->dbPrefix . '_orders';

        // 1) numeric -> ищем по id
        if (is_numeric($orderIdOrUniq)) {
            $id = (int)$orderIdOrUniq;
            $row = DB::table($table)->where('id', $id)->first();
            if ($row) return $row;
        }

        // 2) string -> ищем по uniqId / uniqid
        $uniq = (string)$orderIdOrUniq;
        if ($uniq !== '') {
            $row = DB::table($table)
                ->where(function ($q) use ($uniq) {
                    $q->where('uniqId', $uniq)->orWhere('uniqid', $uniq);
                })
                ->first();
            if ($row) return $row;
        }

        // 3) на всякий — варианты "order_" / без "order_"
        $variants = [];
        if (is_string($orderIdOrUniq)) {
            $variants[] = $orderIdOrUniq;
            $variants[] = 'order_' . $orderIdOrUniq;
            $variants[] = str_replace('order_', '', $orderIdOrUniq);
        }

        foreach (array_unique(array_filter($variants)) as $v) {
            $row = DB::table($table)
                ->where(function ($q) use ($v) {
                    $q->where('uniqId', $v)->orWhere('uniqid', $v);
                })
                ->first();
            if ($row) return $row;
        }

        return null;
    }

    private function getLegacyUniqId($orderInfo, $orderIdOrUniq): string
    {
        if (isset($orderInfo->uniqId) && is_string($orderInfo->uniqId) && $orderInfo->uniqId !== '') {
            return $orderInfo->uniqId;
        }
        if (isset($orderInfo->uniqid) && is_string($orderInfo->uniqid) && $orderInfo->uniqid !== '') {
            return $orderInfo->uniqid;
        }
        if (is_string($orderIdOrUniq) && $orderIdOrUniq !== '') {
            return $orderIdOrUniq;
        }
        return 'order_' . (int)($orderInfo->id ?? 0);
    }

    private function getPassengersCountFromOrder($orderInfo): int
    {
        $cnt = 1;
        if (isset($orderInfo->passagers) && is_numeric($orderInfo->passagers)) {
            $cnt = (int)$orderInfo->passagers;
        } elseif (isset($orderInfo->passengers_count) && is_numeric($orderInfo->passengers_count)) {
            $cnt = (int)$orderInfo->passengers_count;
        }
        return max(1, min(10, $cnt));
    }

    private function loadPassengers(int $orderIdNumeric, string $legacyUniq)
    {
        $table = $this->dbPrefix . '_orders_passangers';

        // 1) самый вероятный вариант: order_id = numeric id
        $rows = DB::table($table)->where('order_id', $orderIdNumeric)->get();
        if ($rows->count() > 0) return $rows;

        // 2) fallback: если вдруг order_id хранит uniqId
        if ($legacyUniq !== '') {
            $rows = DB::table($table)->where('order_id', $legacyUniq)->get();
        }

        return $rows;
    }

    /**
     * Получить информацию о билете
     */
    private function getTicketInfo($orderInfo)
    {
        try {
            $result = DB::select("
                SELECT
                    from_stop.departure_time,
                    from_city.title_uk AS departure_station,
                    departure_city.title_uk AS departure_city,
                    to_stop.arrival_time,
                    to_city.title_uk AS arrival_station,
                    arrival_city.title_uk AS arrival_city,
                    bus.title_uk AS bus,
                    bus.id AS bus_id,
                    prices.price
                FROM `{$this->dbPrefix}_tours_stops` AS from_stop
                JOIN `{$this->dbPrefix}_cities` AS from_city ON from_stop.stop_id = from_city.id
                JOIN `{$this->dbPrefix}_tours` AS tours ON from_stop.tour_id = tours.id
                JOIN `{$this->dbPrefix}_cities` AS departure_city ON departure_city.id = tours.departure
                JOIN `{$this->dbPrefix}_tours_stops` AS to_stop ON from_stop.tour_id = to_stop.tour_id
                JOIN `{$this->dbPrefix}_cities` AS to_city ON to_stop.stop_id = to_city.id
                JOIN `{$this->dbPrefix}_cities` AS arrival_city ON arrival_city.id = tours.arrival
                JOIN `{$this->dbPrefix}_buses` AS bus ON tours.bus = bus.id
                JOIN `{$this->dbPrefix}_tours_stops_prices` AS prices ON
                        prices.tour_id = from_stop.tour_id AND
                        prices.from_stop = from_stop.stop_id AND
                        prices.to_stop = to_stop.stop_id
                WHERE from_stop.tour_id = ?
                AND from_stop.stop_id = ?
                AND to_stop.stop_id = ?
            ", [
                (int)$orderInfo->tour_id,
                (int)$orderInfo->from_stop,
                (int)$orderInfo->to_stop
            ]);

            return $result[0] ?? null;

        } catch (Throwable $e) {
            Log::channel('payment')->error('getTicketInfo exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Генерация PDF билетов
     */
    private function generateTickets($orderInfo, $ticketInfo, $passengers): array
    {
        $pdfFiles = [];
        $ticketsPath = storage_path('app/tickets');

        if (!file_exists($ticketsPath)) {
            @mkdir($ticketsPath, 0777, true);
        }

        // Города/остановки
        $fromStop = DB::table($this->dbPrefix . '_cities')->where('id', (int)$orderInfo->from_stop)->first();
        $toStop   = DB::table($this->dbPrefix . '_cities')->where('id', (int)$orderInfo->to_stop)->first();

        if (!$fromStop || !$toStop) {
            Log::channel('payment')->error('Stop not found', [
                'from_stop' => (int)$orderInfo->from_stop,
                'to_stop'   => (int)$orderInfo->to_stop,
            ]);
            return $pdfFiles;
        }

        $fromCity = DB::table($this->dbPrefix . '_cities')->where('id', (int)$fromStop->section_id)->first();
        $toCity   = DB::table($this->dbPrefix . '_cities')->where('id', (int)$toStop->section_id)->first();

        if (!$fromCity || !$toCity) {
            Log::channel('payment')->error('City not found', [
                'from_city_id' => (int)$fromStop->section_id,
                'to_city_id'   => (int)$toStop->section_id,
            ]);
            return $pdfFiles;
        }

        $orderPassengersCount = $this->getPassengersCountFromOrder($orderInfo);

        // Если нет пассажиров — делаем один билет на покупателя
        if ($passengers->count() === 0) {
            $pdfPath = $this->generateSingleTicket(
                $orderInfo,
                $ticketInfo,
                null,
                (string)$fromCity->title_uk,
                (string)$toCity->title_uk,
                (string)$fromStop->title_uk,
                (string)$toStop->title_uk,
                null
            );
            if ($pdfPath) $pdfFiles[] = $pdfPath;
            return $pdfFiles;
        }

        // Если пассажир 1 — обычно один билет
        if ($orderPassengersCount <= 1) {
            $pdfPath = $this->generateSingleTicket(
                $orderInfo,
                $ticketInfo,
                $passengers->first(),
                (string)$fromCity->title_uk,
                (string)$toCity->title_uk,
                (string)$fromStop->title_uk,
                (string)$toStop->title_uk,
                null
            );
            if ($pdfPath) $pdfFiles[] = $pdfPath;
            return $pdfFiles;
        }

        // Иначе — билет каждому пассажиру
        $i = 1;
        foreach ($passengers as $p) {
            $pdfPath = $this->generateSingleTicket(
                $orderInfo,
                $ticketInfo,
                $p,
                (string)$fromCity->title_uk,
                (string)$toCity->title_uk,
                (string)$fromStop->title_uk,
                (string)$toStop->title_uk,
                $i
            );
            if ($pdfPath) $pdfFiles[] = $pdfPath;
            $i++;
        }

        return $pdfFiles;
    }

    private function generateSingleTicket($orderInfo, $ticketInfo, $passenger, string $fromCity, string $toCity, string $fromStop, string $toStop, ?int $passengerNumber): ?string
    {
        try {
            $tmpDir = storage_path('app/mpdf/tmp');
            if (!file_exists($tmpDir)) {
                @mkdir($tmpDir, 0777, true);
            }

            $mpdf = new Mpdf([
                'mode'        => 'utf-8',
                'orientation' => 'P',
                'tempDir'     => $tmpDir,
            ]);

            $passengerName = $this->buildPassengerName($orderInfo, $passenger);

            $html = $this->getTicketTemplate(
                $orderInfo,
                $ticketInfo,
                $passengerName,
                $fromCity,
                $toCity,
                $fromStop,
                $toStop
            );

            $mpdf->WriteHTML($html);

            $filename = $passengerNumber
                ? "ticket_{$orderInfo->id}_passenger_{$passengerNumber}.pdf"
                : "ticket_{$orderInfo->id}.pdf";

            $pdfPath = storage_path('app/tickets/' . $filename);
            $mpdf->Output($pdfPath, 'F');

            return file_exists($pdfPath) ? $pdfPath : null;

        } catch (Throwable $e) {
            Log::channel('payment')->error('generateSingleTicket exception', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    private function buildPassengerName($orderInfo, $passenger): string
    {
        if ($passenger) {
            $n = trim((string)($passenger->name ?? ''));
            $s = trim((string)($passenger->second_name ?? ''));
            $full = trim($n . ' ' . $s);
            if ($full !== '') return $full;
        }

        $cn = trim((string)($orderInfo->client_name ?? ''));
        $cs = trim((string)($orderInfo->client_surname ?? ''));
        $full = trim($cn . ' ' . $cs);

        return $full !== '' ? $full : 'Passenger';
    }

    private function getTicketTemplate($orderInfo, $ticketInfo, string $passengerName, string $fromCity, string $toCity, string $fromStop, string $toStop): string
    {
        $saleDate = $this->safeDate($orderInfo);

        $depTime  = isset($ticketInfo->departure_time) ? substr((string)$ticketInfo->departure_time, 0, 5) : '';
        $tourDate = (string)($orderInfo->tour_date ?? '');
        $price    = (string)($ticketInfo->price ?? '');

        return '
<!DOCTYPE html>
<html lang="uk">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; margin:0; padding:0; }
    table { width:100%; padding:15px; border-collapse:collapse; }
    td { vertical-align:top; padding:10px; }
    .container { padding:0 30px; width:1140px; }
    .tiket_section { padding:20px; border-bottom:2px dashed #000; }
    table.tiket_bordered { padding:20px; border:2px dashed #000; border-radius:10px; }
    .tiket_column.small_info { width:25%; text-align:center; border-right:1px solid #000; padding-right:20px; margin-right:20px; }
    .title { font-weight:bold; }
    .big_title { font-size:18px; text-align:center; }
    .tr_border_top { padding-top:30px; border-top:1px solid #000; }
  </style>
</head>
<body>
<div class="container">
  <section class="tiket_section">
    <table class="tiket_bordered" style="border-collapse:collapse; width:100%;">
      <tr>
        <td class="tiket_column small_info" style="width:25%;">
          <div class="tiket_logo">
            <img style="max-width:100%; height:auto;" src="https://www.maxtransltd.com/public/upload/logos/maxTransLogo.png" alt="">
          </div>
          <div class="date_title title">Продано/Sales</div>
          <div class="date_info">' . htmlspecialchars($saleDate, ENT_QUOTES, 'UTF-8') . '</div>
          <div class="tiket_id" style="margin-bottom:30px;">№' . (int)$orderInfo->id . '</div>
          <div class="qr-code" style="margin-top:30px;">
            <img style="max-width:200px;" src="https://www.maxtransltd.com/public/upload/logos/qr-code.png" alt="">
          </div>
        </td>
        <td class="tiket_column passanger_data" style="width:100%;">
          <div class="big_title title" style="text-align:center; width:100%;">ЕЛЕКТРОННИЙ КВИТОК</div>

          <table>
            <tr>
              <td><b>Рейс/Flight</b>
                <div>' . htmlspecialchars((string)($ticketInfo->departure_city ?? ''), ENT_QUOTES, 'UTF-8') . ' - ' . htmlspecialchars((string)($ticketInfo->arrival_city ?? ''), ENT_QUOTES, 'UTF-8') . '</div>
              </td>
              <td><b>Відправлення/Departure</b>
                <div>' . htmlspecialchars($tourDate, ENT_QUOTES, 'UTF-8') . ' ' . htmlspecialchars($depTime, ENT_QUOTES, 'UTF-8') . '<br>' . htmlspecialchars($fromCity . ' ' . $fromStop, ENT_QUOTES, 'UTF-8') . '</div>
              </td>
              <td><b>Прибуття/Arrival</b>
                <div>' . htmlspecialchars($toCity . ' ' . $toStop, ENT_QUOTES, 'UTF-8') . '</div>
              </td>
            </tr>
            <tr>
              <td><b>Пасажир/Passenger</b>
                <div>' . htmlspecialchars($passengerName, ENT_QUOTES, 'UTF-8') . '</div>
              </td>
              <td><b>Місце/Seat</b>
                <div>На вільне місце</div>
              </td>
              <td><b>Перевізник/Carrier</b>
                <div>Maks Trans LTD</div>
              </td>
            </tr>
          </table>

          <table>
            <tr>
              <td></td>
              <td><div>Тариф<br>Tariff</div><div>' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</div></td>
              <td><div>Страховий збір<br>Insurance fee</div><div>0.00</div></td>
              <td><div>В т.ч. ПДВ<br>Including VAT</div><div>0.00</div></td>
              <td></td><td></td>
            </tr>
            <tr>
              <td><b>Збір/Послуга<br>Fee/Service</b><div>' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</div></td>
              <td><b>Проїзд<br>Passage</b><div>' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</div></td>
              <td><b>Багаж<br>Luggage</b><div></div></td>
              <td><b>Тип<br>Type</b><div>ПОВНИЙ</div></td>
              <td><b>Знижка<br>Discount</b><div></div></td>
              <td><b>Всього, грн<br>Total, UAH</b><div>' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</div></td>
            </tr>
          </table>

          <table>
            <tr class="tr_border_top" style="padding-top:30px; border-top:1px solid;">
              <td></td><td></td>
              <td>
                <div class="add_info_title title">Служба підтримки / Support service</div>
                <div class="add_info_phone">+38 093 272 11 54</div>
              </td>
            </tr>
          </table>

        </td>
      </tr>
    </table>
  </section>

  <section class="pass_info-section">
    <div class="tiket_wrapper container">
      <div class="pass_info_container">
        <div class="pass_info_title title">До відома пасажирів:</div>
        <div class="pass_info-columns_wrapper">
          <div class="pass_info-column">
            <div class="pass_info">1. Після оплати проїзду пасажиру рекомендовано перевірити усі реєстраційні дані, вказані у ваучері бронювання.</div>
            <div class="pass_info">2. Для забезпечення організованої посадки, пасажиру бажано прибути до місця відправлення автобусу.</div>
            <div class="pass_info">3. Відправлення автобусу у рейс здійснюється за місцевим часом.</div>
            <div class="pass_info">4. Пасажир несе відповідальність за дотримання візового режиму та умов перетину кордону.</div>
            <div class="pass_info">5. Для отримання інформації щодо переоформлення або відміни поїздки пасажир може звернутися до офіційних представництв компанії або за телефонами Служби підтримки.</div>
            <div class="pass_info">6. Оплата поїздки свідчить про згоду пасажира з умовами договору оферти.</div>
            <div class="pass_info">7. Квиток є дійсним тільки за умови відповідності ПІБ паспортним даним.</div>
          </div>
        </div>
      </div>

      <div class="pass_info_container">
        <div class="pass_info_title title">Умови повернення квитків:</div>
        <div class="pass_info">- від 72 год і більше до відправлення – 75% від вартості поїздки</div>
        <div class="pass_info">- від 24 год до 72 год до відправлення - 50% від вартості поїздки</div>
        <div class="pass_info">- від 12 год до 24 год до відправлення – 25% від вартості поїздки</div>
        <div class="pass_info">- менше 12 год до відправлення - гроші за поїздку не повертаються</div>
      </div>
    </div>
  </section>

</div>
</body>
</html>';
    }

    private function safeDate($orderInfo): string
    {
        $raw = '';
        if (isset($orderInfo->date) && is_string($orderInfo->date) && $orderInfo->date !== '') {
            $raw = $orderInfo->date;
        } elseif (isset($orderInfo->created_at) && $orderInfo->created_at) {
            $raw = (string)$orderInfo->created_at;
        }

        if ($raw === '') return date('Y-m-d');

        try {
            return Carbon::parse($raw)->format('Y-m-d');
        } catch (Throwable $e) {
            return date('Y-m-d');
        }
    }

    /**
     * Отправка email с билетами (возвращает true только если ушло клиенту и админу (если не test))
     */
    private function sendTicketsEmail($orderInfo, $ticketInfo, $passengers, array $pdfFiles, array $paymentData = []): bool
    {
        try {
            $fromStop = DB::table($this->dbPrefix . '_cities')->where('id', (int)$orderInfo->from_stop)->first();
            $toStop   = DB::table($this->dbPrefix . '_cities')->where('id', (int)$orderInfo->to_stop)->first();

            $fromCity = $fromStop ? DB::table($this->dbPrefix . '_cities')->where('id', (int)$fromStop->section_id)->first() : null;
            $toCity   = $toStop ? DB::table($this->dbPrefix . '_cities')->where('id', (int)$toStop->section_id)->first() : null;

            $emailData = [
                'orderInfo' => $orderInfo,
                'ticketInfo' => $ticketInfo,
                'passengers' => $passengers,
                'fromCity' => (string)($fromCity->title_uk ?? ''),
                'toCity' => (string)($toCity->title_uk ?? ''),
                'fromStop' => (string)($fromStop->title_uk ?? ''),
                'toStop' => (string)($toStop->title_uk ?? ''),
                'paymentMethodLabel' => $this->detectPaymentLabel($paymentData),
                'totalPrice' => (float)($ticketInfo->price ?? 0) * $this->getPassengersCountFromOrder($orderInfo),
            ];

            // Клиент
            $okClient = $this->sendEmailToClient($emailData, $pdfFiles);

            // Админ (если не тест)
            $isTest = stripos((string)($orderInfo->client_name ?? ''), 'test') !== false
                || stripos((string)($orderInfo->client_surname ?? ''), 'test') !== false;

            $okAdmin = true;
            if (!$isTest) {
                $okAdmin = $this->sendEmailToAdmin($emailData, $pdfFiles);
            }

            if (!$okClient || !$okAdmin) {
                Log::channel('payment')->error('Emails FAILED', [
                    'ok_client'    => $okClient ? 1 : 0,
                    'ok_admin'     => $okAdmin ? 1 : 0,
                    'client_email' => (string)($orderInfo->client_email ?? ''),
                ]);
                return false;
            }

            Log::channel('payment')->info('Emails sent successfully', [
                'client_email' => (string)($orderInfo->client_email ?? ''),
            ]);

            return true;

        } catch (Throwable $e) {
            Log::channel('payment')->error('sendTicketsEmail exception', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function sendEmailToClient(array $data, array $pdfFiles): bool
    {
        $subject = "Ваш квиток";
        $to = (string)($data['orderInfo']->client_email ?? '');

        if ($to === '') {
            Log::channel('payment')->warning('Client email empty, skip sending');
            return false;
        }

        $message = $this->getClientEmailTemplate($data);

        $ok = $this->sendEmailWithAttachments($to, $subject, $message, $pdfFiles);

        Log::channel('payment')->info('Client email result', [
            'to' => $to,
            'ok' => $ok ? 1 : 0,
        ]);

        return $ok;
    }

    private function sendEmailToAdmin(array $data, array $pdfFiles): bool
    {
        $count = (int)($data['orderInfo']->passagers ?? 1);

        $subject = $count > 1 ? "Покупка {$count} білетів:" : "Покупка білета:";
        $to = "max210183@ukr.net";

        $message = $this->getAdminEmailTemplate($data);

        $ok = $this->sendEmailWithAttachments($to, $subject, $message, $pdfFiles);

        Log::channel('payment')->info('Admin email result', [
            'to' => $to,
            'ok' => $ok ? 1 : 0,
        ]);

        return $ok;
    }

    /**
     * ✅ Надёжная отправка multipart/mixed:
     * - HTML как base64 (важно для UTF-8)
     * - retry без 5-го параметра (-f), если хостинг/обвязка режет
     * - логируем error_get_last()
     */
    private function sendEmailWithAttachments(string $to, string $subject, string $message, array $attachments): bool
    {
        $eol = "\r\n";

        $fromName  = "Max Trans LTD";
        $fromEmail = "info@maxtransltd.com";

        $encodedSubject = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader($subject, 'UTF-8', 'B', $eol)
            : $subject;

        $boundary = 'b_' . md5((string)microtime(true));

        $headers  = "From: {$fromName} <{$fromEmail}>{$eol}";
        $headers .= "Reply-To: {$fromEmail}{$eol}";
        $headers .= "Date: " . date('r') . $eol;
        $headers .= "Message-ID: <" . md5((string)microtime(true)) . "@maxtransltd.com>{$eol}";
        $headers .= "MIME-Version: 1.0{$eol}";
        $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"{$eol}";
        $headers .= "X-Mailer: PHP/" . PHP_VERSION . $eol;

        // HTML как base64 — чтобы UTF-8 не ломало письмо
        $htmlBase64 = chunk_split(base64_encode($message));

        $body  = "--{$boundary}{$eol}";
        $body .= "Content-Type: text/html; charset=\"UTF-8\"{$eol}";
        $body .= "Content-Transfer-Encoding: base64{$eol}{$eol}";
        $body .= $htmlBase64 . $eol;

        foreach ($attachments as $file) {
            if (!is_string($file) || $file === '' || !file_exists($file)) {
                Log::channel('payment')->warning('Attachment missing / invalid', ['file' => $file]);
                continue;
            }

            $content = file_get_contents($file);
            if ($content === false || $content === '') {
                Log::channel('payment')->warning('Attachment empty / unreadable', ['file' => $file]);
                continue;
            }

            $fileName = basename($file);
            $fileContent = chunk_split(base64_encode($content));

            $body .= "--{$boundary}{$eol}";
            $body .= "Content-Type: application/pdf; name=\"{$fileName}\"{$eol}";
            $body .= "Content-Transfer-Encoding: base64{$eol}";
            $body .= "Content-Disposition: attachment; filename=\"{$fileName}\"{$eol}{$eol}";
            $body .= $fileContent . $eol;
        }

        $body .= "--{$boundary}--{$eol}";

        Log::channel('payment')->info('mail() attempt', [
            'to'           => $to,
            'subject_raw'  => $subject,
            'subject_enc'  => $encodedSubject,
            'sendmail_path'=> ini_get('sendmail_path'),
            'smtp'         => ini_get('SMTP'),
            'smtp_port'    => ini_get('smtp_port'),
            'sapi'         => php_sapi_name(),
        ]);

        // 1) пробуем с envelope-from (-f)
        $params = '-f ' . $fromEmail;
        $result = @mail($to, $encodedSubject, $body, $headers, $params);

        if ($result) {
            Log::channel('payment')->info('mail() OK (with -f)', ['to' => $to]);
            return true;
        }

        $last = error_get_last();
        Log::channel('payment')->warning('mail() FAILED (with -f), retrying without params', [
            'to'         => $to,
            'params'     => $params,
            'last_error' => $last,
        ]);

        // 2) fallback: без 5-го параметра
        $result2 = @mail($to, $encodedSubject, $body, $headers);

        Log::channel('payment')->info('mail() result final', [
            'to'         => $to,
            'result'     => $result2 ? 1 : 0,
            'last_error' => error_get_last(),
        ]);

        return (bool)$result2;
    }

    private function getClientEmailTemplate(array $data): string
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $appUrl = rtrim((string)config('app.url'), '/');
        $imagePath = $appUrl !== '' ? ($appUrl . '/images/legacy/upload/logos/mailLogo.jpeg') : '';

        $orderInfo = $data['orderInfo'];
        $ticketInfo = $data['ticketInfo'];

        $depTime = substr((string)($ticketInfo->departure_time ?? ''), 0, 5);

        $html = '
        <html><head><title>Ваш квиток</title></head><body>
        <div style="text-align:center;margin-bottom:20px;">
            <a href="https://www.maxtransltd.com">
                <img src="' . $e($imagePath) . '" style="max-width:150px;" alt="MaxTrans LTD">
            </a>
        </div>
        <p>Ваш квиток:</p>
        <div style="border-left:4px solid #40A6FF; padding-left:10px;">
            <table style="width:100%; border-collapse:collapse;">';

        if ($data['passengers']->count() > 1) {
            $i = 1;
            foreach ($data['passengers'] as $passenger) {
                $html .= "
                    <tr><td style='font-weight:bold;'>{$i}</td><td></td></tr>
                    <tr><td style='font-weight:bold;'>Квиток</td><td>{$e($orderInfo->id)} {$i}/{$data['passengers']->count()}</td></tr>
                    <tr><td style='font-weight:bold;'>Рейс</td><td>{$e($ticketInfo->departure_city)} - {$e($ticketInfo->arrival_city)}</td></tr>
                    <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($orderInfo->tour_date)} {$e($depTime)}</td></tr>
                    <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($data['fromCity'])} {$e($data['fromStop'])}</td></tr>
                    <tr><td style='font-weight:bold;'>Прибуття</td><td>{$e($data['toCity'])} {$e($data['toStop'])}</td></tr>
                    <tr><td style='font-weight:bold;'>Пасажир</td><td>{$e($passenger->name ?? '')} {$e($passenger->second_name ?? '')}</td></tr>
                    <tr><td style='font-weight:bold;'>Телефон</td><td>{$e($orderInfo->client_phone ?? '')}</td></tr>
                    <tr><td style='font-weight:bold;'>E-mail</td><td>{$e($orderInfo->client_email ?? '')}</td></tr>
                    <tr><td style='font-weight:bold;'>Ціна квитка</td><td>{$e($ticketInfo->price ?? '')}</td></tr>";
                $i++;
            }
        } else {
            $html .= "
                <tr><td style='font-weight:bold;'>Квиток</td><td>{$e($orderInfo->id)}</td></tr>
                <tr><td style='font-weight:bold;'>Рейс</td><td>{$e($ticketInfo->departure_city)} - {$e($ticketInfo->arrival_city)}</td></tr>
                <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($orderInfo->tour_date)} {$e($depTime)}</td></tr>
                <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($data['fromCity'])} {$e($data['fromStop'])}</td></tr>
                <tr><td style='font-weight:bold;'>Прибуття</td><td>{$e($data['toCity'])} {$e($data['toStop'])}</td></tr>
                <tr><td style='font-weight:bold;'>Пасажир</td><td>{$e($orderInfo->client_name ?? '')} {$e($orderInfo->client_surname ?? '')}</td></tr>
                <tr><td style='font-weight:bold;'>Телефон</td><td>{$e($orderInfo->client_phone ?? '')}</td></tr>
                <tr><td style='font-weight:bold;'>E-mail</td><td>{$e($orderInfo->client_email ?? '')}</td></tr>
                <tr><td style='font-weight:bold;'>Ціна квитка</td><td>{$e($ticketInfo->price ?? '')}</td></tr>";
        }

        $html .= "
                <tr><td style='font-weight:bold;'>Сумма замовлення</td><td>{$e($data['totalPrice'])}</td></tr>
            </table>
            <p>У вартість квитка включено перевезення одного місця багажу вагою до 25 кг.</p>
            <p>Перевізник: Maks Trans LTD</p>
        </div>
        </body></html>";

        return $html;
    }

    private function getAdminEmailTemplate(array $data): string
    {
        $e = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

        $appUrl = rtrim((string)config('app.url'), '/');
        $imagePath = $appUrl !== '' ? ($appUrl . '/images/legacy/upload/logos/mailLogo.jpeg') : '';

        $count = (int)($data['orderInfo']->passagers ?? 1);
        $title = $count > 1 ? "Покупка {$count} білетів:" : "Покупка білета:";

        $orderInfo = $data['orderInfo'];
        $ticketInfo = $data['ticketInfo'];
        $depTime = substr((string)($ticketInfo->departure_time ?? ''), 0, 5);

        $html = '
        <html><head><title>' . $e($title) . '</title></head><body>
        <div style="text-align:center;margin-bottom:20px;">
            <a href="https://www.maxtransltd.com">
                <img src="' . $e($imagePath) . '" style="max-width:150px;" alt="MaxTrans LTD">
            </a>
        </div>
        <p>' . $e($title) . '</p>
        <div style="border-left:4px solid #40A6FF; padding-left:10px;">
        <table style="width:100%; border-collapse:collapse;">
            <tr><td style="font-weight:bold;">Покупець</td><td>' . $e(($orderInfo->client_name ?? '') . ' ' . ($orderInfo->client_surname ?? '')) . '</td></tr>
            <tr><td style="font-weight:bold;">Пасажирів</td><td>' . $count . '</td></tr>';

        if ($data['passengers']->count() > 1) {
            $i = 1;
            foreach ($data['passengers'] as $passenger) {
                $html .= "
                    <tr><td style='font-weight:bold;'>{$i}</td><td></td></tr>
                    <tr><td style='font-weight:bold;'>Квиток</td><td>{$e($orderInfo->id)} {$i}/{$data['passengers']->count()}</td></tr>
                    <tr><td style='font-weight:bold;'>Рейс</td><td>{$e($ticketInfo->departure_city)} - {$e($ticketInfo->arrival_city)}</td></tr>
                    <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($orderInfo->tour_date)} {$e($depTime)}</td></tr>
                    <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($data['fromCity'])} {$e($data['fromStop'])}</td></tr>
                    <tr><td style='font-weight:bold;'>Прибуття</td><td>{$e($data['toCity'])} {$e($data['toStop'])}</td></tr>
                    <tr><td style='font-weight:bold;'>Пасажир</td><td>{$e($passenger->name ?? '')} {$e($passenger->second_name ?? '')}</td></tr>
                    <tr><td style='font-weight:bold;'>Телефон</td><td>{$e($orderInfo->client_phone ?? '')}</td></tr>
                    <tr><td style='font-weight:bold;'>E-mail</td><td>{$e($orderInfo->client_email ?? '')}</td></tr>
                    <tr><td style='font-weight:bold;'>Ціна</td><td>{$e($ticketInfo->price ?? '')}</td></tr>";
                $i++;
            }
        } else {
            $html .= "
                <tr><td style='font-weight:bold;'>Квиток</td><td>{$e($orderInfo->id)}</td></tr>
                <tr><td style='font-weight:bold;'>Рейс</td><td>{$e($ticketInfo->departure_city)} - {$e($ticketInfo->arrival_city)}</td></tr>
                <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($orderInfo->tour_date)} {$e($depTime)}</td></tr>
                <tr><td style='font-weight:bold;'>Виїзд</td><td>{$e($data['fromCity'])} {$e($data['fromStop'])}</td></tr>
                <tr><td style='font-weight:bold;'>Прибуття</td><td>{$e($data['toCity'])} {$e($data['toStop'])}</td></tr>
                <tr><td style='font-weight:bold;'>Телефон</td><td>{$e($orderInfo->client_phone ?? '')}</td></tr>
                <tr><td style='font-weight:bold;'>E-mail</td><td>{$e($orderInfo->client_email ?? '')}</td></tr>
                <tr><td style='font-weight:bold;'>Ціна</td><td>{$e($ticketInfo->price ?? '')}</td></tr>";
        }

        $html .= "
            <tr><td style='font-weight:bold;'>Сумма замовлення</td><td>{$e($data['totalPrice'])}</td></tr>
            <tr><td style='font-weight:bold;'>Спосіб оплати</td><td>{$e($data['paymentMethodLabel'] ?? 'Онлайн')}</td></tr>
        </table>
        <p>Перевізник: Maks Trans LTD</p>
        </div>
        </body></html>";

        return $html;
    }

    /**
     * Provider по payload
     */
    private function detectPaymentProvider(array $paymentData): string
    {
        if (!empty($paymentData['invoiceId']) || !empty($paymentData['invoice_id'])) {
            return 'monobank';
        }

        if (!empty($paymentData['liqpay_order_id']) || !empty($paymentData['payment_id']) || !empty($paymentData['public_key'])) {
            return 'liqpay';
        }

        return 'online';
    }

    private function detectPaymentLabel(array $paymentData): string
    {
        $provider = $this->detectPaymentProvider($paymentData);

        if ($provider === 'monobank') return 'Онлайн Monobank';
        if ($provider === 'liqpay')   return 'Онлайн LiqPay';
        return 'Онлайн';
    }

    /**
     * Выставляем “онлайн/провайдер” в релевантные legacy колонки, если они существуют.
     */
    private function buildLegacyPaymentFieldsUpdate(string $provider): array
    {
        $table = $this->dbPrefix . '_orders';

        $columns = [];
        try {
            $cols = DB::select("SHOW COLUMNS FROM `{$table}`");
            foreach ($cols as $c) {
                $columns[(string)$c->Field] = strtolower((string)$c->Type);
            }
        } catch (Throwable $e) {
            // fallback below
        }

        $has = function (string $col) use ($table, $columns): bool {
            if (!empty($columns)) return array_key_exists($col, $columns);
            return Schema::hasColumn($table, $col);
        };

        $isNumeric = function (string $col) use ($columns): bool {
            if (empty($columns[$col])) return false;
            $t = $columns[$col];
            return str_contains($t, 'int') || str_contains($t, 'decimal') || str_contains($t, 'float') || str_contains($t, 'double');
        };

        $onlineNumeric  = 2;
        $onlineString   = 'online';
        $providerString = $provider;

        $u = [];

        $candidates = [
            'payment_type',
            'pay_type',
            'payment_method',
            'pay_method',
            'payment_provider',
            'provider',
            'payment_form',
            'type_pay',
            'payment_way',
            'payment_kind',
        ];

        foreach ($candidates as $col) {
            if (!$has($col)) continue;

            if ($isNumeric($col)) {
                $u[$col] = $onlineNumeric;
            } else {
                if (str_contains($col, 'provider') || str_contains($col, 'method')) {
                    $u[$col] = $providerString;
                } else {
                    $u[$col] = $onlineString;
                }
            }
        }

        if ($has('paid_online')) {
            $u['paid_online'] = 1;
        }

        return $u;
    }

    private function buildLegacyPaidDateUpdate(): array
    {
        $table = $this->dbPrefix . '_orders';
        $u = [];

        $dateCols = ['paid_at', 'payment_date', 'paid_date', 'pay_date', 'date_paid'];
        foreach ($dateCols as $col) {
            if (Schema::hasColumn($table, $col)) {
                $u[$col] = date('Y-m-d H:i:s');
            }
        }

        return $u;
    }

    private function markTicketsSentIfPossible(int $orderId): void
    {
        $table = $this->dbPrefix . '_orders';
        if (!Schema::hasColumn($table, 'tickets_sent_at')) {
            return;
        }

        try {
            DB::table($table)->where('id', $orderId)->update([
                'tickets_sent_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Throwable $e) {
            // не критично
        }
    }
}
