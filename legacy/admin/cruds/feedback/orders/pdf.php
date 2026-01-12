<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if ($Admin->CheckPermission($_params['access'])) {
    $getRouteDetails = $Db->getAll("SELECT * FROM `" .  DB_PREFIX . "_orders`WHERE tour_id = '".$id."' AND tour_date = '".$_GET['date']."' ");
    if ($getRouteDetails){
        $mainInfo = $Db->getOne("SELECT
                                    departure_city.title_uk AS departure_city,
                                    arrival_city.title_uk AS arrival_city,
                                    bus.title_uk AS bus_title
                                    FROM `" . DB_PREFIX . "_tours` t
                                    LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = t.departure
                                    LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = t.arrival
                                    LEFT JOIN `" . DB_PREFIX . "_buses` bus ON bus.id = t.bus
                                    WHERE t.id = '".$id."' ");
        $departureDetails = $Db->getOne("SELECT departure_station.title_uk AS departure_station,departure_time FROM `" .  DB_PREFIX . "_tours_stops`ts
         LEFT JOIN `" .  DB_PREFIX . "_cities`departure_station ON departure_station.id = ts.stop_id
          WHERE ts.tour_id = '".$id."' ORDER BY ts.id ASC ");

        $arrivalDetails = $Db->getOne("SELECT arrival_station.title_uk AS arrival_station,arrival_time FROM `" .  DB_PREFIX . "_tours_stops`ts
         LEFT JOIN `" .  DB_PREFIX . "_cities`arrival_station ON arrival_station.id = ts.stop_id
          WHERE ts.tour_id = '".$id."' ORDER BY ts.id DESC ");
        $pdfMarkup = "";
        require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/libs/mpdf/autoload.php';
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'orientation' => 'L'
        ]);

        $passangers = "";
        $getPassengers = $Db->getAll("SELECT
                            client.name,
                            client.second_name,
                            client.patronymic,
                            client.phone,
                            client.email,
                            client.id AS client_id,
                            o.date,
                            o.passagers,
                            o.id,
                            o.ticket_return,
                            o.return_date,
                            o.return_payment_type,
                            o.from_stop,
                            o.to_stop,
                            o.client_name,
                            o.client_email,
                            o.client_phone,
                            o.payment_status,
                            return_reason.title_".$Admin->lang." AS return_reason,
                            departure_city.title_".$Admin->lang." AS departure_city,
                            departure_station.title_".$Admin->lang." AS departure_station,
                            arrival_city.title_".$Admin->lang." AS arrival_city,
                            arrival_station.title_".$Admin->lang." AS arrival_station
                            FROM `" .  DB_PREFIX . "_orders`o
                            LEFT JOIN `" .  DB_PREFIX . "_clients`client ON client.id = o.client_id
                            LEFT JOIN `" .  DB_PREFIX . "_cities`departure_station ON departure_station.id = o.from_stop
                            LEFT JOIN `" .  DB_PREFIX . "_cities`departure_city ON departure_city.id = departure_station.section_id
                            LEFT JOIN `" .  DB_PREFIX . "_cities`arrival_station ON arrival_station.id = o.to_stop
                            LEFT JOIN `" .  DB_PREFIX . "_cities`arrival_city ON arrival_city.id = arrival_station.section_id
                            LEFT JOIN `" .  DB_PREFIX . "_return_reasons`return_reason ON return_reason.id = o.return_reason
                            WHERE tour_id = '".$id."'
                            AND tour_date = '".$_GET['date']."'
                            ORDER BY o.date DESC");
        $i = 1;
        foreach ($getPassengers AS $k=>$passenger){
            $price = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE tour_id = '".$id."' AND from_stop = '".$passenger['from_stop']."' AND to_stop = '".$passenger['to_stop']."' ");
            $paymentStatus = '';
            switch ($passenger['payment_status']) {
                case 1:
                    $paymentStatus = 'Наличные';
                    break;
                case 2:
                    $paymentStatus = 'Оплачено';
                    break;
                case 3:
                    $paymentStatus = 'Не оплачено';
                    break;
                default:
                    $paymentStatus = 'Неизвестный статус';
            }
            $passangers .= '<tr>
                                <td>'.$i.'</td>
                                <td>'.$passenger['departure_city'].' '.$passenger['departure_station'].'</td>
                                <td>'.$passenger['arrival_city'].' '.$passenger['arrival_station'].'</td>
                                <td>'.$passenger['client_name'].' '.$passenger['second_name'].' '.$passenger['patronymic'].'</td>
                                <td>б/н</td>
                                <td>'.$passenger['client_phone'].'</td>
                                <td>Сайт MaxTrans</td>
                                <td>'.$paymentStatus.'</td>
                                <td>'.$price['price'].'</td>
                                <td>UAH</td>
                                <td></td>
                            </tr>';
        $i++;
        }

        $pdfMarkup .= '<h1 style="text-align: center">Відомість відправлення пасажирів</h1>';
        $pdfMarkup .= '<h2 style="text-align: center">Вид '.date('d.m.Y H:i',time()).'</h2>';
        $pdfMarkup .= '<table style="width: 100%;">
                            <tr>
                                <td>
                                    <table>
                                    <tr>
                                        <td>
                                            <b>Рейс</b>
                                        </td>
                                        <td>'.$mainInfo['departure_city'].' '.$departureDetails['departure_station'].' - '.$mainInfo['arrival_city'].' '.$arrivalDetails['arrival_station'].'</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b>Дата рейсу</b>
                                        </td>
                                        <td>'.date('d.m.Y',strtotime($_GET['date'])).'</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b>Відправлення</b>
                                        </td>
                                        <td>'.date('H:i',strtotime($departureDetails['departure_time'])).'</td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <b>Перевізник</b>
                                        </td>
                                        <td>ДЖАГАР\'ЯНЦ М.А. ФОП</td>
                                    </tr>
                                    </table>
                                </td>
                                <td>
                                    <table>
                                        <tr>
                                            <td>
                                                <b>Автобус</b>
                                            </td>
                                            <td>'.$mainInfo['bus_title'].'</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>';

        $pdfMarkup .= '<table style="width: 100%;border-collapse: collapse" border="1">
                            <thead>
                                <tr style="background-color: gray">
                                    <th>№ з/п</th>
                                    <th>Від пункту</th>
                                    <th>До пункту</th>
                                    <th>Прізвище та ім\'я пасажира</th>
                                    <th>Номер місця</th>
                                    <th>Контактний телефон</th>
                                    <th>Продавець</th>
                                    <th>Статус оплаты</th>
                                    <th>Сума</th>
                                    <th>Валюта</th>
                                    <th>Примітка</th>
                                </tr>
                                <tr style="background-color: gray">
                                    <th>1</th>
                                    <th>2</th>
                                    <th>3</th>
                                    <th>4</th>
                                    <th>5</th>
                                    <th>6</th>
                                    <th>7</th>
                                    <th>8</th>
                                    <th>9</th>
                                    <th>10</th>
                                    <th>11</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="12"><b>Пассажири без пересадки - 1 чол.</b></td>
                                </tr>
                                '.$passangers.'
                            </tbody>
                        </table>';

        $mpdf->WriteHTML($pdfMarkup);
        $mpdf->Output('Ведомость.pdf','D');
    }
    ?>
<?php } ?>
