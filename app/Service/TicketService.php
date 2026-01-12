<?php

namespace App\Service;

use Mpdf\Mpdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class TicketService
{
    protected string $dbPrefix;

    public function __construct()
    {
        // Загружаем конфигурацию legacy
        require_once config_path('legacy.php');
        $this->dbPrefix = DB_PREFIX;
    }
    
    /**
     * Генерация и отправка билетов после успешной оплаты
     */
    public function processSuccessfulPayment($orderId, $paymentData)
    {
        Log::channel('payment')->info('========================================');
        Log::channel('payment')->info('=== TICKET SERVICE: START PROCESSING ===');
        Log::channel('payment')->info('========================================', [
            'order_id' => $orderId,
            'payment_data' => $paymentData,
            'timestamp' => now()->toIso8601String(),
            'db_prefix' => $this->dbPrefix
        ]);

        try {
            // Получаем информацию о заказе из legacy БД
            $orderInfo = null;
            $attempts = 0;
            $maxAttempts = 3;

            Log::channel('payment')->info('Searching for order in database', [
                'search_field' => 'uniqId',
                'search_value' => $orderId,
                'table' => $this->dbPrefix . '_orders'
            ]);

            // Пытаемся найти заказ несколько раз с задержкой
            while (!$orderInfo && $attempts < $maxAttempts) {
                Log::channel('payment')->info('Order search attempt ' . ($attempts + 1) . '/' . $maxAttempts);
                
                $orderInfo = DB::table($this->dbPrefix . '_orders')
                    ->where('uniqId', $orderId)
                    ->first();

                if (!$orderInfo && $attempts < $maxAttempts - 1) {
                    Log::channel('payment')->warning('Order not found on attempt ' . ($attempts + 1) . ', waiting 2 seconds...', [
                        'order_id' => $orderId
                    ]);
                    sleep(2);
                }

                $attempts++;
            }

            Log::channel('payment')->info('Order search completed', [
                'order_id' => $orderId,
                'found' => !empty($orderInfo),
                'attempts_made' => $attempts
            ]);

            if (!$orderInfo) {
                Log::channel('payment')->error('=== ORDER NOT FOUND ===', [
                    'order_id' => $orderId
                ]);

                // Попробуем найти по частичному совпадению
                Log::channel('payment')->info('Trying partial match search...');
                
                $possibleOrders = DB::table($this->dbPrefix . '_orders')
                    ->where('uniqId', 'like', '%' . $orderId . '%')
                    ->orWhere('uniqId', 'like', $orderId . '%')
                    ->get();

                if ($possibleOrders->count() > 0) {
                    Log::channel('payment')->warning('Found possible orders with partial match', [
                        'count' => $possibleOrders->count(),
                        'orders' => $possibleOrders->map(function($o) {
                            return ['id' => $o->id, 'uniqId' => $o->uniqId];
                        })->toArray()
                    ]);
                } else {
                    Log::channel('payment')->info('No partial matches found');
                }

                // Покажем последние 10 заказов для отладки
                $recentOrders = DB::table($this->dbPrefix . '_orders')
                    ->select('id', 'uniqId', 'date', 'tour_date', 'client_email', 'payment_status')
                    ->orderBy('id', 'desc')
                    ->limit(10)
                    ->get();

                Log::channel('payment')->info('Recent orders for debugging', [
                    'orders' => $recentOrders->toArray()
                ]);

                // Проверяем разные варианты order_id
                $orderVariants = [
                    $orderId,
                    'order_' . $orderId,
                    str_replace('order_', '', $orderId),
                ];

                Log::channel('payment')->info('Trying order ID variants', [
                    'variants' => $orderVariants
                ]);

                foreach ($orderVariants as $variant) {
                    $checkOrder = DB::table($this->dbPrefix . '_orders')
                        ->where('uniqId', $variant)
                        ->first();

                    if ($checkOrder) {
                        Log::channel('payment')->info('Found order with variant!', [
                            'variant' => $variant,
                            'order_id' => $checkOrder->id,
                            'uniqId' => $checkOrder->uniqId
                        ]);
                        break;
                    } else {
                        Log::channel('payment')->debug('Variant not found: ' . $variant);
                    }
                }

                Log::channel('payment')->error('=== TICKET SERVICE: FAILED - ORDER NOT FOUND ===');
                return false;
            }

            // Заказ найден
            Log::channel('payment')->info('=== ORDER FOUND ===', [
                'order_id' => $orderInfo->id,
                'uniqId' => $orderInfo->uniqId,
                'tour_id' => $orderInfo->tour_id,
                'tour_date' => $orderInfo->tour_date ?? 'N/A',
                'from_stop' => $orderInfo->from_stop ?? 'N/A',
                'to_stop' => $orderInfo->to_stop ?? 'N/A',
                'passagers' => $orderInfo->passagers ?? 'N/A',
                'client_name' => $orderInfo->client_name ?? 'N/A',
                'client_surname' => $orderInfo->client_surname ?? 'N/A',
                'client_email' => $orderInfo->client_email ?? 'N/A',
                'client_phone' => $orderInfo->client_phone ?? 'N/A',
                'payment_status' => $orderInfo->payment_status ?? 'N/A',
                'date' => $orderInfo->date ?? 'N/A'
            ]);

            // Обновляем статус оплаты
            Log::channel('payment')->info('Updating payment status to 2 (paid)');
            
            $updateResult = DB::table($this->dbPrefix . '_orders')
                ->where('uniqId', $orderId)
                ->update(['payment_status' => 2]);
            
            Log::channel('payment')->info('Payment status update result', [
                'affected_rows' => $updateResult
            ]);

            // Обновляем количество проданных билетов
            Log::channel('payment')->info('Updating tickets_buy in tours_sales', [
                'tour_id' => $orderInfo->tour_id,
                'tour_date' => $orderInfo->tour_date,
                'increment_by' => $orderInfo->passagers
            ]);
            
            $salesUpdateResult = DB::table($this->dbPrefix . '_tours_sales')
                ->where('tour_id', $orderInfo->tour_id)
                ->where('tour_date', $orderInfo->tour_date)
                ->increment('tickets_buy', $orderInfo->passagers);
            
            Log::channel('payment')->info('Tours sales update result', [
                'affected_rows' => $salesUpdateResult
            ]);

            // Получаем информацию о билете
            Log::channel('payment')->info('Getting ticket info', [
                'tour_id' => $orderInfo->tour_id,
                'from_stop' => $orderInfo->from_stop,
                'to_stop' => $orderInfo->to_stop
            ]);
            
            $ticketInfo = $this->getTicketInfo($orderInfo);
            
            if (!$ticketInfo) {
                Log::channel('payment')->error('=== TICKET INFO NOT FOUND ===', [
                    'tour_id' => $orderInfo->tour_id,
                    'from_stop' => $orderInfo->from_stop,
                    'to_stop' => $orderInfo->to_stop
                ]);
                return false;
            }
            
            Log::channel('payment')->info('Ticket info retrieved', [
                'departure_city' => $ticketInfo->departure_city ?? 'N/A',
                'arrival_city' => $ticketInfo->arrival_city ?? 'N/A',
                'departure_station' => $ticketInfo->departure_station ?? 'N/A',
                'arrival_station' => $ticketInfo->arrival_station ?? 'N/A',
                'departure_time' => $ticketInfo->departure_time ?? 'N/A',
                'arrival_time' => $ticketInfo->arrival_time ?? 'N/A',
                'price' => $ticketInfo->price ?? 'N/A',
                'bus' => $ticketInfo->bus ?? 'N/A'
            ]);

            // Получаем пассажиров
            Log::channel('payment')->info('Getting passengers', [
                'order_id' => $orderId,
                'table' => $this->dbPrefix . '_orders_passangers'
            ]);
            
            $passengers = DB::table($this->dbPrefix . '_orders_passangers')
                ->where('order_id', $orderId)
                ->get();

            Log::channel('payment')->info('Passengers retrieved', [
                'count' => $passengers->count(),
                'passengers' => $passengers->map(function($p) {
                    return [
                        'id' => $p->id ?? 'N/A',
                        'name' => $p->name ?? 'N/A',
                        'second_name' => $p->second_name ?? 'N/A'
                    ];
                })->toArray()
            ]);

            // Генерируем PDF билеты
            Log::channel('payment')->info('=== GENERATING PDF TICKETS ===');
            
            $pdfFiles = $this->generateTickets($orderInfo, $ticketInfo, $passengers);
            
            Log::channel('payment')->info('PDF tickets generated', [
                'count' => count($pdfFiles),
                'files' => $pdfFiles
            ]);

            // Отправляем email
            Log::channel('payment')->info('=== SENDING EMAILS ===', [
                'client_email' => $orderInfo->client_email ?? 'N/A'
            ]);
            
            $this->sendTicketsEmail($orderInfo, $ticketInfo, $passengers, $pdfFiles);
            
            Log::channel('payment')->info('Emails sent successfully');

            // Удаляем временные PDF файлы
            Log::channel('payment')->info('Cleaning up temporary PDF files');
            
            foreach ($pdfFiles as $file) {
                if (file_exists($file)) {
                    unlink($file);
                    Log::channel('payment')->debug('Deleted temp file: ' . $file);
                }
            }

            Log::channel('payment')->info('========================================');
            Log::channel('payment')->info('=== TICKET SERVICE: SUCCESS ===');
            Log::channel('payment')->info('========================================');
            
            return true;

        } catch (\Exception $e) {
            Log::channel('payment')->error('========================================');
            Log::channel('payment')->error('=== TICKET SERVICE: EXCEPTION ===');
            Log::channel('payment')->error('========================================', [
                'error' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Получить информацию о билете
     */
    private function getTicketInfo($orderInfo)
    {
        Log::channel('payment')->debug('Executing getTicketInfo query', [
            'tour_id' => $orderInfo->tour_id,
            'from_stop' => $orderInfo->from_stop,
            'to_stop' => $orderInfo->to_stop
        ]);
        
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
            ", [$orderInfo->tour_id, $orderInfo->from_stop, $orderInfo->to_stop]);
            
            $ticketInfo = $result[0] ?? null;
            
            Log::channel('payment')->debug('getTicketInfo result', [
                'found' => !empty($ticketInfo),
                'data' => $ticketInfo ? (array)$ticketInfo : null
            ]);
            
            return $ticketInfo;
            
        } catch (\Exception $e) {
            Log::channel('payment')->error('getTicketInfo exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Генерация PDF билетов
     */
    private function generateTickets($orderInfo, $ticketInfo, $passengers)
    {
        Log::channel('payment')->info('generateTickets called', [
            'passengers_count' => $passengers->count(),
            'order_passagers' => $orderInfo->passagers
        ]);
        
        $pdfFiles = [];
        $ticketsPath = storage_path('app/tickets');

        if (!file_exists($ticketsPath)) {
            mkdir($ticketsPath, 0777, true);
            Log::channel('payment')->info('Created tickets directory: ' . $ticketsPath);
        }

        // Получаем информацию о городах и остановках
        Log::channel('payment')->debug('Getting city/stop info', [
            'from_stop_id' => $orderInfo->from_stop,
            'to_stop_id' => $orderInfo->to_stop
        ]);
        
        $fromStop = DB::table($this->dbPrefix . '_cities')
            ->where('id', $orderInfo->from_stop)
            ->first();

        $toStop = DB::table($this->dbPrefix . '_cities')
            ->where('id', $orderInfo->to_stop)
            ->first();

        if (!$fromStop || !$toStop) {
            Log::channel('payment')->error('Stop not found', [
                'from_stop' => $fromStop ? 'found' : 'NOT FOUND',
                'to_stop' => $toStop ? 'found' : 'NOT FOUND'
            ]);
            return $pdfFiles;
        }

        $fromCity = DB::table($this->dbPrefix . '_cities')
            ->where('id', $fromStop->section_id)
            ->first();

        $toCity = DB::table($this->dbPrefix . '_cities')
            ->where('id', $toStop->section_id)
            ->first();

        if (!$fromCity || !$toCity) {
            Log::channel('payment')->error('City not found', [
                'from_city' => $fromCity ? 'found' : 'NOT FOUND',
                'to_city' => $toCity ? 'found' : 'NOT FOUND'
            ]);
            return $pdfFiles;
        }

        Log::channel('payment')->info('Cities and stops info', [
            'from_city' => $fromCity->title_uk ?? 'N/A',
            'to_city' => $toCity->title_uk ?? 'N/A',
            'from_stop' => $fromStop->title_uk ?? 'N/A',
            'to_stop' => $toStop->title_uk ?? 'N/A'
        ]);

        if ($passengers->count() == 0 || $orderInfo->passagers == 1) {
            Log::channel('payment')->info('Generating single ticket for buyer');
            
            // Один билет для покупателя
            $pdfPath = $this->generateSingleTicket(
                $orderInfo,
                $ticketInfo,
                $passengers->first(),
                $fromCity->title_uk,
                $toCity->title_uk,
                $fromStop->title_uk,
                $toStop->title_uk
            );
            
            if ($pdfPath) {
                $pdfFiles[] = $pdfPath;
                Log::channel('payment')->info('Single ticket generated: ' . $pdfPath);
            }
        } else {
            Log::channel('payment')->info('Generating tickets for each passenger', [
                'count' => $passengers->count()
            ]);
            
            // Билет для каждого пассажира
            foreach ($passengers as $index => $passenger) {
                $pdfPath = $this->generateSingleTicket(
                    $orderInfo,
                    $ticketInfo,
                    $passenger,
                    $fromCity->title_uk,
                    $toCity->title_uk,
                    $fromStop->title_uk,
                    $toStop->title_uk,
                    $index + 1
                );
                
                if ($pdfPath) {
                    $pdfFiles[] = $pdfPath;
                    Log::channel('payment')->info('Passenger ticket generated', [
                        'passenger_index' => $index + 1,
                        'path' => $pdfPath
                    ]);
                }
            }
        }

        return $pdfFiles;
    }

    /**
     * Генерация одного билета
     */
    private function generateSingleTicket($orderInfo, $ticketInfo, $passenger = null, $fromCity, $toCity, $fromStop, $toStop, $passengerNumber = null)
    {
        Log::channel('payment')->debug('generateSingleTicket called', [
            'order_id' => $orderInfo->id,
            'passenger_number' => $passengerNumber,
            'passenger_name' => $passenger ? ($passenger->name ?? 'N/A') : 'buyer'
        ]);
        
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'orientation' => 'p'
            ]);

            $passengerName = $passenger
                ? $passenger->name . ' ' . $passenger->second_name
                : $orderInfo->client_name . ' ' . $orderInfo->client_surname;

            Log::channel('payment')->debug('Creating ticket for: ' . $passengerName);

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

            Log::channel('payment')->info('PDF created successfully', [
                'path' => $pdfPath,
                'size' => file_exists($pdfPath) ? filesize($pdfPath) : 0
            ]);

            return $pdfPath;
            
        } catch (\Exception $e) {
            Log::channel('payment')->error('generateSingleTicket exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Шаблон билета
     */
    private function getTicketTemplate($orderInfo, $ticketInfo, $passengerName, $fromCity, $toCity, $fromStop, $toStop)
    {
        return '
    <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        table {
            width: 100%;
            padding: 15px;
            border-collapse: collapse;
        }
        img{
        max-width: 100%;
        width: 200px;
        }
        td {
            vertical-align: top;
            padding: 10px;
        }
        .container {
            padding: 0 30px;
            width: 1140px;
        }
        .tiket_section {
            padding: 20px;
            border-bottom: 2px dashed #000;
        }
        table.tiket_bordered {
            padding: 20px;
            border: 2px dashed #000;
            border-radius: 10px;
        }
        .tiket_column.small_info {
            width: 25%;
            text-align: center;
            border-right: 1px solid #000;
            padding-right: 20px;
            margin-right: 20px;
        }
        .tiket_logo img {
            max-width: 100%;
            height: auto;
        }
        .title {
            font-weight: bold;
        }
        .big_title {
            font-size: 18px;
            text-align: center;
        }
        .add_info {
            padding-top: 50px;
            border-top: 1px solid #000;
            text-align: right;
            padding-right: 20px;
        }
        .pass_info-section {
            padding: 20px;
        }
        .pass_info-columns_wrapper {
            display: flex;
            justify-content: space-between;
        }
        .pass_info-column {
            flex: 1;
            padding: 10px;
        }
        .tr_border_top{
            padding-top: 30px;
            border-top: 1px solid #000;
        }
        .qr-code{
        position: relative;
        margin-top: 30px;
        }
        .add_info_title{
        white-space: nowrap;
        }
    </style>
</head>
<body>
<div class="container" >
    <section class="tiket_section">
        <table class="tiket_bordered" style="border-collapse: collapse; width: 100%;">
        <tr>
            <td class="tiket_column small_info" style="width:25%;">
                <div class="tiket_logo"><img src="https://www.maxtransltd.com/public/upload/logos/maxTransLogo.png" alt=""></div>
                <div class="date_title title">Продано/Sales</div>
                <div class="date_info">' . $orderInfo->date . '</div>
                <div class="tiket_id" style="margin-bottom: 30px;">№' . $orderInfo->id . '</div>
                <div class="qr-code" style="margin-top: 30px;"><img src="https://www.maxtransltd.com/public/upload/logos/qr-code.png" alt=""></div>
            </td>
            <td class="tiket_column passanger_data" style="width: 100%;">
                <div class="big_title title" style="text-align: center; width: 100%;">ЕЛЕКТРОННИЙ КВИТОК</div>
                <table>
                    <tr>
                        <td><b>Рейс/Flight</b>
                            <div>' . $ticketInfo->departure_city . ' - ' . $ticketInfo->arrival_city . '</div>
                        </td>
                        <td><b>Відправлення/Departure</b>
                            <div>' . $orderInfo->tour_date . ' ' . substr($ticketInfo->departure_time, 0, 5) . '<br>' . $fromCity . ' ' . $fromStop . '</div>
                        </td>
                        <td><b>Прибуття/Arrival</b>
                            <div>' . $toCity . ' ' . $toStop . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Пасажир/Passenger</b>
                            <div>' . $passengerName . '</div>
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
                        <td>
                        </td>
                        <td><div>Тариф<br>Tariff</div>
                            <div>' . $ticketInfo->price . '</div>
                        </td>
                        <td><div>Страховий збір<br>Insurance fee</div>
                            <div>0.00</div>
                        </td>
                        <td><div>В т.ч. ПДВ<br>Including VAT</div>
                            <div>0.00</div>
                        </td>
                        <td>
                        </td>
                        <td>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Збір/Послуга<br>Fee/Service</b>
                            <div>' . $ticketInfo->price . '</div>
                        </td>
                        <td><b>Проїзд<br>Passage</b>
                            <div>' . $ticketInfo->price . '</div>
                        </td>
                        <td><b>Багаж<br>Luggage</b>
                            <div></div>
                        </td>
                        <td><b>Тип<br>Type</b>
                            <div>ПОВНИЙ</div>
                        </td>
                        <td><b>Знижка<br>Discount</b>
                            <div></div>
                        </td>
                        <td><b>Всього, грн<br>Total, UAH</b>
                            <div>' . $ticketInfo->price . '</div>
                        </td>
                    </tr>
                </table>
                <table>
                    <tr class="tr_border_top" style="padding-top: 30px; border-top: 1px solid;">
                        <td>
                        </td>
                        <td>
                        </td>
                        <td>
                            <div class="add_info_title title">
                            Служба підтримки / Support service
                            </div>
                            <div class="add_info_phone">
                                +38 093 272 11 54
                            </div>
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
            <div class="pass_info_title title">
                До відома пасажирів:
            </div>
            <div class="pass_info-columns_wrapper">
            <div class="pass_info-column">
                <div class="pass_info">1.Після оплати проїзду пасажиру рекомендовано перевірити усі реєстраційні дані, вказані у ваучері бронювання.</div>
                <div class="pass_info">2.Для забезпечення організованої посадки, пасажиру бажано прибути до місця відправлення автобусу </div>
                <div class="pass_info">3.Відправлення автобусу у рейс здійснюється за місцевим часом</div>
                <div class="pass_info">4.Пасажир несе відповідальність за домтримання візового режиму та умов перетину кордону</div>
                <div class="pass_info">5.Для отримання інформації щодо переоформлення або відміни поїздки пасажир може звернутися до офіційних представництв компаніі, або за телефонами Служби підтримки</div>
                <div class="pass_info">6.Оплата поїздки свідчить про згоду пасажира з умовами договору оферти, розміщенного на сайті та в офіційних прдставництвах компанії.</div>
                <div class="pass_info">7.Квиток є дійсним, тільки за умови, якщо прізвище та Імя пасажира відповідають його паспортним даним.</div>
            </div>
            </div>
        </div>
        <div class="pass_info_container">
            <div class="pass_info_title title">
    Умови повернення квитків:
            </div>
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

    /**
     * Отправка email с билетами
     */
    private function sendTicketsEmail($orderInfo, $ticketInfo, $passengers, $pdfFiles)
    {
        Log::channel('payment')->info('sendTicketsEmail called', [
            'client_email' => $orderInfo->client_email,
            'pdf_count' => count($pdfFiles)
        ]);
        
        try {
            $fromStop = DB::table($this->dbPrefix . '_cities')
                ->where('id', $orderInfo->from_stop)
                ->first();

            $toStop = DB::table($this->dbPrefix . '_cities')
                ->where('id', $orderInfo->to_stop)
                ->first();

            $fromCity = DB::table($this->dbPrefix . '_cities')
                ->where('id', $fromStop->section_id)
                ->first();

            $toCity = DB::table($this->dbPrefix . '_cities')
                ->where('id', $toStop->section_id)
                ->first();

            $emailData = [
                'orderInfo' => $orderInfo,
                'ticketInfo' => $ticketInfo,
                'passengers' => $passengers,
                'fromCity' => $fromCity->title_uk,
                'toCity' => $toCity->title_uk,
                'fromStop' => $fromStop->title_uk,
                'toStop' => $toStop->title_uk,
                'totalPrice' => $ticketInfo->price * $orderInfo->passagers,
            ];

            // Отправка клиенту
            Log::channel('payment')->info('Sending email to client', [
                'email' => $orderInfo->client_email
            ]);
            $this->sendEmailToClient($emailData, $pdfFiles);

            // Отправка администратору (если не тест)
            $isTest = stripos($orderInfo->client_name, 'test') !== false ||
                      stripos($orderInfo->client_surname, 'test') !== false;

            Log::channel('payment')->info('Test order check', [
                'is_test' => $isTest,
                'client_name' => $orderInfo->client_name,
                'client_surname' => $orderInfo->client_surname
            ]);

            if (!$isTest) {
                Log::channel('payment')->info('Sending email to admin');
                $this->sendEmailToAdmin($emailData, $pdfFiles);
            } else {
                Log::channel('payment')->info('Skipping admin email (test order)');
            }
            
            Log::channel('payment')->info('All emails sent successfully');
            
        } catch (\Exception $e) {
            Log::channel('payment')->error('sendTicketsEmail exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Отправка email клиенту
     */
    private function sendEmailToClient($data, $pdfFiles)
    {
        $subject = "Ваш квиток";
        $to = $data['orderInfo']->client_email;

        Log::channel('payment')->info('sendEmailToClient', [
            'to' => $to,
            'subject' => $subject
        ]);

        $message = $this->getClientEmailTemplate($data);

        $result = $this->sendEmailWithAttachments($to, $subject, $message, $pdfFiles);
        
        Log::channel('payment')->info('Client email result', [
            'to' => $to,
            'result' => $result ? 'SUCCESS' : 'FAILED'
        ]);
    }

    /**
     * Отправка email администратору
     */
    private function sendEmailToAdmin($data, $pdfFiles)
    {
        $subject = $data['orderInfo']->passagers > 1
            ? "Покупка {$data['orderInfo']->passagers} білетів:"
            : "Покупка білета:";

        $to = "max210183@ukr.net";

        Log::channel('payment')->info('sendEmailToAdmin', [
            'to' => $to,
            'subject' => $subject
        ]);

        $message = $this->getAdminEmailTemplate($data);

        $result = $this->sendEmailWithAttachments($to, $subject, $message, $pdfFiles);
        
        Log::channel('payment')->info('Admin email result', [
            'to' => $to,
            'result' => $result ? 'SUCCESS' : 'FAILED'
        ]);
    }

    /**
     * Отправка email с вложениями
     */
    private function sendEmailWithAttachments($to, $subject, $message, $attachments)
    {
        Log::channel('payment')->info('sendEmailWithAttachments', [
            'to' => $to,
            'subject' => $subject,
            'attachments_count' => count($attachments),
            'message_length' => strlen($message)
        ]);
        
        try {
            $separator = md5(time());
            $eol = "\r\n";

            $fromName = "Max Trans LTD";
            $fromEmail = "info@maxtransltd.com";

            $headers = "From: $fromName <$fromEmail>" . $eol;
            $headers .= "MIME-Version: 1.0" . $eol;
            $headers .= "Content-Type: multipart/mixed; boundary=\"$separator\"" . $eol;

            $body  = "--" . $separator . $eol;
            $body .= "Content-Type: text/html; charset=\"utf-8\"" . $eol;
            $body .= "Content-Transfer-Encoding: 7bit" . $eol . $eol;
            $body .= $message . $eol;

            // Добавляем вложения
            foreach ($attachments as $file) {
                if (file_exists($file)) {
                    $fileName = basename($file);
                    $fileSize = filesize($file);
                    $fileContent = chunk_split(base64_encode(file_get_contents($file)));
                    
                    Log::channel('payment')->debug('Adding attachment', [
                        'file' => $fileName,
                        'size' => $fileSize
                    ]);
                    
                    $body .= "--" . $separator . $eol;
                    $body .= "Content-Type: application/pdf; name=\"$fileName\"" . $eol;
                    $body .= "Content-Transfer-Encoding: base64" . $eol;
                    $body .= "Content-Disposition: attachment; filename=\"$fileName\"" . $eol . $eol;
                    $body .= $fileContent . $eol;
                } else {
                    Log::channel('payment')->warning('Attachment file not found', [
                        'file' => $file
                    ]);
                }
            }

            $body .= "--" . $separator . "--";

            // Отправка
            $result = mail($to, $subject, $body, $headers);

            Log::channel('payment')->info('mail() function result', [
                'to' => $to,
                'result' => $result,
                'body_length' => strlen($body)
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Log::channel('payment')->error('sendEmailWithAttachments exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Шаблон email для клиента
     */
    private function getClientEmailTemplate($data)
    {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $html = '
        <html>
        <head>
            <title>Ваш квиток</title>
            <style>
                .email-content {
                    border-left: 4px solid #40A6FF;
                    padding-left: 10px;
                }
                .email-content table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .email-content td {
                    padding: 5px 10px;
                }
                .email-titles {
                    font-weight: bold;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    max-width: 150px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <a href="https://www.maxtransltd.com">
                <img src="' . $imagePath . '" alt="MaxTrans LTD" class="logo">
                </a>
            </div>
            <p>Ваш квиток:</p>
            <div class="email-content">
                <table>';

        if ($data['passengers']->count() > 1) {
            $i = 1;
            foreach ($data['passengers'] as $passenger) {
                $html .= "
                    <tr>
                        <td class='email-titles'>$i</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>{$data['orderInfo']->id} $i/{$data['passengers']->count()}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>{$data['ticketInfo']->departure_city} - {$data['ticketInfo']->arrival_city}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>{$data['orderInfo']->tour_date} " . substr($data['ticketInfo']->departure_time, 0, 5) . "</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>{$data['fromCity']} {$data['fromStop']}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>{$data['toCity']} {$data['toStop']}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>{$passenger->name} {$passenger->second_name}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>{$data['orderInfo']->client_phone}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>{$data['orderInfo']->client_email}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна квитка</td>
                        <td>{$data['ticketInfo']->price}</td>
                    </tr>";
                $i++;
            }
        } else {
            $html .= "
                    <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>{$data['orderInfo']->id}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>{$data['ticketInfo']->departure_city} - {$data['ticketInfo']->arrival_city}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>{$data['orderInfo']->tour_date} " . substr($data['ticketInfo']->departure_time, 0, 5) . "</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>{$data['fromCity']} {$data['fromStop']}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>{$data['toCity']} {$data['toStop']}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>{$data['orderInfo']->client_name} {$data['orderInfo']->client_surname}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>{$data['orderInfo']->client_phone}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>{$data['orderInfo']->client_email}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна квитка</td>
                        <td>{$data['ticketInfo']->price}</td>
                    </tr>";
        }

        $html .= "
                    <tr>
                        <td class='email-titles'>Сумма замовлення</td>
                        <td>{$data['totalPrice']}</td>
                    </tr>
                </table>
                <p>У вартість квитка включено перевезення одного місця багажу вагою до 25 кг. За кожну додаткову одиницю багажу передбачена доплата в розмірі 10% від вартості квитка.</p>
                <p>Перевізник: Maks Trans LTD</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Шаблон email для администратора
     */
    private function getAdminEmailTemplate($data)
    {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $title = $data['orderInfo']->passagers > 1
            ? "Покупка {$data['orderInfo']->passagers} білетів:"
            : "Покупка білета:";

        $html = '
        <html>
        <head>
            <title>' . $title . '</title>
            <style>
                .email-content {
                    border-left: 4px solid #40A6FF;
                    padding-left: 10px;
                }
                .email-content table {
                    width: 100%;
                    border-collapse: collapse;
                }
                .email-content td {
                    padding: 5px 10px;
                }
                .email-titles {
                    font-weight: bold;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
                .logo {
                    max-width: 150px;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <a href="https://www.maxtransltd.com">
                <img src="' . $imagePath . '" alt="MaxTrans LTD" class="logo">
                </a>
            </div>
            <p>' . $title . '</p>
            <div class="email-content">
                <table>
                <tr>
                    <td class="email-titles">Покупець</td>
                    <td>' . $data['orderInfo']->client_name . ' ' . $data['orderInfo']->client_surname . '</td>
                </tr>
                <tr>
                    <td class="email-titles">Пасажирів</td>
                    <td>' . $data['orderInfo']->passagers . '</td>
                </tr>';

        if ($data['passengers']->count() > 1) {
            $i = 1;
            foreach ($data['passengers'] as $passenger) {
                $html .= "
                    <tr>
                        <td class='email-titles'>$i</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>{$data['orderInfo']->id} $i/{$data['passengers']->count()}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>{$data['ticketInfo']->departure_city} - {$data['ticketInfo']->arrival_city}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>{$data['orderInfo']->tour_date} " . substr($data['ticketInfo']->departure_time, 0, 5) . "</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>{$data['fromCity']} {$data['fromStop']}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>{$data['toCity']} {$data['toStop']}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>{$passenger->name} {$passenger->second_name}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>{$data['orderInfo']->client_phone}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>{$data['orderInfo']->client_email}</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна</td>
                        <td>{$data['ticketInfo']->price}</td>
                    </tr>";
                $i++;
            }
        } else {
            $html .= "
                <tr>
                    <td class='email-titles'>Квиток</td>
                    <td>{$data['orderInfo']->id}</td>
                </tr>
                <tr>
                    <td class='email-titles'>Рейс</td>
                    <td>{$data['ticketInfo']->departure_city} - {$data['ticketInfo']->arrival_city}</td>
                </tr>
                <tr>
                    <td class='email-titles'>Виїзд</td>
                    <td>{$data['orderInfo']->tour_date} " . substr($data['ticketInfo']->departure_time, 0, 5) . "</td>
                </tr>
                <tr>
                    <td class='email-titles'>Виїзд</td>
                    <td>{$data['fromCity']} {$data['fromStop']}</td>
                </tr>
                <tr>
                    <td class='email-titles'>Прибуття</td>
                    <td>{$data['toCity']} {$data['toStop']}</td>
                </tr>
                <tr>
                    <td class='email-titles'>Телефон</td>
                    <td>{$data['orderInfo']->client_phone}</td>
                </tr>
                <tr>
                    <td class='email-titles'>E-mail</td>
                    <td>{$data['orderInfo']->client_email}</td>
                </tr>
                <tr>
                    <td class='email-titles'>Ціна</td>
                    <td>{$data['ticketInfo']->price}</td>
                </tr>";
        }

        $html .= "
                <tr>
                    <td class='email-titles'>Сумма замовлення</td>
                    <td>{$data['totalPrice']}</td>
                </tr>
                <tr>
                    <td class='email-titles'>Спосіб оплати</td>
                    <td>Онлайн LiqPay</td>
                </tr>
            </table>
            <p>Перевізник: Maks Trans LTD</p>
        </div>
    </body>
    </html>";

        return $html;
    }
}
