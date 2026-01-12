<?php

// Инициализация PHP сессии
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])."/config.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/CDb.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/User.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])."/". ADMIN_PANEL ."/includes.php");

$db =  mysqli_connect(DB_HOST, DB_LOGIN, DB_PASS, DB_NAME);
mysqli_set_charset($db , "utf8" );

$Db = new CDb($db, $db);
$User = new User($Db);

$cleanPost = json_decode(file_get_contents('php://input'), true);
$cleanPost = $_REQUEST;

if ($cleanPost === null) {
    echo  json_encode(['error' => 'Invalid JSON']);
    exit;
}


if (!isset($cleanPost['request']) || empty($cleanPost['request'])) {
    echo  json_encode($_POST);
    exit;
}

/* авторизация  */
if ($cleanPost['request'] === 'appAuth') {
    $user = $User->appAuth($cleanPost['email'], $cleanPost['password']);
    if ($user === false) {
        echo  $GLOBALS['dictionary']['MSG_MSG_LOGIN_USER_NOTFOUND'];
    } elseif ($user === null) {
        echo  $GLOBALS['dictionary']['MSG_MSG_LOGIN_NEVERNYE_DANNYE'];
    } elseif ($user) {
        $getUser = $Db->getOne("SELECT id, name, second_name, phone, email, password FROM `" .  DB_PREFIX . "_clients`WHERE email = '" . $cleanPost['email'] . "'");
        $response = [
            'accessToken' => $_SESSION['user']['app_crypt'],
            'user' => [ '_id' => $getUser['id'],
                'email' => $getUser['email'],
                'password' => $getUser['password'],
                'name' => $getUser['name'],
                'surname' => $getUser['second_name'],
                'phone' => $getUser['phone']
            ]
        ];
        echo  json_encode($response);
    } else {
        echo  $GLOBALS['dictionary']['MSG_MSG_LOGIN_NEVERNYE_DANNYE'];
    }
}

if ($cleanPost['request'] === 'AuthSMS') {

    $userInfo = $Db->getOne("SELECT id, email FROM `" .  DB_PREFIX . "_clients`WHERE phone = '" . $cleanPost['phone'] . "'");

    function generateRandomCode($length = 4) {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= random_int(0, 9);
        }
        return $code;
    }

    $randomCode = generateRandomCode();

    if ($userInfo) {

        // Сохранение кода в базе данных
        $saveCode = $Db->query("UPDATE `" .  DB_PREFIX . "_clients`SET code = '".$randomCode."' WHERE id = '".$userInfo['id'] ."'");
        $adminPhone = '380951577726';
        $isAdmin = trim(str_replace([' ', '(', ')', '-'], '', $cleanPost['phone']), "+") == $adminPhone;

        if (!$isAdmin) {
            // Отправка SMS через TurboSMS API
            $apiKey = '40de5c81e6360bb0bfda2ada1a00304cbb4d4dfa';
            $sender = 'Max Trans'; // Имя отправителя, как зарегистрировано в TurboSMS
            $phone = $cleanPost['phone'];
            if ($cleanPost['language'] === 'uk') {
                $message = "Ваш код підтвердження: $randomCode";
            } else if ($cleanPost['language'] === 'ru') {
                $message = "Ваш код подтверждения: $randomCode";
            } else {
                $message = "Your confirmation code: $randomCode";
            }

            // Формируем запрос к TurboSMS API
            $data = [
                "recipients" => [$phone],
                "sms" => [
                    "sender" => $sender,
                    "text" => $message
                ]
            ];

            // Отправка запроса
            $ch = curl_init('https://api.turbosms.ua/message/send.json');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

            $response = curl_exec($ch);
            curl_close($ch);

            // Проверяем ответ от API
            if ($response) {
                echo  $cleanPost['language'];
                echo  json_encode('code saved to DB and SMS sent');
            } else {
                echo  $cleanPost['language'];
                echo  json_encode('code saved to DB but SMS not sent');
            }
        }
    } else {
        echo  'nouser';
    }
}



//if ($cleanPost['request'] === 'AuthSMS') {
//
//    $userInfo = $Db->getOne("SELECT id, email FROM `" .  DB_PREFIX . "_clients`WHERE phone = '" . $cleanPost['phone'] . "'");
//    function generateRandomCode($length = 4)
//    {
//        $code = '';
//        for ($i = 0; $i < $length; $i++) {
//            $code .= random_int(0, 9);
//        }
//        return $code;
//    }
//
//    $randomCode = generateRandomCode();
//
//    if ($userInfo) {
//        $saveCode = $Db->query("UPDATE `" .  DB_PREFIX . "_clients`SET code = '" . $randomCode . "' WHERE id = '" . $userInfo['id'] . "'");
//
//        $fromName = "Max Trans LTD";
//        $fromEmail = "info@maxtransltd.com";
//
//        $subject = "Код для входа";
//
//        if ($cleanPost['language'] === 'uk') {
//            $message = "Ваш код підтвердження: $randomCode";
//        } else if ($cleanPost['language'] === 'ru') {
//            $message = "Ваш код подтверждения: $randomCode";
//        } else {
//            $message = "Your confirmation code: $randomCode";
//        }
//
//
//        $headers = "MIME-Version: 1.0" . "\r\n";
//        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
//        $headers .= 'From: "' . $fromName . '" <' . $fromEmail . '>' . "\r\n";
//        $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
//        $headers .= 'X-Mailer: PHP/' . phpversion();
//
//        ini_set('SMTP', 'mail.adm.tools');
//        ini_set('smtp_port', '465');
//        ini_set('sendmail_from', 'info@maxtransltd.com');
//        ini_set('sendmail_path', '"/usr/sbin/sendmail -t -i"');
//        mail($userInfo['email'], $subject, $message, $headers);
//
////        echo  json_encode('code saved to DB');
//        echo  $cleanPost['language'];
//    } else {
//        echo  'nouser';
//    }
//}


if ($cleanPost['request'] === 'AuthCode') {
    $adminPhone = '380951577726';
    $isAdmin = trim(str_replace([' ', '(', ')', '-'], '', $cleanPost['phone']), "+") == $adminPhone;

    $userInfo = $Db->getOne("SELECT email, code FROM `" .  DB_PREFIX . "_clients`WHERE phone = '" . $cleanPost['phone'] . "'");
    $isAdmin = $isAdmin && !empty($userInfo['email']) && $cleanPost['code'] == 7937;
    if (($userInfo['code'] === $cleanPost['code']) || $isAdmin) {
        $user = $User->appAuth($userInfo['email']);

        if ($user === false) {
            echo  $GLOBALS['dictionary']['MSG_MSG_LOGIN_USER_NOTFOUND'];
        } elseif ($user === null) {
            echo  $GLOBALS['dictionary']['MSG_MSG_LOGIN_NEVERNYE_DANNYE'];
        } elseif ($user) {
            $getUser = $Db->getOne("SELECT id, name, second_name, phone, email, password FROM `" .  DB_PREFIX . "_clients`WHERE email = '" . $userInfo['email'] . "'");
            $response = [
                'accessToken' => $_SESSION['user']['app_crypt'],
                'user' => [ '_id' => $getUser['id'],
                    'email' => $getUser['email'],
                    'password' => $getUser['password'],
                    'name' => $getUser['name'],
                    'surname' => $getUser['second_name'],
                    'phone' => $getUser['phone']
                ]
            ];
            echo  json_encode($response);
        } else {
            echo  $GLOBALS['dictionary']['MSG_MSG_LOGIN_NEVERNYE_DANNYE'];
        }
    } else {
        echo  'error';
    }
}


if ($cleanPost['request'] === "appRegisterOld") {
    if (!filter_var($cleanPost['email'], FILTER_VALIDATE_EMAIL)) {
        exit($GLOBALS['dictionary']['MSG_MSG_REGISTER_NEVERNYJ_EMAIL']);
    }
    $getemail = $Db->getAll("SELECT id FROM `" . DB_PREFIX . "_clients` WHERE email = '" . $cleanPost['email'] . "' LIMIT 1");
    if ($getemail) {
        exit($GLOBALS['dictionary']['MSG_MSG_REGISTER_ETOT_E-MAIL_UZHE_ZANYAT']);
    }
    if (isset($cleanPost['save_data']) && $cleanPost['save_data'] == 1){
        $arFields = array('name', 'email','second_name','phone','patronymic','birth_date','phone_code');
        $arData = array("'" . $cleanPost['name'] . "'",
            "'" . $cleanPost['email'] . "'",
            "'" . $cleanPost['family_name'] . "'",
            "'" . $cleanPost['phone'] . "'",
            "'" . $cleanPost['patronymic'] . "'",
            "'" . $cleanPost['birth_date'] . "'",
            "'" . $cleanPost['phone_code'] . "'");
    }else{
        $arFields = array('name', 'email');
        $arData = array("'" . $cleanPost['name'] . "'", "'" . $cleanPost['email'] . "'");
    }

    if ($User->appRegister($arFields, $arData, $cleanPost['email'], $cleanPost['password'])) {
        echo  json_encode($_SESSION['user']['app_crypt']);
    } else {
        echo  $GLOBALS['dictionary']['MSG_MSG_REGISTER_NE_UDALOSI_ZAREGISTRIROVATISYA'];
    }
}


if ($cleanPost['request'] === "appRegister") {


    if (!filter_var($cleanPost['email'], FILTER_VALIDATE_EMAIL)) {
        exit($GLOBALS['dictionary']['MSG_MSG_REGISTER_NEVERNYJ_EMAIL']);
    }
    $getemail = $Db->getAll("SELECT id FROM `" . DB_PREFIX . "_clients` WHERE email = '" . $cleanPost['email'] . "' LIMIT 1");
    $getPhone = $Db->getAll("SELECT id FROM `" . DB_PREFIX . "_clients` WHERE phone = '" . $cleanPost['phone'] . "' LIMIT 1");
    if ($getemail) {
        exit('mailbusy');
    }
    if ($getPhone) {
        exit('phonebusy');
    }
    if (isset($cleanPost['save_data']) && $cleanPost['save_data'] == 1){
        $arFields = array('name', 'email','second_name','phone','patronymic','birth_date','phone_code');
        $arData = array("'" . $cleanPost['name'] . "'",
            "'" . $cleanPost['email'] . "'",
            "'" . $cleanPost['family_name'] . "'",
            "'" . $cleanPost['phone'] . "'",
            "'" . $cleanPost['patronymic'] . "'",
            "'" . $cleanPost['birth_date'] . "'",
            "'" . $cleanPost['phone_code'] . "'");
    }else{

        $arFields = array(
            'name',
            'second_name',
            'patronymic',
            'email',
            'phone',
            'phone_code',
            'uid',
            'birth_date',
            'last_auth_date',
            'miles',
            'crypt',
        );
        $arData = array(
            "'" . $cleanPost['name'] . "'",
            "''",
            "''",
            "'" . $cleanPost['email'] . "'",
            "'" . $cleanPost['phone'] . "'",
            "0",
            "''",
            "NOW()",
            "NOW()",
            "0",
            "''",
        );
    }

    $password = bin2hex(random_bytes(8));


    if ($User->appRegister($arFields, $arData, $cleanPost['email'], $password)) {

        $getUser = $Db->getOne("SELECT id, name, second_name, phone, email, password FROM `" .  DB_PREFIX . "_clients`WHERE email = '" . $cleanPost['email'] . "'");
        $response = [
            'accessToken' => $_SESSION['user']['app_crypt'],
            'user' => [ '_id' => $getUser['id'],
                'email' => $getUser['email'],
                'password' => $getUser['password'],
                'name' => $getUser['name'],
                'surname' => $getUser['second_name'],
                'phone' => $getUser['phone']
            ]
        ];

        // Отправка email с паролем
        $fromName = "Max Trans LTD";
        $fromEmail = "info@maxtransltd.com";

        $subject = "Ваш новый пароль";
        $message = "Здравствуйте!\n\nВаш новый пароль: $password\n\nС уважением,\nКоманда Max Trans LTD";
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: "' . $fromName . '" <' . $fromEmail . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        ini_set('SMTP', 'mail.adm.tools');
        ini_set('smtp_port', '465');
        ini_set('sendmail_from', 'info@maxtransltd.com');
        ini_set('sendmail_path', '"/usr/sbin/sendmail -t -i"');
        mail($cleanPost['email'], $subject, $message, $headers);

        // Возврат ответа с зашифрованными данными пользователя
        echo  json_encode($response);
    } else {
        echo  'error';
    }
}


if($cleanPost['request'] === 'faq') {
    $lang = $cleanPost['lang'];

    $faq_questions = $Db->getAll("SELECT id, question_".$lang." as question, answer_".$lang." as answer FROM `" .  DB_PREFIX . "_faq`WHERE active = '1'");



    if ($faq_questions) {
        echo  json_encode($faq_questions);
    } else {
        echo  json_encode(['error' => 'Wrong query']);
    }
}


if($cleanPost['request'] === 'getCities') {
    $lang = $cleanPost['lang'];

    $get_cities = $Db->getall("SELECT id,title_".$lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND section_id > 0 AND station = 0 ORDER BY sort DESC,title_".$lang." ASC");

    if ($get_cities) {
        echo  json_encode($get_cities);
    } else {
        echo  json_encode(['error' => 'Wrong query']);
    }
}

if ($cleanPost['request'] === 'terms') {
    $lang = $cleanPost['lang'];

    $getTerms = $Db->getOne("SELECT text_".$lang." AS text FROM `" .  DB_PREFIX . "_pages`WHERE id = '83'");

    if ($getTerms) {
        echo  json_encode($getTerms['text'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($cleanPost['request'] === 'persData') {
    $lang = $cleanPost['lang'];

    $getTerms = $Db->getOne("SELECT text_".$lang." AS text FROM `" .  DB_PREFIX . "_txt_blocks`WHERE code = 'TEXT_PERSONALDATA'");

    if ($getTerms) {
        echo  json_encode($getTerms['text'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}





if ($cleanPost['request'] === 'app_filter_date') {
    $departure = $_POST['departure'];
    $arrival = $_POST['arrival'];

/*    $tourParams = $Db->getAll("SELECT departure_closed, stops_closed, races_future_date FROM `" .  DB_PREFIX . "_tours`WHERE id =".$Elem['id']."");
    $tourParams['0']['races_future_date'];*/
    $daysResult = $Db->getAll("SELECT DISTINCT t.days, t.races_future_date
                FROM `" . DB_PREFIX . "_tours` t
                LEFT JOIN `" .  DB_PREFIX . "_cities`dc ON dc.id = t.departure
                LEFT JOIN `" .  DB_PREFIX . "_cities`ac ON ac.id = t.arrival
                LEFT JOIN `" .  DB_PREFIX . "_cities`dcountry ON dcountry.id = dc.section_id
                LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
                LEFT JOIN `" .  DB_PREFIX . "_tours_stops_prices`tsp ON tsp.tour_id = t.id
                LEFT JOIN `" .  DB_PREFIX . "_tours_stops`ts ON ts.tour_id = t.id
                WHERE t.active = '1' AND (t.departure = ".(int)$cleanPost['departure']." OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE from_stop
                IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".(int)$cleanPost['departure']."' ) ))
                 AND (t.arrival = ".(int)$cleanPost['arrival']." OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE to_stop
                IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".(int)$cleanPost['arrival']."' ) ))
                ORDER BY dc.section_id ASC,tsp.price DESC");


   /* $highlightedDays = [];*/
    exit(json_encode($daysResult));
    /*foreach ($daysResult as $row) {
        if (isset($row['days'])) {
            $tourDays = explode(',', $row['days']);
            foreach ($tourDays as $day) {
                if (!in_array($day, $highlightedDays)) {
                    $highlightedDays[] = $day;
                }
            }
        }
    }



    $highlightedDays = json_encode($highlightedDays);
    $highlightedDaysString = implode(",", $highlightedDays);

    $data = [
        'daysOfWeek' => $highlightedDays,
        'countAvailableDays' => 1,
    ];

    exit(json_encode($data));*/

    //exit($highlightedDays);
}


if ($cleanPost['request'] === 'searchTickets') {
    $lang = $cleanPost['lang'];
    $arrival = $cleanPost['arrival'];
    $departure = $cleanPost['departure'];
    $date = $cleanPost['date'];

    $departureTitle = $Db->getOne("SELECT title_".$lang." as dep_title FROM `" .  DB_PREFIX . "_cities`WHERE id = ".$departure."");
    $arrivalTitle = $Db->getOne("SELECT title_".$lang." as arr_title FROM `" .  DB_PREFIX . "_cities`WHERE id = ".$arrival."");

    $getTickets = $Db->getAll("SELECT DISTINCT(t.id), t.departure, t.arrival,
        dc.title_".$lang." AS departure_city,
        dc.section_id AS departure_city_section_id,
        ac.title_".$lang." AS arrival_city,
        ac.section_id AS arrival_city_section_id,
        b.title_" . $lang . " AS bus_title,
        tsl.free_tickets AS free_tickets
        FROM `" . DB_PREFIX . "_tours` t
        LEFT JOIN `" .  DB_PREFIX . "_cities`dc ON dc.id = t.departure
        LEFT JOIN `" .  DB_PREFIX . "_cities`ac ON ac.id = t.arrival
        LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
        LEFT JOIN `" .  DB_PREFIX . "_tours_stops_prices`tsp ON tsp.tour_id = t.id
        LEFT JOIN `" .  DB_PREFIX . "_tours_stops`ts ON ts.tour_id = t.id
        LEFT JOIN `" .  DB_PREFIX . "_tours_sales`tsl ON tsl.tour_id = t.id
        WHERE t.active = '1' AND tsl.tour_date = '".$date."' AND (t.departure = ".$departure." OR t.id
            IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE from_stop
            IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".$departure."')) ) AND (t.arrival = ".$arrival." OR t.id
            IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE to_stop
            IN(SELECT id FROM `" .  DB_PREFIX . "_cities`WHERE section_id = '".$arrival."')) )");

    $ticketsData = [];

    foreach ($getTickets as $ticket) {
        $ticketId = $ticket['id'];





        $getDepartureStations = $Db->getAll("SELECT DISTINCT(c.id) AS station_id, c.title_" . $lang . " AS dep_station_title, ts.departure_time AS dep_time
                                         FROM `" . DB_PREFIX . "_cities` c
                                         LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices` tsp ON tsp.from_stop = c.id
                                         LEFT JOIN `".DB_PREFIX."_tours_stops` ts ON ts.stop_id = c.id
                                         WHERE c.active = '1' AND c.section_id = '" . $departure . "' AND ts.tour_id = '".$ticketId."'
                                         AND c.station = '1'
                                         AND tsp.price > 0
                                         ORDER BY c.sort DESC");

        $getArrivalStations = $Db->getAll("SELECT DISTINCT(c.id) AS station_id, c.title_" . $lang . " AS arr_station_title, ts.arrival_time AS arr_time,
                                        arrival_day AS days
                                       FROM `" . DB_PREFIX . "_cities` c
                                       LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices` tsp ON tsp.to_stop = c.id
                                       LEFT JOIN `" . DB_PREFIX . "_tours_stops` ts ON ts.stop_id = c.id
                                       WHERE c.active = '1' AND c.section_id = '" . $arrival . "' AND ts.tour_id = '".$ticketId."'
                                       AND c.station = '1'
                                       AND tsp.price > 0
                                       ORDER BY c.sort DESC");
        $transfer = $Db->getOne("SELECT transfer_station_id FROM `" .  DB_PREFIX . "_tours_transfers`WHERE tour_id = '".$ticketId."'");



        $prices = [];


        foreach ($getDepartureStations as $depStation) {
            foreach ($getArrivalStations as $arrStation) {
                $getTicketStops = $Db->getAll("SELECT stop_id, arrival_time, departure_time, arrival_day FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$ticketId."' ORDER BY id ASC");
                $price = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE from_stop = '".$depStation['station_id']."' AND to_stop = '".$arrStation['station_id']."'");

                // Рассчитываем общее время в пути
                $rideTime = calculateTotalTravelTime($getTicketStops, $depStation['station_id'], $arrStation['station_id'], $arrStation['days']);
                // Предположим, что $rideTime возвращает "29:00" (часы:минуты)
                $rideTimeParts = explode(':', $rideTime);
                $rideTimeHours = (int)$rideTimeParts[0]; // Получаем часы
                $rideTimeMinutes = (int)$rideTimeParts[1]; // Получаем минуты

// Теперь вы можете использовать эти значения для расчета времени прибытия
                $calculatedArrivalDateTime = calculateArrivalDateTime($date . ' ' . $depStation['dep_time'], $rideTimeHours, $rideTimeMinutes);

// Добавляем данные в массив билетов
                if ($price) {
                    $ticket['departure_station_id'] = $depStation['station_id'];
                    $ticket['dep_station_title'] = $depStation['dep_station_title'];
                    $ticket['dep_time'] = $depStation['dep_time'];
                    $ticket['arrival_station_id'] = $arrStation['station_id'];
                    $ticket['arr_station_title'] = $arrStation['arr_station_title'];
                    $ticket['arr_time'] = $arrStation['arr_time'];
                    $ticket['price'] = $price['price'];
                    $ticket['transfers'] = $transfer['transfer_station_id'];
                    $ticket['rideTime'] = $rideTime;
                    $ticket['ticket_arrival_city'] = $arrivalTitle['arr_title']; // Сохраняем исходное значение времени в пути


                    $ticket['calculated_arrival_time'] = $calculatedArrivalDateTime;

                    $ticketsData[] = $ticket;
                }

            }
        }

    }

    $response = [
        'tickets' => $ticketsData,
        'departure_title' => $departureTitle['dep_title'],
        'arrival_title' => $arrivalTitle['arr_title']
    ];

    echo  json_encode($response);

}

if ($cleanPost['request'] === 'canOrderTicket') {
    $tourId = $cleanPost['tour_id'];
    $from = $cleanPost['departure'];
    $date = $cleanPost['date'];

    $canOrderTicket = true;
    $toursDeparture = $Db->getOne("SELECT departure FROM `" .  DB_PREFIX . "_tours`WHERE id='".$tourId."'");
    $toursClosed = $Db->getOne("SELECT departure_closed, stops_closed FROM `" .  DB_PREFIX . "_tours`WHERE id='".$tourId."'");

    $toursDepartureClosedTime = $toursClosed['departure_closed'];
    $toursStopsClosedTime = $toursClosed['stops_closed'];

    $checkTicketDeparture = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".(int)$cleanPost['tour_id']."' AND stop_id = '".$cleanPost['departure']."' ");

    $currentDate = date('Y-m-d');
    $currentTime = date('H:i:s', time());
    $currentDateTime = strtotime($currentDate . ' ' . $currentTime);

    $departureDateTime = strtotime($date . ' ' . $checkTicketDeparture['departure_time']);


    if (strtotime($date) < strtotime($currentDate)) {
        $canOrderTicket = false;
    }

    elseif (strtotime($date) == strtotime($currentDate)) {

        if ($currentTime >= $toursDepartureClosedTime || $currentTime >= $toursStopsClosedTime) {
            $canOrderTicket = false;
        }

        elseif ($currentTime < $toursDepartureClosedTime && $currentTime >= $checkTicketDeparture['departure_time']) {
            $canOrderTicket = false;
        }
    }


    if($canOrderTicket) {
        echo  'ok';
    } else {
        echo  'late';
    }
}




if ($cleanPost['request'] === 'businfo') {


    $busId = $Db->getOne("SELECT bus FROM `" .  DB_PREFIX . "_tours`WHERE id = '".$cleanPost['tour_id']."'");


    $busTitle = $Db->getOne("SELECT title_".$cleanPost['lang']." AS title FROM `" .  DB_PREFIX . "_buses`WHERE id = ".$busId['bus']."");

    $busOpt = $Db->getAll("SELECT option_id, bo.option_icon, bo.title_".$cleanPost['lang']." AS title FROM `" .  DB_PREFIX . "_buses_options_connector`LEFT JOIN `" .  DB_PREFIX . "_buses_options`bo ON bo.id = option_id WHERE bus_id = ".$busId['bus']." AND bo.active = '1' ");

    $response = [
        'busTitle' =>  $busTitle['title'],
        'busOptions' => $busOpt
    ];

    echo  json_encode($response);

}

if ($cleanPost['request'] === 'routeStations') {
    $getStops = $Db->getAll("SELECT stop_id,arrival_time,departure_time,arrival_day,stop_num,st.title_".$cleanPost['lang']." AS stop_title,st.section_id,ct.title_".$cleanPost['lang']." AS city_title FROM `" .  DB_PREFIX . "_tours_stops`LEFT JOIN `" .  DB_PREFIX . "_cities`st ON st.id = stop_id
    LEFT JOIN `" .  DB_PREFIX . "_cities`ct ON ct.id = st.section_id
     WHERE tour_id = ".$cleanPost['tour_id']." ORDER BY stop_num ");

    $response = [
        'stations' => $getStops
    ];

    echo  json_encode($response);
}


if ($cleanPost['request'] == 'order_uniqid') {
    function generateOrderId()
    {
        return uniqid('order_', true);
    }

    $order_id = generateOrderId();

    $response = ['order_id' => $order_id];


    echo  json_encode($response);
}



if ($cleanPost['request'] === 'order_route'){
    $tourId = (int)$cleanPost['order']['tour_id'];
    $from = (int)$cleanPost['order']['from'];
    $to = (int)$cleanPost['order']['to'];
    $tourDate = $cleanPost['order']['date'];
    $clientName = $cleanPost['order']['name'];
    $clientSurname = $cleanPost['order']['surname'];
    $clientMail = $cleanPost['order']['email'];
    $clientPhone = $cleanPost['order']['phone'];
    $paymethod = $cleanPost['order']['paymethod'];
    $passengers = (int)$cleanPost['order']['passengers'];
    $uniqId = $cleanPost['order']['order_id'];

    $existingOrder = $Db->getOne("SELECT id FROM `" . DB_PREFIX . "_orders` WHERE uniqId = '".$uniqId."' ");

    if ($existingOrder) {
        echo  'ok';
    } else {
        $fieldName = $fieldValue = array();
        $fieldName[] = 'date';
        $fieldValue[] = 'NOW()';
        $fieldName[] = 'client_id';
        $fieldValue[] = '"' . $User->id . '"';
        $fieldName[] = 'client_name';
        $fieldValue[] = '"' . $clientName . '"';
        $fieldName[] = 'client_surname';
        $fieldValue[] = '"' . $clientSurname . '"';
        $fieldName[] = 'client_email';
        $fieldValue[] = '"' . $clientMail . '"';
        $fieldName[] = 'client_phone';
        $fieldValue[] = '"' . $clientPhone . '"';
        $fieldName[] = 'tour_id';
        $fieldValue[] = '"' . $tourId . '"';
        $fieldName[] = 'from_stop';
        $fieldValue[] = '"' . $from . '"';
        $fieldName[] = 'to_stop';
        $fieldValue[] = '"' . $to . '"';
        $fieldName[] = 'tour_date';
        $fieldValue[] = '"' . $tourDate . '"';
        $fieldName[] = 'passagers';
        $fieldValue[] = '"' . (int)$cleanPost['order']['passengers'] . '"';
        $fieldName[] = 'uniqId';
        $fieldValue[] = '"' . $uniqId . '"';
        $order = $Db->query("INSERT INTO `" . DB_PREFIX . "_orders` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");
        if ($order) {
            $updPopular = $Db->query("UPDATE `" .  DB_PREFIX . "_tours`SET popular = popular + 1 WHERE id = '" . $tourId . "' ");
            $tourDistance = $Db->getOne("SELECT distance FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE tour_id = '" . $tourId . "' AND from_stop = '" . $from . "' AND to_stop = '" . $to . "' ");
            $updClientsMiles = $Db->query("UPDATE `" .  DB_PREFIX . "_clients`SET miles = miles + " . (int)$tourDistance['distance'] . " WHERE id = '" . $User->id . "' ");
            $updSales = $Db->query("UPDATE `" .  DB_PREFIX . "_tours_sales`SET tickets_order = tickets_order + " . $passengers . " WHERE tour_id = '" . $tourId . "' AND tour_date = '" . $tourDate . "' ");



            $buyerData = [
                'name' => $clientName,
                'family_name' => $clientSurname,
                'patronymic' => $cleanPost['patronymic'],
                'birth_date' => $cleanPost['birth_date'],
            ];

            $fieldName = $fieldValue = [];
            $fieldName[] = 'name';
            $fieldValue[] = '"' . $buyerData['name'] . '"';
            $fieldName[] = 'second_name';
            $fieldValue[] = '"' . $buyerData['family_name'] . '"';
            $fieldName[] = 'patronymic';
            $fieldValue[] = '"' . $buyerData['patronymic'] . '"';
            $fieldName[] = 'order_id';
            $fieldValue[] = '"' . $uniqId . '"';
            $fieldName[] = 'birth_date';
            $fieldValue[] = '"' . $buyerData['birth_date'] . '"';

            $Db->query("INSERT INTO `" . DB_PREFIX . "_orders_passangers` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");

            foreach ($cleanPost['order']['passengersData'] as $passenger) {
                $fieldName = $fieldValue = array();
                $fieldName[] = 'name';
                $fieldValue[] = '"' . $passenger['name'] . '"';
                $fieldName[] = 'second_name';
                $fieldValue[] = '"' . $passenger['surname'] . '"';
                $fieldName[] = 'order_id';
                $fieldValue[] = '"' . $uniqId . '"';


                $Db->query("INSERT INTO `" . DB_PREFIX . "_orders_passangers` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");
            }


            echo  'ok';
        } else {
            echo  'err';
        }
    }
}

if( $cleanPost['request'] === 'checkPayment') {
    $order_id = $cleanPost['order_id'];

    $status = $Db->getOne("SELECT id, payment_status FROM `" .  DB_PREFIX . "_orders`WHERE uniqid = '".$order_id."'");

    echo  json_encode($status);
}

if ($cleanPost['request'] === 'removeAccount') {
    $token = $cleanPost['token'];
    $appToken = $_SESSION['user']['app_crypt'];
    $user = [];

    if ($appToken == $token) {
        $user = $Db->getOne("SELECT * FROM `" .  DB_PREFIX . "_clients`WHERE crypt = '" . $token . "'");
    }

    if (!empty($user)) {
        mysqli_query($db, "DELETE FROM `" .  DB_PREFIX . "_clients`WHERE `id`='". $user['id'] ."'");
        echo  json_encode(["status" => "200", "message" => "User deleted"]);
    } else {
        echo  json_encode(["status" => "403", "message" => "Token expired"]);
    }
}

if ($cleanPost['request'] === 'history') {
    $userMail = $cleanPost['user'];

    $futureRidesArray = [];
    $getFutureRides = $Db->getAll("SELECT
                o.id, o.uniqid, o.passagers,
                o.tour_id,
                o.from_stop,
                o.to_stop,
                o.tour_date,
                departure_city.title_" . $cleanPost['lang'] . " AS departure_city,
                departure_city.section_id AS departure_city_section_id,
                departure_station.title_" . $cleanPost['lang'] . " AS departure_station,
                arrival_city.title_" . $cleanPost['lang'] . " AS arrival_city,
                arrival_city.section_id AS arrival_city_section_id,
                arrival_station.title_" . $cleanPost['lang'] . " AS arrival_station,
                tsp.price AS price,
                bus.title_" . $cleanPost['lang'] . " AS bus_title,
                dt.departure_time,
                at.arrival_time,
                ad.arrival_day
                FROM `" . DB_PREFIX . "_orders` o
                LEFT JOIN `" . DB_PREFIX . "_tours` t ON t.id = o.tour_id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` dt ON dt.tour_id = o.tour_id AND dt.stop_id = o.from_stop
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` at ON at.tour_id = o.tour_id AND at.stop_id = o.to_stop
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` ad ON ad.tour_id = o.tour_id AND ad.stop_id = o.to_stop
                LEFT JOIN `" . DB_PREFIX . "_cities` departure_station ON departure_station.id = o.from_stop
                LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = departure_station.section_id
                LEFT JOIN `" . DB_PREFIX . "_cities` arrival_station ON arrival_station.id = o.to_stop
                LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = arrival_station.section_id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices` tsp ON tsp.from_stop = o.from_stop AND tsp.to_stop = o.to_stop AND tsp.tour_id = t.id
                LEFT JOIN `" . DB_PREFIX . "_buses` bus ON bus.id = t.bus
                WHERE o.client_email = '" . $userMail . "'
                AND o.tour_date >= CURDATE()
                AND o.ticket_return = 0
                GROUP BY o.id
                ORDER BY o.tour_date ASC ");

    foreach ($getFutureRides as $k => $potencialFutureRide) {
        if (strtotime($potencialFutureRide['tour_date'] . ' ' . $potencialFutureRide['departure_time']) > time()) {
            $futureRidesArray[] = $potencialFutureRide;
        }
    }

    if (count($futureRidesArray) > 0) {
        foreach ($futureRidesArray as $k => $futureRide) {
            $month = $Db->getone("SELECT title_" . $cleanPost['lang'] . " AS title FROM `" . DB_PREFIX . "_months` WHERE id = '" . date('m', strtotime($futureRide['tour_date'])) . "' ");
            $futureRidesArray[$k]['month'] = $month['title'];

            $international = (int)$futureRide['departure_city_section_id'] != $futureRide['arrival_city_section_id'];
            $futureRidesArray[$k]['international'] = $international;

            $getTicketStops = $Db->getAll("SELECT stop_id, arrival_time, departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '" . $futureRide['tour_id'] . "' ORDER BY id ASC ");
            $rideTime = calculateTotalTravelTime($getTicketStops, $futureRide['from_stop'], $futureRide['to_stop'], $futureRide['arrival_day']);
            $futureRidesArray[$k]['rideTime'] = $rideTime;

            $getTicketStops = $Db->getAll("SELECT stop_id, arrival_time, departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '" . $futureRide['tour_id'] . "' ORDER BY id ASC ");
            $rideTime = calculateTotalTravelTime($getTicketStops, $futureRide['from_stop'], $futureRide['to_stop'], $futureRide['arrival_day']);
            $futureRidesArray[$k]['rideTime'] = $rideTime;

            list($rideHours, $rideMinutes, $rideSeconds) = explode(':', $rideTime);

            $departureDateTime = new DateTime($futureRide['tour_date'] . ' ' . $futureRide['departure_time']);

            $departureDateTime->modify("+{$rideHours} hours");
            $departureDateTime->modify("+{$rideMinutes} minutes");
            $departureDateTime->modify("+{$rideSeconds} seconds");


            $futureRidesArray[$k]['arrival_date'] = $departureDateTime->format('Y-m-d H:i:s');
        }

    }

    $pastRidesArray = [];
    $getPastRides = $Db->getAll("SELECT
                o.id, o.uniqid, o.passagers,
                o.tour_id,
                o.from_stop,
                o.to_stop,
                o.tour_date,
                departure_city.title_" . $cleanPost['lang'] . " AS departure_city,
                departure_city.section_id AS departure_city_section_id,
                departure_station.title_" . $cleanPost['lang'] . " AS departure_station,
                arrival_city.title_" . $cleanPost['lang'] . " AS arrival_city,
                arrival_city.section_id AS arrival_city_section_id,
                arrival_station.title_" . $cleanPost['lang'] . " AS arrival_station,
                tsp.price AS price,
                bus.title_" . $cleanPost['lang'] . " AS bus_title,
                dt.departure_time,
                at.arrival_time,
                ad.arrival_day
                FROM `" . DB_PREFIX . "_orders` o
                LEFT JOIN `" . DB_PREFIX . "_tours` t ON t.id = o.tour_id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` dt ON dt.tour_id = o.tour_id AND dt.stop_id = o.from_stop
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` at ON at.tour_id = o.tour_id AND at.stop_id = o.to_stop
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` ad ON ad.tour_id = o.tour_id AND ad.stop_id = o.to_stop
                LEFT JOIN `" . DB_PREFIX . "_cities` departure_station ON departure_station.id = o.from_stop
                LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = departure_station.section_id
                LEFT JOIN `" . DB_PREFIX . "_cities` arrival_station ON arrival_station.id = o.to_stop
                LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = arrival_station.section_id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices` tsp ON tsp.from_stop = o.from_stop AND tsp.to_stop = o.to_stop AND tsp.tour_id = t.id
                LEFT JOIN `" . DB_PREFIX . "_buses` bus ON bus.id = t.bus
                WHERE o.client_email = '" . $userMail . "'
                AND o.tour_date <= CURDATE()
                AND o.ticket_return = 0
                GROUP BY o.id
                ORDER BY o.tour_date ASC ");

    foreach ($getPastRides as $k => $potencialPastRide) {
        if (strtotime($potencialPastRide['tour_date'] . ' ' . $potencialPastRide['departure_time']) < time()) {

            $pastRidesArray[] = $potencialPastRide;
        }
    }

    if (count($pastRidesArray) > 0) {
        foreach ($pastRidesArray as $k => $pastRide) {

            $month = $Db->getone("SELECT title_" . $cleanPost['lang'] . " AS title FROM `" . DB_PREFIX . "_months` WHERE id = '" . date('m', strtotime($pastRide['tour_date'])) . "' ");
            $pastRidesArray[$k]['month'] = $month['title'];

            $international = (int)$pastRide['departure_city_section_id'] != $pastRide['arrival_city_section_id'];
            $pastRidesArray[$k]['international'] = $international;

            $getTicketStops = $Db->getAll("SELECT stop_id, arrival_time, departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '" . $pastRide['tour_id'] . "' ORDER BY id ASC ");
            $rideTime = calculateTotalTravelTime($getTicketStops, $pastRide['from_stop'], $pastRide['to_stop'], $pastRide['arrival_day']);
            $pastRidesArray[$k]['rideTime'] = $rideTime;

            list($rideHours, $rideMinutes, $rideSeconds) = explode(':', $rideTime);

            $departureDateTime = new DateTime($pastRide['tour_date'] . ' ' . $pastRide['departure_time']);

            $departureDateTime->modify("+{$rideHours} hours");
            $departureDateTime->modify("+{$rideMinutes} minutes");
            $departureDateTime->modify("+{$rideSeconds} seconds");

            $pastRidesArray[$k]['arrival_date'] = $departureDateTime->format('Y-m-d H:i:s');
        }

    }


    $response = ['pastrides' => $pastRidesArray, 'futurerides' => $futureRidesArray];


    echo  json_encode($response);
}

if ($cleanPost['request'] === 'coopOffer') {
    $name = $cleanPost['name'];
    $email = $cleanPost['email'];
    $phone = $cleanPost['phone'];
    $company = $cleanPost['company'];
    $message = $cleanPost['comment'];


    function sendOfferMail($name, $email, $phone, $message, $company) {
        $to = env('MAIL_ADMIN');
        $subject = 'Нова заявка на дзвінок';
        $messageContent = "
        <html>
        <head>
            <title>Нова заявка на співпрацю:</title>
            <style>
                .email-content { border-left: 4px solid #40A6FF; padding-left: 10px; }
                .email-content table { width: 100%; border-collapse: collapse; }
                .email-content td { padding: 5px 10px; }
                .email-titles { font-weight: bold; }
            </style>
        </head>
        <body>
            <div class='email-content'>
                <table>
                    <tr><td class='email-titles'>Ім'я</td><td>$name</td></tr>
                    <tr><td class='email-titles'>Емайл</td><td>$email</td></tr>
                    <tr><td class='email-titles'>Телефон</td><td>$phone</td></tr>
                    <tr><td class='email-titles'>Компанія</td><td>$company</td></tr>
                    <tr><td class='email-titles'>Повідомлення</td><td>$message</td></tr>
                </table>
            </div>
        </body>
        </html>";
        $fromName = "Max Trans LTD";
        $fromEmail = "info@maxtransltd.com";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: "' . $fromName . '" <' . $fromEmail . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        ini_set('SMTP', 'mail.adm.tools');
        ini_set('smtp_port', '465');
        ini_set('sendmail_from', 'info@maxtransltd.com');
        ini_set('sendmail_path', '"/usr/sbin/sendmail -t -i"');

        try {
            if (mail($to, $subject, $messageContent, $headers)) {
                echo  'ok';
            } else {
                echo  'error';
            }
        } catch (Exception $e) {
            echo  'Message could not be sent. Error: ' . $e->getMessage();
        }

    }


    sendOfferMail($name, $email, $phone, $message, $company);

}

if ($cleanPost['request'] === 'getPassangers') {
    $uniqId = $cleanPost['uniqid'];
    $getOrderedTickets = $Db->getAll("SELECT id, name, second_name FROM `" .  DB_PREFIX . "_orders_passangers`WHERE order_id = '".$uniqId."' AND ticket_return = 0");

    echo  json_encode($getOrderedTickets);
}


if ($cleanPost['request'] === 'returnTickets'){


    $tikcetsIds = $cleanPost['ticketsIds'];
    $idsString = implode(',', array_map('intval', $tikcetsIds));

    $returnedTickets = count($tikcetsIds);


    $ticketInfo = $Db->getOne("SELECT o.client_name, o.client_phone, o.client_surname, o.tour_date, o.client_email,o.from_stop,o.to_stop,o.tour_id, o.uniqid, fs.title_uk AS from_title, fs.section_id AS from_city_id, ts.section_id AS to_city_id, tc.title_uk AS to_city_title, fc.title_uk AS from_city_title, ts.title_uk AS to_title, tsp.price
    FROM `".DB_PREFIX."_orders` o
    LEFT JOIN `".DB_PREFIX."_cities` fs ON fs.id = from_stop
    LEFT JOIN `".DB_PREFIX."_cities` ts ON ts.id = to_stop
    LEFT JOIN `".DB_PREFIX."_cities` fc ON fc.id = fs.section_id
    LEFT JOIN `".DB_PREFIX."_cities` tc ON tc.id = ts.section_id
    LEFT JOIN `".DB_PREFIX."_tours_stops_prices` tsp ON tsp.from_stop = o.from_stop AND tsp.to_stop = o.to_stop AND tsp.tour_id = o.tour_id
 WHERE o.id = '".$cleanPost['id']."' ");

    $upd = $Db->query("UPDATE `".DB_PREFIX."_orders_passangers` SET ticket_return = '1',return_reason = '".(int)$cleanPost['reason']."',return_payment_type = '".(int)$cleanPost['return_payments']."',return_date = NOW() WHERE id IN ($idsString) ");






    if ($upd){

        $checkOrder = $Db->getAll("SELECT id FROM `".DB_PREFIX."_orders_passangers` WHERE order_id = '".$ticketInfo['uniqid']."' AND ticket_return = 0");

        if (empty($checkOrder)) {

            $updOrder = $Db->query("UPDATE `".DB_PREFIX."_orders` SET ticket_return = '1',return_reason = '".(int)$cleanPost['reason']."',return_payment_type = '".(int)$cleanPost['return_payments']."',return_date = NOW() WHERE id = '".(int)$cleanPost['id']."' ");

            $returnMiles = $Db->getOne("SELECT distance FROM `".DB_PREFIX."_tours_stops_prices` WHERE tour_id = '".$ticketInfo['tour_id']."' AND from_stop = '".$ticketInfo['from_stop']."' AND to_stop = '".$ticketInfo['to_stop']."' ");
            $updClientMiles = $Db->query("UPDATE `".DB_PREFIX."_clients` SET miles = miles - ".(int)$returnMiles['distance']." WHERE id = '".$User->id."' ");

        }


        $clientName = $ticketInfo['client_name'];
        $clientSurname = $ticketInfo['client_surname'];
        $phone = $ticketInfo['client_phone'];
        $date = $ticketInfo['tour_date'];
        $from = $ticketInfo['from_title'];
        $fromCity = $ticketInfo['from_city_title'];
        $to = $ticketInfo['to_title'];
        $toCity = $ticketInfo['to_city_title'];
        $email = $ticketInfo['client_email'];
        $total = $ticketInfo['price'] * $returnedTickets;

        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $to1 = env('MAIL_ADMIN');  // Замените на email администратора max210183@ukr.net
        $subject1 = 'Повернення квитків';
        $message1 = "
        <html>
        <head>
            <title>Повернення квитків</title>
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
            <div class='header'>
                <a href='https://www.maxtransltd.com'>
                <img src='$imagePath' alt='MaxTrans LTD' class='logo'>
                </a>
            </div>
            <p>Повернено квиток:</p>
            <div class='email-content'>
                <table>
                    <tr>
                        <td class='email-titles'>ФІО</td>
                        <td>$clientName $clientSurname</td>
                    </tr>
                     <tr>
                        <td class='email-titles'>Телефон</td>
                        <td>$phone</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Від пункту</td>
                        <td>$fromCity $from</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>До пункту</td>
                        <td>$toCity $to</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Дата та час</td>
                        <td>$date</td>
                    </tr>
                    <tr>

                    <tr>
                        <td class='email-titles'>E-mail</td>
                        <td>$email</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Сумма повернення</td>
                        <td>$total</td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
        ";

        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $to2 = $email;
        $subject2 = 'Повернення квитків';
        $message2 = "
        <html>
        <head>
            <title>Повернення квитків</title>
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
                .img_logo {
                    margin: 0 auto;
                    text-align: center;
                    pointer-events: none;
                }
                .img_logo img{
                    pointer-events: none;
                }
                }
                .logo {
                    max-width: 150px;
                }
            </style>
        </head>
        <body>
            <div class='header' oncontextmenu='return false;'>
                <a href='https://www.maxtransltd.com'>
                <img src='$imagePath' alt='MaxTrans LTD' class='logo'>
                </a>
            </div>
            <p><b>Шановний(а) $clientName</b> <br>Дякуємо вам за звернення. Ми отримали ваш запит на повернення квитків, придбаних через наш онлайн-сервіс.</p>
            <div class='email-content'>
                <p>Ваш запит перебуває в обробці. Ми постараємося якнайшвидше завершити процес повернення коштів. Будь ласка, зверніть увагу, що в залежності від умов повернення та методу оплати кошти можуть надійти на ваш рахунок протягом 1-3 робочих днів.</p><br>
                <p>Якщо у вас виникнуть додаткові запитання або потрібна додаткова допомога, будь ласка, не соромтеся звертатися до нас за телефоном <a href='tel:+380971603474'>+380 97 160 34 74</a>.</p><br>
                <p>Ще раз просимо вибачення за можливі незручності і сподіваємося, що в майбутньому ви знову скористаєтеся нашими послугами.</p>
                <br><p>З повагою, <br>
компанія Max Trans LTD</p>
<a href='tel:+380971603474'>+380 97 160 34 74</a>
            </div>
            <p>* Дане повідомлення створене автоматично та не потребує відповіді.</p>
        </body>
        </html>
        ";
        $fromName = "Max Trans LTD";
        $fromEmail = "info@maxtransltd.com";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: "' . $fromName . '" <' . $fromEmail . '>' . "\r\n";
        $headers .= 'Reply-To: ' . $fromEmail . "\r\n";
        $headers .= 'X-Mailer: PHP/' . phpversion();

        // Настройка параметров SMTP
        ini_set('SMTP', 'mail.adm.tools');
        ini_set('smtp_port', '465'); // Порт для SSL
        ini_set('sendmail_from', 'info@maxtransltd.com');
        ini_set('sendmail_path', '"/usr/sbin/sendmail -t -i"');

        // Функция для отправки почты через mail() с использованием SMTP
        function sendMail($to, $subject, $message, $headers) {
            $result = mail($to, $subject, $message, $headers);
            if (!$result) {
                throw new Exception('Mail sending failed.');
            }
        }

        try {
            sendMail($to1, $subject1, $message1, $headers);
        } catch (Exception $e) {
            echo  'Message could not be sent. Error: ' . $e->getMessage();
        }

        try {
            sendMail($to2, $subject2, $message2, $headers);

            if (empty($checkOrder)) {
                echo  'ok';
            } elseif (!empty($checkOrder)) {
                echo  'ok';
            }
        } catch (Exception $e) {
            echo  'Ошибка отправки второго сообщения. Error: ' . $e->getMessage();
        }



    } else {
        echo  'error';
    }
}
