<?php
include(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/config.php");
include(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/includes.php");

require_once('private/LiqPay.php');
require_once('private/payment_keys.php');

function logToFile($message) {
    $logFile = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/log.txt';
    file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
}

// Получение данных от LiqPay
$data = $_POST['data'] ?? null;
$signature = $_POST['signature'] ?? null;


if ($data && $signature) {
    // Проверка подписи
    $liqpay = new LiqPay($public_key, $private_key);
    $valid_signature = $liqpay->str_to_sign($private_key . $data . $private_key);


    if ($signature === $valid_signature) {
        // Декодирование данных
        $decoded_data = base64_decode($data);
        $json_data = json_decode($decoded_data, true);



        if ($json_data && $json_data['status'] === 'success') {
            // Получение информации о заказе
            $order_id = $json_data['order_id'];
            $order_id_safe = addslashes($order_id); // Защита от SQL-инъекций
            $tour_info = $Db->getOne("SELECT tour_id AS tour_id, tour_date AS tour_date, passagers AS passagers FROM `" .  DB_PREFIX . "_orders`WHERE uniqId = '$order_id_safe'");
            $orderInfo = $Db->getOne("SELECT id, date AS order_date, from_stop, to_stop, tour_date, passagers, client_name, client_surname, client_email, client_phone FROM `" .  DB_PREFIX . "_orders`WHERE uniqid = '$order_id_safe'");
            logToFile("Order info: " . print_r($orderInfo, true));
            logToFile("Tour info: " . print_r($tour_info, true));

            $ticketInfo = $Db->getOne("SELECT
                    from_stop.departure_time,
                    from_city.title_uk AS departure_station,
                    departure_city.title_uk AS departure_city,
                    to_stop.arrival_time,
                    to_city.title_uk AS arrival_station,
                    arrival_city.title_uk AS arrival_city,
                    bus.title_uk AS bus,
                    prices.price
                FROM `" .  DB_PREFIX . "_tours_stops`AS from_stop
                JOIN `" .  DB_PREFIX . "_cities`AS from_city ON from_stop.stop_id = from_city.id
                JOIN `" .  DB_PREFIX . "_tours`AS tours ON from_stop.tour_id = tours.id
                JOIN `" .  DB_PREFIX . "_cities`AS departure_city ON departure_city.id = tours.departure
                JOIN `" .  DB_PREFIX . "_tours_stops`AS to_stop ON from_stop.tour_id = to_stop.tour_id
                JOIN `" .  DB_PREFIX . "_cities`AS to_city ON to_stop.stop_id = to_city.id
                JOIN `" .  DB_PREFIX . "_cities`AS arrival_city ON arrival_city.id = tours.arrival
                JOIN `" .  DB_PREFIX . "_buses`AS bus ON tours.bus = bus.id
                JOIN `" .  DB_PREFIX . "_tours_stops_prices`AS prices ON
                        prices.tour_id = from_stop.tour_id AND
                        prices.from_stop = from_stop.stop_id AND
                        prices.to_stop = to_stop.stop_id
                WHERE from_stop.tour_id = '" . addslashes($tour_info['tour_id']) . "'
                AND from_stop.stop_id = '" . addslashes($orderInfo['from_stop']) . "'
                AND to_stop.stop_id = '" . addslashes($orderInfo['to_stop']) . "'");
            logToFile("Tour info: " . print_r($ticketInfo, true));
            $from_stopId = $orderInfo['from_stop'];
            $to_stopId = $orderInfo['to_stop'];

            $client_stops= $Db->getAll("SELECT title_uk AS station_title, section_id AS city FROM `" .  DB_PREFIX . "_cities`WHERE id IN ('$from_stopId', '$to_stopId') ORDER BY FIELD(id, '".$from_stopId."', '".$to_stopId."') ");

            $client_cities = $Db->getAll("
                SELECT title_uk AS city_title
                FROM `" .  DB_PREFIX . "_cities`WHERE id IN ('".$client_stops['0']['city']."', '".$client_stops['1']['city']."')
                ORDER BY FIELD(id, '".$client_stops['0']['city']."', '".$client_stops['1']['city']."')
            ");

            $from_stop= $client_stops['0']['station_title'];
            $to_stop=$client_stops['1']['station_title'];
            $from_city= $client_cities['0']['city_title'];
            $to_city= $client_cities['1']['city_title'];
            logToFile("Tour info: " . print_r($from_stop, true));
            logToFile("Tour info: " . print_r($to_stop, true));
            logToFile("Tour info: " . print_r($from_city, true));
            logToFile("Tour info: " . print_r($to_city, true));

            if ($tour_info) {
                // Обновление статуса оплаты и продаж
                $Db->query("UPDATE `" .  DB_PREFIX . "_orders`SET payment_status = 2 WHERE uniqId = '$order_id_safe'");
                $Db->query("UPDATE `" .  DB_PREFIX . "_tours_sales`SET tickets_buy = tickets_buy + " . (int)$tour_info['passagers'] . " WHERE tour_id ='" . $tour_info['tour_id'] . "' AND tour_date = '" . $tour_info['tour_date'] . "'");


                $getPassangers = $Db->getAll("SELECT name, second_name, patronymic FROM `" .  DB_PREFIX . "_orders_passangers`WHERE order_id = '".$order_id_safe."'");
                // Отправка email с PDF
                generateAndSendPdf($paymentData, $orderInfo, $ticketInfo, $tourInfo, $from_city, $to_city, $from_stop, $to_stop, $getPassangers);

                echo  'ok';
                http_response_code(200); // OK
            } else {
                echo  'Order not found';
                http_response_code(404); // Not Found
            }
        } else {
            echo  'Invalid payment status';
            http_response_code(400); // Bad Request
        }
    } else {
        echo  'Invalid signature';
        http_response_code(400); // Bad Request
    }
} else {
    echo  'No data or signature !!!';
    http_response_code(400); // Bad Request
}

// Генерация и отправка PDF
function generateAndSendPdf($paymentData, $orderInfo, $ticketInfo, $tourInfo, $from_city, $to_city, $from_stop, $to_stop, $getPassangers) {
    require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/libs/mpdf/autoload.php';

    $passengers = $getPassangers;
    logToFile($passengers);


    $order_id = $paymentData['order_id'];
    $total_price = $paymentData['amount'];

    $passenger_pdfs = [];

    if ($orderInfo['passagers'] === '1') {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'orientation' => 'p'
        ]);
        // Генерация PDF
        $html = '
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
            border-collapse: collapse; /* Для объединения границ ячеек */
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
            width: 25%; /* Ширина для small_info */
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
                <div class="date_info">' . $orderInfo['order_date'] . '</div>
                <div class="tiket_id" style="margin-bottom: 30px;">№' . $orderInfo['id'] . '</div>
                <div class="qr-code" style="margin-top: 30px;"><img src="https://www.maxtransltd.com/public/upload/logos/qr-code.png" alt=""></div>
            </td>
            <td class="tiket_column passanger_data" style="width: 100%;">
                <div class="big_title title" style="text-align: center; width: 100%;">ЕЛЕКТРОННИЙ КВИТОК</div>
                <table>
                    <tr>
                        <td><b>Рейс/Flight</b>
                            <div>' . $ticketInfo['departure_city'] . ' - ' . $ticketInfo['arrival_city'] . '</div>
                        </td>
                        <td><b>Відправлення/Departure</b>
                            <div>' . $orderInfo['tour_date'] . ' ' . $ticketInfo['departure_time'] . '<br>' . $from_city . ' ' . $from_stop . '</div>
                        </td>
                        <td><b>Прибуття/Arrival</b>
                            <div>' . $to_city . ' ' . $to_stop . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Пасажир/Passenger</b>
                            <div>' . $orderInfo['client_name'] . ' ' . $orderInfo['client_surname'] . '</div>
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
                            <div>' . $ticketInfo['price'] . '</div>
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
                            <div>' . $ticketInfo['price'] . '</div>
                        </td>
                        <td><b>Проїзд<br>Passage</b>
                            <div>' . $ticketInfo['price'] . '</div>
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
                            <div>' . $ticketInfo['price'] . '</div>
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
</html>
    ';

        logToFile('GOT 1st ONE');

        $mpdf->WriteHTML($html);
        $pdfFilePath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/tickets_pdf/ticket_' . $orderInfo['id'] . '.pdf';
        $mpdf->Output($pdfFilePath, 'F');
        $passenger_pdfs[] = $pdfFilePath;

    } else {



        foreach ($getPassangers as $index => $passenger) {

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'orientation' => 'p'
            ]);
            logToFile('Пассажир ' . ($index + 1) . ': ' . $passenger['name'] . ' ' . $passenger['second_name'] . '<br>');

            $passengerHtml = '
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
            border-collapse: collapse; /* Для объединения границ ячеек */
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
            width: 25%; /* Ширина для small_info */
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
                <div class="date_info">' . $orderInfo['order_date'] . '</div>
                <div class="tiket_id" style="margin-bottom: 30px;">№' . $orderInfo['id'] . '</div>
                <div class="qr-code" style="margin-top: 30px;"><img src="https://www.maxtransltd.com/public/upload/logos/qr-code.png" alt=""></div>
            </td>
            <td class="tiket_column passanger_data" style="width: 100%;">
                <div class="big_title title" style="text-align: center; width: 100%;">ЕЛЕКТРОННИЙ КВИТОК</div>
                <table>
                    <tr>
                        <td><b>Рейс/Flight</b>
                            <div>' . $ticketInfo['departure_city'] . ' - ' . $ticketInfo['arrival_city'] . '</div>
                        </td>
                        <td><b>Відправлення/Departure</b>
                            <div>' . $orderInfo['tour_date'] . ' ' . $ticketInfo['departure_time'] . '<br>' . $from_city . ' ' . $from_stop . '</div>
                        </td>
                        <td><b>Прибуття/Arrival</b>
                            <div>' . $to_city . ' ' . $to_stop . '</div>
                        </td>
                    </tr>
                    <tr>
                        <td><b>Пасажир/Passenger</b>
                                    <div>' . $passenger['name'] . ' ' . $passenger['second_name'] . '</div>
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
                            <div>' . $ticketInfo['price'] . '</div>
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
                            <div>' . $ticketInfo['price'] . '</div>
                        </td>
                        <td><b>Проїзд<br>Passage</b>
                            <div>' . $ticketInfo['price'] . '</div>
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
                            <div>' . $ticketInfo['price'] . '</div>
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
</html>
    ';


            $mpdf->WriteHTML($passengerHtml);
            $passenger_pdf_path = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/tickets_pdf/ticket_' . $orderInfo['id'] . '_passenger_' . ($index + 1) . '.pdf';
            $mpdf->Output($passenger_pdf_path, 'F');
            logToFile('Ticket added for passenger ' . $passenger['name']);
            $passenger_pdfs[] = $passenger_pdf_path;
        }
    }

    $pdf_files = array_merge([$pdfFilePath], $passenger_pdfs);


    logToFile('Tickets outputed');


    sendEmailWithAttachment($pdf_files, $orderInfo, $ticketInfo, $from_city, $from_stop, $to_city, $to_stop, $getPassangers);


    return $pdf_files; // Возвращаем путь к сохраненному файлу
}

function sendEmailWithAttachment($pdf_files, $orderInfo, $ticketInfo, $from_city, $from_stop, $to_city, $to_stop, $passangers = []) {
    $departureCity = $ticketInfo['departure_city'];
    $departureTime = substr($ticketInfo['departure_time'], 0, 5);
    $arrivalCity = $ticketInfo['arrival_city'];
    $date = $orderInfo['tour_date'];
    $email = $orderInfo['client_email'];
    $name = $orderInfo['client_name'];
    $familyName = $orderInfo['client_surname'];
    $phone = $orderInfo['client_phone'];
    $price = $ticketInfo['price'];
    $ticketId = $orderInfo['id'];
    $passagersCount = $orderInfo['passagers'];
    $totalPrice = ($price * $passagersCount);
    $message1 = "";
    $test = strtolower($name) == 'test' || strtolower($familyName) == 'test';

    if (!empty($passangers) && count($passangers) > 1) {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $i = 1;
        $countPassangers = count($passangers);
        $message1 .= "<html>
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
                .img_logo img{
                    pointer-events: none;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class='header' oncontextmenu='return false;'>
                <a href='https://www.maxtransltd.com'>
                <img src='$imagePath' alt='MaxTrans LTD' class='logo'>
                </a>
            </div>
            <p>Ваш квиток:</p>
            <div class='email-content'>
                <table>";
        foreach ($passangers as $passenger) {
            $pasName = $passenger['name'];
            $pasSecondName = $passenger['second_name'];
            $test = strtolower($pasName) == 'test' || strtolower($pasSecondName) == 'test' || $test;
            $passengerName = $pasName . ' ' . $pasSecondName;
            $message1 .= "
                    <tr>
                        <td class='email-titles'>$i</td>
                        <td></td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>$ticketId $i/$countPassangers</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$date $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$from_city $from_stop </td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>$to_city $to_stop</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>$passengerName</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>$phone</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>$email</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна квитка</td>
                        <td>$price</td>
                    </tr>
            ";
            $i++;
        }

        $message1 .= "
        <tr>
                        <td class='email-titles'>Сумма замовлення</td>
                        <td>$totalPrice</td>
                    </tr>
                    </table>
                <p>У вартість квитка включено перевезення одного місця багажу вагою до 25 кг. За кожну додаткову одиницю багажу передбачена доплата в розмірі 10% від вартості квитка.</p>
                <p>Перевізник: Maks Trans LTD</p>
            </div>
        </body>
        </html>";
    } else {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        // Сообщение для клиента
        $message1 = "
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
                .img_logo img{
                    pointer-events: none;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class='header' oncontextmenu='return false;'>
                <a href='https://www.maxtransltd.com'>
                <img src='$imagePath' alt='MaxTrans LTD' class='logo'>
                </a>
            </div>
            <p>Ваш квиток:</p>
            <div class='email-content'>
                <table>
                    <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>$ticketId</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$date $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$from_city $from_stop </td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>$to_city $to_stop</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>$name $familyName</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>$phone</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>$email</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна квитка</td>
                        <td>$price</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Сумма замовлення</td>
                        <td>$totalPrice</td>
                    </tr>
                </table>
                <p>У вартість квитка включено перевезення одного місця багажу вагою до 25 кг. За кожну додаткову одиницю багажу передбачена доплата в розмірі 10% від вартості квитка.</p>
                <p>Перевізник: Maks Trans LTD</p>
            </div>
        </body>
        </html>
    ";

    }



    if ($passagersCount > 1) {
        $title = "Покупка $passagersCount білетів:";
    } else {
        $title = "Покупка білета:";
    }



    // Сообщение для администратора
    $message2 = '';
    if (!empty($passangers) && count($passangers) > 1) {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $message2 .= "<html>
        <head>
            <title>$title</title>
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
                .img_logo img{
                    pointer-events: none;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class='header' oncontextmenu='return false;'>
                <a href='https://www.maxtransltd.com'>
                <img src='$imagePath' alt='MaxTrans LTD' class='logo'>
                </a>
            </div>
            <p>$title</p>
            <div class='email-content'>
                <table>
                <tr>
                        <td class='email-titles'>Покупець</td>
                        <td>$name $familyName</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажирів</td>
                        <td>$passagersCount</td>
                    </tr>";
        $i = 1;
        $countPassangers = count($passangers);
        foreach ($passangers as $passenger) {
            $pasName = $passenger['name'];
            $pasSecondName = $passenger['second_name'];
            $passengerName = $pasName . ' ' . $pasSecondName;
            $message2 .= "
                    <tr>
                        <td class='email-titles'>$i</td>
                        <td></td>
                    </tr>
            <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>$ticketId $i/$countPassangers</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$date $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$from_city $from_stop </td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>$to_city $to_stop</td>
                    </tr>
                     <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>$passengerName</td>
                    </tr>

                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>$phone</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>$email</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна</td>
                        <td>$price</td>
                    </tr>";
        }
        $message2 .= "
        <tr>
                        <td class='email-titles'>Сумма замовлення</td>
                        <td>$totalPrice</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Спосіб оплати</td>
                        <td>Онлайн LiqPay</td>
                    </tr>
                </table>
                <p>Перевізник: Maks Trans LTD</p>
            </div>
        </body>
        </html>
        ";
    } else {
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $message2 = "
        <html>
        <head>
            <title>$title</title>
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
                .img_logo img{
                    pointer-events: none;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class='header' oncontextmenu='return false;'>
                <a href='https://www.maxtransltd.com'>
                <img src='$imagePath' alt='MaxTrans LTD' class='logo'>
                </a>
            </div>
            <p>$title</p>
            <div class='email-content'>
                <table>
                    <tr>
                        <td class='email-titles'>Квиток</td>
                        <td>$ticketId</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$date $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Виїзд</td>
                        <td>$from_city $from_stop </td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Прибуття</td>
                        <td>$to_city $to_stop</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Покупець</td>
                        <td>$name $familyName</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>$phone</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>$email</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Ціна</td>
                        <td>$price</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Сумма замовлення</td>
                        <td>$totalPrice</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Спосіб оплати</td>
                        <td>Онлайн LiqPay</td>
                    </tr>
                </table>
                <p>Перевізник: Maks Trans LTD</p>
            </div>
        </body>
        </html>
    ";
    }



    $separator = md5(time());
    $eol = "\r\n";

    $fromName = "Max Trans LTD";
    $fromEmail = "info@maxtransltd.com";

    $headers = "From: $fromName <$fromEmail>" . $eol;
    $headers .= "MIME-Version: 1.0" . $eol;
    $headers .= "Content-Type: multipart/mixed; boundary=\"$separator\"" . $eol;

// Настройка параметров SMTP
    ini_set('SMTP', 'mail.adm.tools');
    ini_set('smtp_port', '465'); // Порт для SSL
    ini_set('sendmail_from', 'info@maxtransltd.com');
    ini_set('sendmail_path', '"/usr/sbin/sendmail -t -i"');

// Функция отправки письма
    function sendMail($to, $subject, $message, $headers, $pdf_files, $separator, $eol) {
        $body  = "--" . $separator . $eol;
        $body .= "Content-Type: text/html; charset=\"utf-8\"" . $eol;
        $body .= "Content-Transfer-Encoding: 7bit" . $eol . $eol;
        $body .= $message . $eol;

        // Вложение
        $body .= "--" . $separator . $eol;
        $body .= "Content-Type: application/pdf; name=\"ticket.pdf\"" . $eol;
        $body .= "Content-Transfer-Encoding: base64" . $eol;
        $body .= "Content-Disposition: attachment; filename=\"ticket.pdf\"" . $eol . $eol;
        $body .= chunk_split(base64_encode(file_get_contents($pdf_files))) . $eol;
        foreach ($pdf_files as $file) {
            if (file_exists($file)) {
                $fileName = basename($file);
                $fileContent = chunk_split(base64_encode(file_get_contents($file)));
                $body .= "--" . $separator . $eol;
                $body .= "Content-Type: application/pdf; name=\"$fileName\"" . $eol;
                $body .= "Content-Transfer-Encoding: base64" . $eol;
                $body .= "Content-Disposition: attachment; filename=\"$fileName\"" . $eol . $eol;
                $body .= $fileContent . $eol;
            }
        }

        $body .= "--" . $separator . "--";



        // Отправка письма
        $mail_sent = mail($to, $subject, $body, $headers);

        if ($mail_sent) {
            return true;
        } else {
            return false;
        }
    }

    // Отправка письма клиенту
    $clientEmail = $orderInfo['client_email'];
    $clientSubject = "Ваш квиток";
    sendMail($clientEmail, $clientSubject, $message1, $headers, $pdf_files, $separator, $eol);

    logToFile('Tickets sent to' . print_r($clientEmail, true));

    // Отправка письма администратору $adminEmail = "max210183@ukr.net";
    if (!$test) {
        $adminEmail = "max210183@ukr.net";
        //$adminEmail = $clientEmail;
        $adminSubject = $title;
        sendMail($adminEmail, $adminSubject, $message2, $headers, $pdf_files, $separator, $eol);
    }


    logToFile('Tickets sent to' . print_r($adminEmail, true));
}
?>

