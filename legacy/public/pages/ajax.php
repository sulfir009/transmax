<?php
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])."/config.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/" . ADMIN_PANEL . "/engine/CDb.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])."/". ADMIN_PANEL ."/includes.php");

$db =  mysqli_connect(DB_HOST, DB_LOGIN, DB_PASS, DB_NAME);
mysqli_set_charset($db , "utf8" );
$toursRepository = new \App\Repository\Races\ToursRepository();
$Db = new CDb($db, $db);
$cleanPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
if (!isset($_POST) || empty($_POST) || !isset($_POST['request'])) {
    exit;
}


/* авторизация  */
if ($cleanPost['request'] === 'auth') {
    $user = $User->auth($cleanPost['login'], $cleanPost['password']);
    if ($user === false) {
        echo "email_not_found";
    } elseif ($user === null) {
        echo __('dictionary.MSG_MSG_LOGIN_NEVERNYE_DANNYE');
    } elseif ($user) {
        \App\Service\User::login();
        echo "ok";
    } else {
        echo __('dictionary.MSG_MSG_LOGIN_NEVERNYE_DANNYE');
    }
}


if ($cleanPost['request'] === 'resetPassMail') {

    $email = $cleanPost['email'];

    // Проверяем, существует ли пользователь с данным email
    $getemail = $Db->getAll("SELECT id FROM `" . DB_PREFIX . "_clients` WHERE email = '" . $email . "' LIMIT 1");
    if (!$getemail) {
        echo 'email_not_found';
        exit;
    }

    $clientId = $getemail['0']['id'];

    // Генерируем токен для сброса пароля
    $token = bin2hex(random_bytes(50));


    $saveToken = $Db->query("UPDATE `" .  DB_PREFIX . "_clients`  SET reset_token = '".$token."', token_date = NOW() WHERE id = '".$clientId."'");



    // Генерируем ссылку для сброса пароля
    $resetLink = $Router->writelink(93) . "?token={$token}&email=" . urlencode($email);

    function sendResetMail($resetLink, $email) {
        $to = $email;
        $subject = 'Відновлення паролю';
        $messageContent = "
        <html>
        <head>
            <title>Відновлення паролю</title>
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
                    <tr><td class='email-titles'>Для відновлення паролю перейдіть за <a href='https://www.maxtransltd.com$resetLink'>посиланням </a></td></tr>
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
            if (!mail($to, $subject, $messageContent, $headers)) {
                throw new Exception('Mail sending failed.');
            }
        } catch (Exception $e) {
            echo 'Message could not be sent. Error: ' . $e->getMessage();
        }
    }


    sendResetMail($resetLink, $email);
    echo 'ok';
    exit;

}

if ($cleanPost['request'] === 'newPass') {
    $email = $cleanPost['email'];
    $token = $cleanPost['token'];
    $password = $cleanPost['password'];

    // Проверка введенных данных
    if (!$email || !$token || strlen($password) < 6) {
        echo __('dictionary.MSG_MSG_LOGIN_NEVERNYE_DANNYE');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Получаем дату создания токена
    $tokenDate = $Db->getOne("SELECT token_date FROM `" .  DB_PREFIX . "_clients`  WHERE reset_token = '".$token."' AND email = '".$email."'");

    // Проверяем, не истек ли токен (30 минут)
    $tokenTimestamp = strtotime($tokenDate['token_date']);
    $currentTimestamp = time();
    $timeDifference = $currentTimestamp - $tokenTimestamp;

    if ($timeDifference > 1800) { // 1800 секунд = 30 минут
        echo 'token_expired';
        exit;
    }

    // Если все ок, обновляем пароль пользователя
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $updPass = $Db->query("UPDATE `" .  DB_PREFIX . "_clients`  SET password = '".$hashedPassword."' WHERE reset_token = '".$token."'");

    // Очищаем токен после использования
    $Db->query("UPDATE `" .  DB_PREFIX . "_clients`  SET reset_token = NULL, token_date = NULL WHERE email = '".$email."'");

    echo 'ok';
    exit;
}



/* регитрация */
if ($cleanPost['request'] === "register") {
    if (!filter_var($cleanPost['email'], FILTER_VALIDATE_EMAIL)) {
        exit(__('dictionary.MSG_MSG_REGISTER_NEVERNYJ_EMAIL'));
    }
    $getemail = $Db->getAll("SELECT id FROM `" . DB_PREFIX . "_clients` WHERE email = '" . $cleanPost['email'] . "' LIMIT 1");
    if ($getemail) {
        exit(__('dictionary.MSG_MSG_REGISTER_ETOT_E-MAIL_UZHE_ZANYAT'));
    }
    if (isset($_SESSION['order']['save_data']) && $_SESSION['order']['save_data'] == 1){
        $arFields = array('name', 'email','second_name','phone','patronymic','birth_date','phone_code');
        $arData = array("'" . $cleanPost['name'] . "'",
            "'" . $cleanPost['email'] . "'",
            "'" . $_SESSION['order']['family_name'] . "'",
            "'" . $_SESSION['order']['phone'] . "'",
            "'" . $_SESSION['order']['patronymic'] . "'",
            "'" . $_SESSION['order']['birth_date'] . "'",
            "'" . $_SESSION['order']['phone_code'] . "'");
    }else{
        $arFields = array('name', 'email');
        $arData = array("'" . $cleanPost['name'] . "'", "'" . $cleanPost['email'] . "'");
    }

    if ($User->register($arFields, $arData, $cleanPost['email'], $cleanPost['password'])) {
        echo "ok";
    } else {
        echo __('dictionary.MSG_MSG_REGISTER_NE_UDALOSI_ZAREGISTRIROVATISYA');
    }
}

if ($cleanPost['request'] === 'exit'){
    \App\Service\User::logout();
    unset($_SESSION['user']);
}

if ($cleanPost['request'] === 'more_buses') {
    $getBuses = $Db->getAll("SELECT id,image,seats_qty,title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_buses` WHERE active = '1' ORDER BY sort DESC LIMIT " . (int)$cleanPost['current'] . " ,6");
    foreach ($getBuses as $k => $bus) {
        ?>
        <div class="bus flex-row gap-30">
            <div class="col-lg-6">
                <div class="bus_img">
                    <img src="<?= asset('images/legacy/upload/buses/' . $bus['image']); ?>" alt="bus" class="fit_img">
                </div>
            </div>
            <div class="col-lg-6">
                <div class="bus_info">
                    <div class="bus_title h2_title">
                        <?= $bus['title'] ?>
                    </div>
                    <div class="bus_seats flex_ac h4_title">
                        <?= __('dictionary.MSG_MSG_BUSES_KILIKISTI_MISCI') ?>
                        <span class="total_seats h2_title">
                                        <?= $bus['seats_qty'] ?>
                                    </span>
                    </div>
                    <div class="bus_info_delimiter"></div>
                    <div class="bus_options">
                        <div class="flex-row gap-30">
                            <? $getBusAdditionalOptions = $Db->getAll("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE active = '1'
                                        AND id IN(SELECT option_id FROM `" . DB_PREFIX . "_buses_options_connector` WHERE bus_id = '" . $bus['id'] . "' ) ORDER BY sort DESC");
                            foreach ($getBusAdditionalOptions as $key => $additionalOption) { ?>
                                <div class="col-sm-4 col-xs-6">
                                    <div class="bus_option flex_ac par">
                                        <div class="check_imitation"></div>
                                        <?= $additionalOption['title'] ?>
                                    </div>
                                </div>
                            <? } ?>
                        </div>
                    </div>
                    <button class="order_bus_link h4_title flex_ac blue_btn"
                            onclick="toggleOrderBus('<?= $bus['id'] ?>')">
                        <?= __('dictionary.MSG_MSG_BUSES_ZAMOVITI_AVTOBUS') ?>
                    </button>
                </div>
            </div>
        </div>
    <? }
}

if ($cleanPost['request'] === 'feedback') {
    $fieldName = $fieldValue = array();
    $fieldName[] = 'date';
    $fieldValue[] = 'NOW()';
    $fieldName[] = 'name';
    $fieldValue[] = '"' . $cleanPost['name'] . '"';
    $fieldName[] = 'email';
    $fieldValue[] = '"' . $cleanPost['email'] . '"';
    $fieldName[] = 'phone';
    $fieldValue[] = '"' . $cleanPost['phone'] . '"';
    $fieldName[] = 'message';
    $fieldValue[] = '"' . $cleanPost['message'] . '"';

    $addFeedback = $Db->query("INSERT INTO `" . DB_PREFIX . "_messages` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");
    if ($addFeedback) {
        echo 'ok';
    } else {
        echo 'err';
    }
}

if ($cleanPost['request'] === 'orderBus') {
    $fieldName = $fieldValue = array();
    $fieldName[] = 'date';
    $fieldValue[] = 'NOW()';
    $fieldName[] = 'name';
    $fieldValue[] = '"' . $cleanPost['name'] . '"';
    $fieldName[] = 'phone';
    $fieldValue[] = '"' . $cleanPost['phone'] . '"';
    $fieldName[] = 'message';
    $fieldValue[] = '"' . $cleanPost['message'] . '"';

    $addFeedback = $Db->query("INSERT INTO `" . DB_PREFIX . "_bus_orders` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");

    $phone = $cleanPost['phone'];
    $message = $cleanPost['message'];
    $name = $cleanPost['name'];


    function sendCallbackMail($name, $phone, $message, $date) {
        $to = env('MAIL_ADMIN');  //max210183@ukr.net
        $subject = 'Нове замовлення автобусу';
        $messageContent = "
        <html>
        <head>
            <title>Нове замовлення автобусу:</title>
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
                    <tr><td class='email-titles'>Замовник</td><td>$name</td></tr>
                    <tr><td class='email-titles'>Телефон</td><td>$phone</td></tr>
                    <tr><td class='email-titles'>Повідомлення</td><td>$message</td></tr>
                    <tr><td class='email-titles'>Заявка залишена в</td><td>$date</td></tr>
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
            if (!mail($to, $subject, $messageContent, $headers)) {
                throw new Exception('Mail sending failed.');
            }
        } catch (Exception $e) {
            echo 'Message could not be sent. Error: ' . $e->getMessage();
        }
    }

    if ($addFeedback) {
        sendCallbackMail($name, $phone, $message, date('Y-m-d H:i:s'));
        echo 'ok';
    } else {
        echo 'err';
    }
}

if ($cleanPost['request'] === 'callback') {

    $departure = (int)$cleanPost['departure'];
    $arrival = (int)$cleanPost['arrival'];
    $phone = $cleanPost['phone'];
    $message = $cleanPost['message'];


    $addCallback = $Db->query(
        "INSERT INTO `" . DB_PREFIX . "_callback` (date, departure, arrival, phone, message) VALUES (NOW(), '$departure', '$arrival', '$phone', '$message')"
    );

    // Получение данных станций
    $getStations = $Db->getAll("SELECT id, title_uk FROM `" .  DB_PREFIX . "_cities`  WHERE id IN ($departure, $arrival)");


    foreach ($getStations as $station) {
        if ($station['id'] == $departure) {
            $departureTitle = $station['title_uk'];
        } elseif ($station['id'] == $arrival) {
            $arrivalTitle = $station['title_uk'];
        }
    }

    // Функция для отправки почты
    function sendCallbackMail($departure, $arrival, $phone, $message, $date) {
        $to = env('MAIL_ADMIN');  //max210183@ukr.net
        $subject = 'Нова заявка на дзвінок';
        $messageContent = "
        <html>
        <head>
            <title>Нова заявка на дзвінок:</title>
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
                    <tr><td class='email-titles'>Від пункту</td><td>$departure</td></tr>
                    <tr><td class='email-titles'>До пункту</td><td>$arrival</td></tr>
                    <tr><td class='email-titles'>Телефон</td><td>$phone</td></tr>
                    <tr><td class='email-titles'>Повідомлення</td><td>$message</td></tr>
                    <tr><td class='email-titles'>Заявка залишена в</td><td>$date</td></tr>
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
            if (!mail($to, $subject, $messageContent, $headers)) {
                throw new Exception('Mail sending failed.');
            }
        } catch (Exception $e) {
            echo 'Message could not be sent. Error: ' . $e->getMessage();
        }
    }

    if ($addCallback) {
        sendCallbackMail($departureTitle, $arrivalTitle, $phone, $message, date('Y-m-d H:i:s'));
        echo 'ok';
    } else {
        echo 'err';
    }
}


if ($cleanPost['request'] === 'remember_ticket') {
    $tourId = (int)$cleanPost['id'];
    $from = (int)$cleanPost['departure'];
    $to = (int)$cleanPost['arrival'];
    $date = implode('-',array_map('intval',explode('-',$cleanPost['date'])));
    $passangers = (int)$cleanPost['passengers'];
    $canOrderTicket = true;
    $fromCity = $Db->getOne("SELECT title_".$Router->getLang()." AS title FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['fromCity']."' ");
    $toCity = $Db->getOne("SELECT title_".$Router->getLang()." AS title FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['toCity']."' ");
    $fromCityId = $Db->getOne("SELECT section_id FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['departure']."' ");
    $toCityId = $Db->getOne("SELECT section_id FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['arrival']."' ");
    $toursDeparture = $Db->getOne("SELECT departure FROM `" .  DB_PREFIX . "_tours`  WHERE id='".$tourId."'");
    $toursClosed = $Db->getOne("SELECT departure_closed, stops_closed FROM `" .  DB_PREFIX . "_tours`  WHERE id='".$tourId."'");
    $toursDepartureClosed = strtotime($toursClosed['departure_closed']);
    $toursStopsClosed = strtotime($toursClosed['stops_closed']);
    if (strtotime($date) == strtotime(date('Y-m-d',time()))){
        $checkTicketDeparture = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`  WHERE tour_id = '".(int)$cleanPost['id']."' AND stop_id = '".$from."' ");
        $currentDate = date('Y-m-d');
        $ticketDepartureTime = strtotime($currentDate . ' ' . $checkTicketDeparture['departure_time']);
        $toursStopsClosedTime = strtotime($toursClosed['stops_closed']);
        $toursDepartureClosedTime = strtotime($toursClosed['departure_closed']);
        $currentTime = time();

        if ($from === $toursDeparture) {
            if ($currentTime > $ticketDepartureTime - $toursClosed['departure_closed']) {
                $canOrderTicket = false;
                echo $ticketDepartureTime - $toursClosed['departure_closed'];
            }
        } else {
            if ($currentTime > $ticketDepartureTime - $toursClosed['departure_closed']) {
                $canOrderTicket = false;
            }
        }
    }

    if ($canOrderTicket){
        session_start();
        $ticketDateArr = explode('-',$cleanPost['date']);
        $ticketDate = array();
        foreach ($ticketDateArr AS $k=>$value){
            $ticketDate[] = (int)$value;
        }
        $_SESSION['order']['tour_id'] = $tourId;
        $_SESSION['order']['from'] = $from;
        $_SESSION['order']['to'] = $to;
        $_SESSION['order']['date'] = $date;
        $_SESSION['order']['passengers'] = $passangers;
        $_SESSION['order']['fromCity'] = $fromCity['title'];
        $_SESSION['order']['toCity'] = $toCity['title'];
        $_SESSION['order']['fromCityId'] = $fromCityId['section_id'];
        $_SESSION['order']['toCityId'] = $toCityId['section_id'];
        echo 'ok';
    }else{
        echo 'late';
    }
}

if ($cleanPost['request'] === 'check_OrderTicket') {
    $tourId = (int)$_SESSION['order']['tour_id'];
    $from = (int)$_SESSION['order']['from'];
    $date = $_SESSION['order']['date'];
    $passengers = (int)$_SESSION['order']['passengers'];

    $canOrderTicket = true;


    function generateOrderId()
    {
        return uniqid('order_', true);
    }

    $toursClosed = $Db->getOne("SELECT departure_closed, stops_closed FROM `" .  DB_PREFIX . "_tours`  WHERE id='".$tourId."'");
    $toursDepartureClosed = strtotime($toursClosed['departure_closed']);
    $toursStopsClosed = strtotime($toursClosed['stops_closed']);
    if (strtotime($date) == strtotime(date('Y-m-d',time()))){
        $checkTicketDeparture = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`  WHERE tour_id = '".(int)$tourId."' AND stop_id = '".$from."' ");
        if ($from === $toursDeparture) {
            if (strtotime($checkTicketDeparture['departure_time']) < strtotime(date('H:i:s',time()))){
                $canOrderTicket = false;
            }
        } else {
            $hors = (int)(strtotime($checkTicketDeparture['departure_time']) - strtotime(date('H:i:s',time()))) / 3600;
            if ($hors < 1){
                $canOrderTicket = false;
            }
        }
    }

    if ($canOrderTicket) {
        // Проверка наличия тура на указанную дату
        $tourSale = $Db->getOne("SELECT * FROM `" .  DB_PREFIX . "_tours_sales`  WHERE tour_id = '{$tourId}' AND tour_date = '{$date}'");

        if ($tourSale) {
            // Если тур существует, проверить количество свободных билетов
            if ($tourSale['free_tickets'] >= $passengers  && $tourSale['active'] == 1) {
                // Обновить количество свободных билетов
                $Db->query("UPDATE `" .  DB_PREFIX . "_tours_sales`  SET free_tickets = free_tickets - {$passengers} WHERE id = '{$tourSale['id']}'");
                $order_id = generateOrderId();
                $_SESSION['order']['order_id'] = $order_id;
                echo 'ok';
            } else {
                // Недостаточно билетов
                echo 'soldout';
            }
        } else {
            // Получить ID автобуса для данного тура
            $busId = $Db->getOne("SELECT bus FROM `" .  DB_PREFIX . "_tours`  WHERE id = '{$tourId}'");
            // Получить количество мест в автобусе
            $busCapacity = $Db->getOne("SELECT seats_qty FROM `" .  DB_PREFIX . "_buses`  WHERE id = '{$busId}'");
            // Вставить новую запись в таблицу _tours_sales
            $Db->query("INSERT INTO `" .  DB_PREFIX . "_tours_sales`  (tour_id, free_tickets, tour_date) VALUES ('{$tourId}', '{$busCapacity['seats_qty']}' - {$passengers}, '{$date}')");
            echo 'ok';
        }
    } else {
        echo 'late';
    }
    exit;
}



if ($cleanPost['request'] === 'remember_ticket_without_date') {
    $tourId = (int)$cleanPost['id'];
    $from = (int)$cleanPost['departure'];
    $to = (int)$cleanPost['arrival'];
    $passangers = (int)$cleanPost['passengers'];
    $date = implode('-',array_map('intval',explode('-',$cleanPost['date'])));
    $fromCity = $Db->getOne("SELECT title_".$Router->getLang()." AS title FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['fromCity']."' ");
    $toCity = $Db->getOne("SELECT title_".$Router->getLang()." AS title FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['toCity']."' ");
    $fromCityId = $Db->getOne("SELECT section_id FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['departure']."' ");
    $toCityId = $Db->getOne("SELECT section_id FROM `" .  DB_PREFIX . "_cities`  WHERE id = '".(int)$cleanPost['arrival']."' ");
    session_start();
    $ticketDateArr = explode('-',$cleanPost['date']);
    $ticketDate = array();
    foreach ($ticketDateArr AS $k=>$value){
        $ticketDate[] = (int)$value;
    }
    $_SESSION['order']['tour_id'] = $tourId;
    $_SESSION['order']['from'] = $from;
    $_SESSION['order']['to'] = $to;
    $_SESSION['order']['date'] = $date;
    $_SESSION['order']['passengers'] = $passangers;
    $_SESSION['order']['fromCity'] = $fromCity['title'];
    $_SESSION['order']['toCity'] = $toCity['title'];
    $_SESSION['order']['fromCityId'] = $fromCityId['section_id'];
    $_SESSION['order']['toCityId'] = $toCityId['section_id'];
    echo 'ok';

}

if ($cleanPost['request'] === 'clear_session_data') {
    // Начинаем сессию
    session_start();

    // Удаляем данные из сессии
    unset($_SESSION['order']);
    unset($_SESSION['filter']);

    // Возвращаем успешный ответ
    echo 'ok';
}

if ($cleanPost['request'] === 'route_details') {
    ?>
    <div class="route_details_popup_content_wrapper">
        <div class="close_route_details_wrapper">
            <button class="close_menu" onclick="toggleRouteDetails('0')">
                <img src="<?= asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
        </div>
        <div class="route_details_popup_content">
            <div class="route_details_title route_details_block_title h3_title flex_ac" onclick="toggleInfoBlock(this)">
                <?= __('dictionary.MSG_MSG_TICKETS_MARSHRUT') ?>
                <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
            </div>
            <div class="route_details_points">
                <?
                $ticketPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop = '".(int)$cleanPost['departure']."' AND to_stop = '".(int)$cleanPost['arrival']."' AND tour_id = '".(int)$cleanPost['id']."' ");
                ?>
                <? $getStops = $Db->getAll("SELECT stop_id,arrival_time FROM `" . DB_PREFIX . "_tours_stops`
                WHERE tour_id = '" . (int)$cleanPost['id'] . "' ORDER BY stop_num ASC ");
                $transferStop = $Db->getAll("SELECT transfer_station_id AS transfer_station FROM `" .  DB_PREFIX . "_tours_transfers`  tt
    WHERE tt.tour_id = '".(int)$cleanPost['id']."' ");

                foreach ($getStops as $k => $stop) {
                    $stopTitle = $Db->getOne("SELECT section_id,title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_cities`
                    WHERE id = '" . $stop['stop_id'] . "' ");
                    $stopCity = $Db->getOne("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $stopTitle['section_id'] . "' ");
                    $stopPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE tour_id = '".(int)$cleanPost['id']."' AND (from_stop = '".$stop['stop_id']."' OR to_stop = '".$stop['stop_id']."') ");

                    $isTransfer = false;
                    foreach ($transferStop as $transfer) {
                        if ($stop['stop_id'] == $transfer['transfer_station']) {
                            $isTransfer = true;
                            break;
                        }
                    }
                    ?>
                    <div class="route_details_point flex_ac <?if ($k == 0){echo 'active';}?>">
                        <div class="route_details_point_time par">
                            <?= date('H:i', strtotime($stop['arrival_time'])) ?>
                        </div>
                        <div class="route_details_point_title par">
                            <?= $stopCity['title'] . ' ' . $stopTitle['title'] ?><?if ($isTransfer) { ?>
                                <img class="transfer_icon" src="<?= asset('images/legacy/transfer_white.svg'); ?>" alt="Пересадка">
                                <?= __('dictionary.MSG_MSG_SCHEDULE_TRANSFER') . ''; ?>
                            <?php }?>
                            <?if (!$stopPrice || $stopPrice['price'] == 0){?>
                                <?= __('dictionary.MSG_MSG_TICKETS_NET_POSADKI_/_VYSADKI_PASSAZHIROV')?>
                            <?}?>
                        </div>
                    </div>
                    <?
                } ?>
            </div>
            <div class="route_details_bus_services_wrapper">
                <div class="route_details_bus_services_title route_details_block_title h3_title flex_ac"
                     onclick="toggleInfoBlock(this)">
                    <?= __('dictionary.MSG_MSG_TICKETS_POSLUGI_V_AVTOBUSI'); ?>
                    <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
                </div>
                <? $getBusOptions = $Db->getAll("SELECT option_id FROM `" . DB_PREFIX . "_buses_options_connector` WHERE bus_id = (SELECT bus FROM `" . DB_PREFIX . "_tours` WHERE id = '" . $cleanPost['id'] . "') ");
                $busOptionsArrays = array_chunk($getBusOptions, 3); ?>
                <div class="route_details_bus_services flex-row gap-24">
                    <? foreach ($busOptionsArrays as $k => $busOptions) {
                        ?>
                        <div class="col-sm-4 col-xs-12">
                            <? foreach ($busOptions as $k => $busOption) {
                                $busOptionTitle = $Db->getOne("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE id = '" . $busOption['option_id'] . "' "); ?>
                                <div class="route_details_service par active">
                                    <?= $busOptionTitle['title'] ?>
                                </div>
                                <?
                            } ?>
                        </div>
                        <?
                    } ?>
                </div>
            </div>
            <div class="route_details_rules">
                <div class="route_details_rules_title route_details_block_title h3_title flex_ac"
                     onclick="toggleInfoBlock(this)">
                    <?= __('dictionary.MSG_MSG_TICKETS_PRAVILA_POVERNENNYA') ?>
                    <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
                </div>
                <div class="route_details_rules_txt par">
                    <? $returnTxt = $Db->getOne("SELECT text_" . $Router->getLang() . " AS text FROM `" . DB_PREFIX . "_txt_blocks` WHERE id = '7' ") ?>
                    <?= $returnTxt['text'] ?>
                </div>
            </div>
            <div class="route_details_totals flex_ac">
                <div class="route_details_route_price h2_title">
                    <?= $ticketPrice['price'] . ' ' . __('dictionary.MSG_MSG_TICKETS_GRN'); ?>
                </div>
                <button class="blue_btn flex_ac route_details_buy_btn h4_title"
                        onclick="buyTicket(this,'<?= $cleanPost['id'] ?>','<?=(int)$cleanPost['departure']?>','<?=(int)$cleanPost['arrival']?>')">
                    <?= __('dictionary.MSG_MSG_TICKETS_KUPITI_KVITOK') ?>
                </button>
            </div>
        </div>
    </div>
    <?
}

if ($cleanPost['request'] === 'route_details_private') {
    ?>
    <div class="route_details_popup_content_wrapper">
        <div class="close_route_details_wrapper">
            <button class="close_menu" onclick="toggleRouteDetailsPrivate('0')">
                <img src="<?= asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
        </div>
        <div class="route_details_popup_content">
            <div class="route_details_title route_details_block_title h3_title flex_ac" onclick="toggleInfoBlock(this)">
                <?= __('dictionary.MSG_MSG_TICKETS_MARSHRUT') ?>
                <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
            </div>
            <div class="route_details_points">
                <?
                $ticketPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop = '".(int)$cleanPost['departure']."' AND to_stop = '".(int)$cleanPost['arrival']."' AND tour_id = '".(int)$cleanPost['id']."' ");
                $getStops = $Db->getAll("SELECT stop_id,arrival_time FROM `" . DB_PREFIX . "_tours_stops` WHERE `tour_id` = '" . $cleanPost['id'] . "' ORDER BY stop_num ASC ");
                foreach ($getStops as $k => $stop) {
                    $stopTitle = $Db->getOne("SELECT section_id,title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $stop['stop_id'] . "' ");
                    $stopCity = $Db->getOne("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $stopTitle['section_id'] . "' ") ?>
                    <div class="route_details_point flex_ac <?if ($k == 0){echo 'active';}?>">
                        <div class="route_details_point_time par">
                            <?= date('H:i', strtotime($stop['arrival_time'])) ?>
                        </div>
                        <div class="route_details_point_title par">
                            <?= $stopCity['title'] . ' ' . $stopTitle['title'] ?>
                        </div>
                    </div>
                    <?
                } ?>
            </div>
            <div class="route_details_bus_services_wrapper">
                <div class="route_details_bus_services_title route_details_block_title h3_title flex_ac"
                     onclick="toggleInfoBlock(this)">
                    <?= __('dictionary.MSG_MSG_TICKETS_POSLUGI_V_AVTOBUSI') ?>
                    <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
                </div>
                <? $getBusOptions = $Db->getAll("SELECT option_id FROM `" . DB_PREFIX . "_buses_options_connector` WHERE bus_id = (SELECT bus FROM `" . DB_PREFIX . "_tours` WHERE id = '" . $cleanPost['id'] . "') ");
                $busOptionsArrays = array_chunk($getBusOptions, 3); ?>
                <div class="route_details_bus_services flex-row gap-24">
                    <? foreach ($busOptionsArrays as $k => $busOptions) {
                        ?>
                        <div class="col-sm-4 col-xs-12">
                            <? foreach ($busOptions as $k => $busOption) {
                                $busOptionTitle = $Db->getOne("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE id = '" . $busOption['option_id'] . "' "); ?>
                                <div class="route_details_service par active">
                                    <?= $busOptionTitle['title'] ?>
                                </div>
                                <?
                            } ?>
                        </div>
                        <?
                    } ?>
                </div>
            </div>
            <div class="route_details_rules">
                <div class="route_details_rules_title route_details_block_title h3_title flex_ac"
                     onclick="toggleInfoBlock(this)">
                    <?= __('dictionary.MSG_MSG_TICKETS_PRAVILA_POVERNENNYA') ?>
                    <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
                </div>
                <div class="route_details_rules_txt par">
                    <? $returnTxt = $Db->getOne("SELECT text_" . $Router->getLang() . " AS text FROM `" . DB_PREFIX . "_txt_blocks` WHERE id = '7' ") ?>
                    <?= $returnTxt['text'] ?>
                </div>
            </div>
            <div class="route_details_totals flex_ac">
                <div class="route_details_route_price h2_title">
                    <?= $ticketPrice['price'] . ' ' . __('dictionary.MSG_MSG_TICKETS_GRN') ?>
                </div>
            </div>
        </div>
    </div>
    <?
}

if ($cleanPost['request'] === 'history_route_details') { ?>
    <div class="route_details_popup_content_wrapper">
        <div class="close_route_details_wrapper">
            <button class="close_menu" onclick="toggleRouteDetailsHistory('0')">
                <img src="<?= asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
        </div>
        <div class="route_details_popup_content">
            <div class="route_details_title route_details_block_title h3_title flex_ac" onclick="toggleInfoBlock(this)">
                <?= __('dictionary.MSG_MSG_TICKETS_MARSHRUT') ?>
                <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
            </div>
            <div class="route_details_points">
                <?
                $ticketPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop = '".(int)$cleanPost['departure']."' AND to_stop = '".(int)$cleanPost['arrival']."' AND tour_id = '".(int)$cleanPost['id']."' ");
                $getStops = $Db->getAll("SELECT stop_id,arrival_time FROM `" . DB_PREFIX . "_tours_stops` WHERE `tour_id` = '" . $cleanPost['id'] . "' ORDER BY stop_num ASC "); ?>
                <?foreach ($getStops as $k => $stop) {
                    $stopTitle = $Db->getOne("SELECT section_id,title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $stop['stop_id'] . "' ");
                    $stopCity = $Db->getOne("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_cities` WHERE id = '" . $stopTitle['section_id'] . "' ") ?>
                    <div class="route_details_point flex_ac">
                        <div class="route_details_point_time par">
                            <?= date('H:i', strtotime($stop['arrival_time'])) ?>
                        </div>
                        <div class="route_details_point_title par">
                            <?= $stopCity['title'] . ' ' . $stopTitle['title'] ?>
                        </div>
                    </div>
                    <?
                } ?>
            </div>
            <div class="route_details_bus_services_wrapper">
                <div class="route_details_bus_services_title route_details_block_title h3_title flex_ac" onclick="toggleInfoBlock(this)">
                    <?= __('dictionary.MSG_MSG_TICKETS_POSLUGI_V_AVTOBUSI') ?>
                    <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
                </div>
                <? $getBusOptions = $Db->getAll("SELECT option_id FROM `" . DB_PREFIX . "_buses_options_connector` WHERE bus_id = (SELECT bus FROM `" . DB_PREFIX . "_tours` WHERE id = '" . $cleanPost['id'] . "') ");
                $busOptionsArrays = array_chunk($getBusOptions, 3); ?>
                <div class="route_details_bus_services flex-row gap-24">
                    <? foreach ($busOptionsArrays as $k => $busOptions) {
                        ?>
                        <div class="col-sm-4 col-xs-12">
                            <? foreach ($busOptions as $k => $busOption) {
                                $busOptionTitle = $Db->getOne("SELECT title_" . $Router->getLang() . " AS title FROM `" . DB_PREFIX . "_buses_options` WHERE id = '" . $busOption['option_id'] . "' "); ?>
                                <div class="route_details_service par active">
                                    <?= $busOptionTitle['title'] ?>
                                </div>
                                <?
                            } ?>
                        </div>
                        <?
                    } ?>
                </div>
            </div>
            <div class="route_details_rules">
                <div class="route_details_rules_title route_details_block_title h3_title flex_ac"
                     onclick="toggleInfoBlock(this)">
                    <?= __('dictionary.MSG_MSG_TICKETS_PRAVILA_POVERNENNYA') ?>
                    <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow left">
                </div>
                <div class="route_details_rules_txt par">
                    <? $returnTxt = $Db->getOne("SELECT text_" . $Router->getLang() . " AS text FROM `" . DB_PREFIX . "_txt_blocks` WHERE id = '7' ") ?>
                    <?= $returnTxt['text'] ?>
                </div>
            </div>
            <div class="route_details_totals flex_ac">
                <div class="route_details_route_price h2_title">
                    <?= $ticketPrice['price'] . ' ' . __('dictionary.MSG_MSG_TICKETS_GRN') ?>
                </div>
            </div>
        </div>
    </div>
    <?
}

if ($cleanPost['request'] === 'filter') {

    $filterParams = '';
    if ($cleanPost['stops']) {
        switch ((int)$cleanPost['stops']) {
            case 0:
                $filterParams .= '';
                break;
            case 1:
                $filterParams .= " AND t.id NOT IN(SELECT DISTINCT(tour_id) FROM " . DB_PREFIX . "_tours_transfers)";
                break;
            case 2:
                $filterParams .= " AND t.id IN(SELECT DISTINCT(tour_id) FROM " . DB_PREFIX . "_tours_transfers)";
                break;
        }
    }
    $departureAdditionalQuery = $arrivalAdditionalQuery = $arrivalAdditionalQueryForPrice = $departureAdditionalQueryForPrice = "";
    if ((int)$cleanPost['arrival_city'] > 0){
        $filterParams .= " AND (t.arrival = '".(int)$cleanPost['arrival_city']."' OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE to_stop
                IN(SELECT id FROM `" .  DB_PREFIX . "_cities`  WHERE section_id = '".(int)$cleanPost['arrival_city']."' )))";
        $arrivalAdditionalQuery = " AND stop_id IN(SELECT id FROM `" .  DB_PREFIX . "_cities`  WHERE section_id = '".(int)$cleanPost['arrival_city']."' )";
        $arrivalAdditionalQueryForPrice = " AND to_stop IN(SELECT id FROM `" .  DB_PREFIX . "_cities`  WHERE section_id = '".(int)$cleanPost['arrival_city']."' )";
    }
    if ((int)$cleanPost['departure_city'] > 0){
        $filterParams .= " AND (t.departure = '".(int)$cleanPost['departure_city']."' OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop
                IN(SELECT id FROM `" .  DB_PREFIX . "_cities`  WHERE section_id = '".(int)$cleanPost['departure_city']."' )))";
        $departureAdditionalQuery = " AND stop_id IN(SELECT id FROM `" .  DB_PREFIX . "_cities`  WHERE section_id = '".(int)$cleanPost['departure_city']."' )";
        $departureAdditionalQueryForPrice = " AND from_stop IN(SELECT id FROM `" .  DB_PREFIX . "_cities`  WHERE section_id = '".(int)$cleanPost['departure_city']."' )";
    }

    if (isset($cleanPost['departure_time']) ?? $cleanPost['departure_time'] > 0) {
        $tourIdsByDepartureTime = [];
        $cleanDepartureTime = array_map('intval',$cleanPost['departure_time']);
        $departureIntervals = [];
        foreach ($cleanDepartureTime AS $k=>$departureTime) {
            switch ($departureTime) {
                case 2:
                    $departureIntervals[] = "TIME(departure_time) BETWEEN '06:00' AND '12:00'";
                    break;
                case 3:
                    $departureIntervals[] = "TIME(departure_time) BETWEEN '12:00' AND '18:00'";
                    break;
                case 4:
                    $departureIntervals[] = "TIME(departure_time) BETWEEN '18:00' AND '23:59'";
                    break;
                case 5:
                    $departureIntervals[] = "TIME(departure_time) BETWEEN '23:59' AND '06:00'";
                    break;
            }
        }
        $getTourIdsByDepartureTime = $Db->getAll("SELECT DISTINCT(tour_id) FROM `" .  DB_PREFIX . "_tours_stops`  WHERE (".implode(' OR ',$departureIntervals).") $departureAdditionalQuery GROUP BY tour_id ORDER BY stop_num ASC");

        foreach ($getTourIdsByDepartureTime AS $k=>$tourIdByDepartureTime){
            $tourIdsByDepartureTime[] = $tourIdByDepartureTime['tour_id'];
        }
        $filterParams .= " AND t.id IN(".implode(',',$tourIdsByDepartureTime).")";
    }

    if (isset($cleanPost['arrival_time']) && (int)$cleanPost['arrival_time'] > 0) {
        $tourIdsByArrivalTime = [];
        $cleanArrivalTime = array_map('intval',$cleanPost['arrival_time']);
        $arrivalIntervals = [];
        foreach ($cleanArrivalTime AS $k=>$arrivalTime) {
            switch ($arrivalTime) {
                case 2:
                    $arrivalIntervals[] = "TIME(arrival_time) BETWEEN '06:00' AND '12:00'";
                    break;
                case 3:
                    $arrivalIntervals[] = "TIME(arrival_time) BETWEEN '12:00' AND '18:00'";
                    break;
                case 4:
                    $arrivalIntervals[] = "TIME(arrival_time) BETWEEN '18:00' AND '23:59'";
                    break;
                case 5:
                    $arrivalIntervals[] = "TIME(arrival_time) BETWEEN '23:59' AND '06:00'";
                    break;
            }
        }
        $getTourIdsByArrivalTime = $Db->getAll("SELECT DISTINCT(tour_id) FROM `" .  DB_PREFIX . "_tours_stops`  WHERE (".implode(' OR ',$arrivalIntervals).") $arrivalAdditionalQuery GROUP BY tour_id ORDER BY stop_num ASC");
        foreach ($getTourIdsByArrivalTime AS $k=>$tourIdByArrivalTime){
            $tourIdsByArrivalTime[] = $tourIdByArrivalTime['tour_id'];
        }
        $filterParams .= " AND t.id IN(".implode(',',$tourIdsByArrivalTime).")";
    }

    if (isset($cleanPost['departure_station']) && $cleanPost['departure_station']) {
        $tourIdsByDepartureStation = [];
        $cleanDepartureStations = array_map('intval',$cleanPost['departure_station']);
        $getTourIdsByDepartureStation = $Db->getAll("SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop IN(".implode(',',$cleanDepartureStations).") ");
        foreach ($getTourIdsByDepartureStation AS $k=>$tourIdByDepartureStation){
            $tourIdsByDepartureStation[] = $tourIdByDepartureStation['tour_id'];
        }
        $filterParams .= " AND t.id IN(".implode(',',$tourIdsByDepartureStation).")";
    }
    if (isset($cleanPost['arrival_station']) && $cleanPost['arrival_station']) {
        $tourIdsByArrivalStation = [];
        $cleanArrivalStations = array_map('intval',$cleanPost['arrival_station']);
        $getTourIdsByArrivalStation = $Db->getAll("SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE to_stop IN(".implode(',',$cleanArrivalStations).") ");
        foreach ($getTourIdsByArrivalStation AS $k=>$tourIdByArrivalStation){
            $tourIdsByArrivalStation[] = $tourIdByArrivalStation['tour_id'];
        }
        $filterParams .= " AND t.id IN(".implode(',',$tourIdsByArrivalStation).")";
    }

    $minPrice = (int)$cleanPost['min_price'];
    $maxPrice = (int)$cleanPost['max_price'];

    $tourIdsByPrice = [];
    $getTourIdsByPrice = $Db->getAll("SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE price BETWEEN ".$minPrice." AND ".$maxPrice." $departureAdditionalQueryForPrice $arrivalAdditionalQueryForPrice");
    foreach ($getTourIdsByPrice AS $k=>$tourIdByPrice){
        $tourIdsByPrice[] = $tourIdByPrice['tour_id'];
    }
    $filterParams .= " AND t.id IN(".implode(',',$tourIdsByPrice).")";

    if (isset($cleanPost['comfort']) && $cleanPost['comfort']) {
        $filterParams .= " AND t.bus IN(SELECT DISTINCT(bus_id) FROM `" .  DB_PREFIX . "_buses_options_connector`  WHERE option_id IN(" . implode(',', $cleanPost['comfort']) . ") )";
    }

    $filterDate = $cleanPost['date'];
    if ($cleanPost['date'] == 'today'){
        $filterDate = date('Y-m-d',time());
    }

    $weekDay = date('N',time());

    if ($cleanPost['date'] != 'today'){
        $weekDay = date('N',strtotime($filterDate));
        $filterParams .= " AND t.days LIKE '%".$weekDay."%' ";
    }

    $sortField = '';
    $sortDirection = '';

    switch((int)$cleanPost['sort_option']){
        case 1:
            $sortField = 'tsp.price';
            break;
        case 2:
            $sortField = 'ts.departure_time';
            break;
        case 3:
            $sortField = 'ts.arrival_time';
            break;
        case 4:
            $sortField = 't.popular';
            break;
    }

    switch ((int)$cleanPost['sort_direction']){
        case 1:
            $sortDirection = 'DESC';
            break;
        case 2:
            $sortDirection = 'ASC';
    }

    $getTickets = $Db->getall("SELECT DISTINCT(t.id),t.departure,t.arrival,t.days,
            dc.title_".$Router->getLang()." AS departure_city,
            dc.section_id AS departure_city_section_id,
            ac.title_".$Router->getLang()." AS arrival_city,
            ac.section_id AS arrival_city_section_id,
            b.title_" . $Router->getLang() . " AS bus_title
            FROM `" . DB_PREFIX . "_tours` t
            LEFT JOIN `" .  DB_PREFIX . "_cities`  dc ON dc.id = t.departure
            LEFT JOIN `" .  DB_PREFIX . "_cities`  ac ON ac.id = t.arrival
            LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
            LEFT JOIN `" .  DB_PREFIX . "_tours_stops_prices`  tsp ON tsp.tour_id = t.id
            LEFT JOIN `" .  DB_PREFIX . "_tours_stops`  ts ON ts.tour_id = t.id
    WHERE t.active = '1' $filterParams
    ORDER BY $sortField ".$sortDirection);

    if (is_array($getTickets) && count($getTickets) > 0) {?>
        <div class="catalog_elements_title h3_title">
            <?= __('dictionary.MSG_MSG_TICKETS_ZNAJDENO') . ' ' . count($getTickets) . ' ' . __('dictionary.MSG_MSG_TICKETS_AVTOBUSIV') ?></div>
        <div class="catalog_elements_subtitle par"><?= __('dictionary.MSG_MSG_TICKETS_CHAS_VIDPRAVLENNYA_TA_PRIBUTTYA_MISCEVIJ') ?></div>
        <div class="ticket_cards_wrapper">
            <?php foreach ($getTickets as $k => $ticket) {
                $selectColumns = rtrim("stop_id,arrival_time,departure_time,$arrival_day", ',');
                $getTicketStops = $Db->getAll("SELECT $selectColumns FROM `" .  DB_PREFIX . "_tours_stops`  WHERE tour_id = '".$ticket['id']."' ORDER BY id ASC ");
                $tourDeparture = $ticket['departure'];
                if ((int)$cleanPost['departure_city'] > 0){
                    $tourDeparture = (int)$cleanPost['departure_city'];
                }
                $tourArrival = $ticket['arrival'];
                if ((int)$cleanPost['arrival_city'] > 0){
                    $tourArrival = (int)$cleanPost['arrival_city'];
                }
                $lastStop = end($getTicketStops);
                $arrival_day = $lastStop['arrival_day'];
                $departureDetails = $Db->getOne("SELECT station.id,station.title_".$Router->getLang()." AS station,city.title_".$Router->getLang()." AS city,stop.departure_time FROM `" .  DB_PREFIX . "_cities`  station
                                    LEFT JOIN `" .  DB_PREFIX . "_cities`  city ON city.id = station.section_id
                                    LEFT JOIN `" .  DB_PREFIX . "_tours_stops`  stop ON stop.stop_id = station.id AND stop.tour_id = '".$ticket['id']."'
                                    WHERE station.station = 1 AND station.section_id = '".$tourDeparture."' AND station.id IN(SELECT stop_id FROM `" .  DB_PREFIX . "_tours_stops`  WHERE tour_id = '".$ticket['id']."' )");
                $arrivalDetails = $Db->getOne("SELECT station.id,station.title_".$Router->getLang()." AS station,city.title_".$Router->getLang()." AS city,stop.arrival_time FROM `" .  DB_PREFIX . "_cities`  station
                                    LEFT JOIN `" .  DB_PREFIX . "_cities`  city ON city.id = station.section_id
                                    LEFT JOIN `" .  DB_PREFIX . "_tours_stops`  stop ON stop.stop_id = station.id AND stop.tour_id = '".$ticket['id']."'
                                    WHERE station.station = 1 AND station.section_id = '".$tourArrival."' AND station.id IN(SELECT stop_id FROM `" .  DB_PREFIX . "_tours_stops`  WHERE tour_id = '".$ticket['id']."' )");
                $rideTime = calculateTotalTravelTime($getTicketStops,$departureDetails['id'],$arrivalDetails['id'],$arrival_day);

                $filterDate = array_map('intval',explode('-',$cleanPost['date']));

                if ($cleanPost['date'] == 'today'){
                    $filterDate = findNearestDayOfWeek(date('Y-m-d',time()), explode(',',$ticket['days']));
                    $filterDate = array_map('intval',explode('-',$filterDate));
                }
                $month = $Db->getOne("SELECT title_".$Router->getLang()." AS title FROM `" .  DB_PREFIX . "_months`  WHERE id = '".(int)$filterDate[1]."' ");
                $departureDate = $filterDate[2] . ' ' . $month['title'] . ' ' . $filterDate[0];

                $international = ($ticket['departure_city_section_id'] != $ticket['arrival_city_section_id']);
                $ticketPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop = '".$departureDetails['id']."' AND to_stop = '".$arrivalDetails['id']."' AND tour_id = '".$ticket['id']."' ");
                ?>
                <div class="ticket_card shadow_block">
                    <div class="flex-row">
                        <div class="col-lg-9 col-xs-12">
                            <div class="ticket_info">
                                <div class="ticket_info_header flex_ac">
                                    <div class="ticket_info_date_block flex_ac">
                                        <img src="<?= asset('images/legacy/common/ticket_calendar.svg'); ?>"
                                             alt="calendar">
                                        <span class="ticket_info_date par">
                                    <?= $departureDate ?>
                                </span>
                                    </div>
                                    <div class="ride_description_wrapper flex_ac">
                                        <div class="ride_description par">
                                            <span><?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_REJS'] ?></span>
                                            <span><?= $ticket['departure_city'] ?> — <?= $ticket['arrival_city'] ?></span>
                                        </div>
                                        <div class="ride_description par">
                                            <span><?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_AVTOBUS'] ?></span>
                                            <span><?= $ticket['bus_title'] ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="ticket_ride_info_block flex-row gap-30">
                                    <div class="col-lg-4 col-sm-6 col-xs-12">
                                        <div class="ticket_ride_departure ticket_ride_info">
                                            <div class="ticket_ride_time flex_ac">
                                                <img src="<?= asset('images/legacy/common/clock.svg'); ?>" alt="clock">
                                                <span class="btn_txt"><?= date("H:i", strtotime($departureDetails['departure_time'])) ?></span>
                                            </div>
                                            <div class="ticket_ride_city btn_txt">
                                                <?= $departureDetails['city'] ?>
                                            </div>
                                            <div class="ticket_ride_checkpoint manrope">
                                                <?= $departureDetails['station'] ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 hidden-md hidden-sm col-xs-12">
                                        <div class="ticket_ride_info ride_total_time">
                                            <div class="ticket_logo_wrapper">
                                                <img src="<?= asset('images/legacy/common/ticket_logo_2.svg'); ?>"
                                                     alt="ticket logo" class="fit_img">
                                            </div>
                                            <div class="ticket_ride_total_time_wrapper">
                                                <div class="ticket_ride_total_time_info">
                                                    <img src="<?= asset('images/legacy/common/info.svg'); ?>"
                                                         alt="info">
                                                </div>
                                                <div class="ticket_ride_total_time_data">
                                                    <div class="ticket_ride_total_time par">
                                                        <?= (int)explode(':',$rideTime)[0].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_GOD'].' '.(int)explode(':',$rideTime)[1].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_HV_V_DOROZI'] ?>
                                                    </div>
                                                    <? if ($international) { ?>
                                                        <div class="ticket_ride_status par">
                                                            <?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_MIZHNARODNIJ'] ?>
                                                        </div>
                                                    <? } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-sm-6 col-xs-12">
                                        <div class="ticket_ride_arrival ticket_ride_info">
                                            <div class="ticket_ride_time flex_ac">
                                                <img src="<?= asset('images/legacy/common/clock.svg'); ?>"
                                                     alt="clock">
                                                <span class="btn_txt"><?= date('H:i', strtotime($arrivalDetails['arrival_time'])) ?></span>
                                            </div>
                                            <div class="ticket_ride_city btn_txt">
                                                <?= $arrivalDetails['city'] ?>
                                            </div>
                                            <div class="ticket_ride_checkpoint">
                                                <?= $arrivalDetails['station'] ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button class="ticket_details_btn shedule_link flex_ac hidden-md hidden-sm hidden-xs" onclick="toggleRouteDetails('<?= $ticket['id'] ?>','<?=$departureDetails['id']?>','<?=$arrivalDetails['id']?>')">
                                <?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_DETALINISHE'] ?>
                                <img src="<?= asset('images/legacy/common/arrow_down_2.svg'); ?>" alt="arrow down">
                            </button>
                        </div>
                        <div class="col-lg-3 hidden-md hidden-sm hidden-xs">
                            <div class="ticket_totals">
                                <div class="ticket_price"><?= $ticketPrice['price'] ?> ₴</div>
                                <button class="ticket_buy_btn flex_ac h5_title blue_btn" onclick="buyTicket(this,'<?= $ticket['id'] ?>','<?=$departureDetails['id']?>','<?=$arrivalDetails['id']?>')">
                                    <?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_KUPITI_KVITOK'] ?>
                                </button>
                            </div>
                        </div>
                        <div class="hidden-xxl hidden-xl hidden-lg col-sm-12 hidden-xs">
                            <div class="ride_total_time">
                                <div class="ticket_logo_wrapper">
                                    <img src="<?= asset('images/legacy/common/ticket_logo_2.svg'); ?>"
                                         alt="ticket logo" class="fit_img">
                                </div>
                                <div class="mobile_ticket_ride_total_time_wrapper flex_ac">
                                    <div class="ticket_ride_total_time_info flex_ac">
                                        <img src="<?= asset('images/legacy/common/info.svg'); ?>" alt="info">
                                        <div class="ticket_ride_total_time par">
                                            <?= (int)explode(':',$rideTime)[0].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_GOD'].' '.(int)explode(':',$rideTime)[1].' '.$GLOBALS['dictionary']['MSG_MSG_TICKETS_HV_V_DOROZI'] ?>
                                        </div>
                                    </div>
                                    <? if ($international) { ?>
                                        <div class="ticket_ride_status par">
                                            <?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_MIZHNARODNIJ'] ?>
                                        </div>
                                    <? } ?>
                                </div>
                            </div>
                        </div>
                        <div class="hidden-xxl hidden-xl hidden-lg col-xs-12">
                            <div class="mobile_ticket_totals flex_ac">
                                <div class="mobile_ticket_details flex_ac">
                                    <div class="ticket_price"><?= $ticketPrice['price'] ?> ₴</div>
                                    <button class="ticket_details_btn shedule_link flex_ac" onclick="toggleRouteDetails('<?= $ticket['id'] ?>','<?=$departureDetails['id']?>','<?=$arrivalDetails['id']?>')">
                                        <?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_DETALINISHE'] ?>
                                        <img src="<?= asset('images/legacy/common/arrow_down_2.svg'); ?>" alt="arrow down">
                                    </button>
                                </div>
                                <button class="ticket_buy_btn flex_ac h5_title blue_btn" onclick="buyTicket(this,'<?= $ticket['id'] ?>','<?=$departureDetails['id']?>','<?=$arrivalDetails['id']?>')">
                                    <?= $GLOBALS['dictionary']['MSG_MSG_TICKETS_KUPITI_KVITOK'] ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }?>
        </div>
    <?php } else{?>
        <div class="catalog_elements">
            <div class="catalog_elements_title h3_title">
                <?=$GLOBALS['dictionary']['MSG_MSG_TICKETS_PO_VYBRANNYM_PARAMETRAM_FILITRA_BILETOV_NE_NAJDENO']?>
            </div>
        </div>
    <?php }

}

if ($cleanPost['request'] === 'remember_private_data'){
    /*dd($cleanPost);*/
    $_SESSION['order']['family_name'] = $cleanPost['family_name'];
    $_SESSION['order']['name'] = $cleanPost['name'];
    $_SESSION['order']['patronymic'] = $cleanPost['patronymic'];
    $_SESSION['order']['birth_date'] = $cleanPost['birthDate'];
    /*$_SESSION['order']['doc_type'] = $cleanPost['doc_type'];*/
    $_SESSION['order']['email'] = $cleanPost['email'];
    $_SESSION['order']['phone'] = $cleanPost['phone'];
    $_SESSION['order']['save_data'] = (int)$cleanPost['save_data'];
    $_SESSION['order']['phone_code'] = (int)$cleanPost['phone_code'];


    //Сохраняем пассажиров
    if (!empty($cleanPost['passengers']) && is_array($cleanPost['passengers'])) {
        $_SESSION['order']['passengers_data'] = [];
        foreach ($cleanPost['passengers'] as $passenger) {
            $_SESSION['order']['passengers_data'][] = [
                'family_name' => $passenger['family_name'],
                'name' => $passenger['name'],
                'patronymic' => $passenger['patronymic'],
                'birth_date' => $passenger['birth_date']
            ];
        }
    }
    echo 'ok';
}

if ($cleanPost['request'] === 'order_route'){
    $tourId = (int)$cleanPost['order']['tour_id'];
    $from = (int)$cleanPost['order']['from'];
    $to = (int)$cleanPost['order']['to'];
    $tourDate = $cleanPost['order']['date'];
    $clientName = $cleanPost['order']['name'];
    $clientSurname = $cleanPost['order']['family_name'];
    $clientMail = $cleanPost['order']['email'];
    $clientPhone = $cleanPost['order']['phone'];
    $paymethod = $cleanPost['order']['paymethod'];
    $passengers = (int)$cleanPost['order']['passengers'];
    $uniqId = $cleanPost['order']['order_id'];

    $existingOrder = $Db->getOne("SELECT id FROM `" . DB_PREFIX . "_orders` WHERE uniqId = '".$uniqId."' ");

    if ($existingOrder) {
        // Если заказ уже существует, использовать его
        echo 'ok'; // или другая логика, например, обновление заказа или сообщение пользователю
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
        $fieldValue[] = '"' . (int)$_SESSION['order']['passengers'] . '"';
        $fieldName[] = 'uniqId';
        $fieldValue[] = '"' . $uniqId . '"';
        $order = $Db->query("INSERT INTO `" . DB_PREFIX . "_orders` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");
        if ($order) {
            $updPopular = $Db->query("UPDATE `" .  DB_PREFIX . "_tours`  SET popular = popular + 1 WHERE id = '" . $tourId . "' ");
            $tourDistance = $Db->getOne("SELECT distance FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE tour_id = '" . $tourId . "' AND from_stop = '" . $from . "' AND to_stop = '" . $to . "' ");
            $updClientsMiles = $Db->query("UPDATE `" .  DB_PREFIX . "_clients`  SET miles = miles + " . (int)$tourDistance['distance'] . " WHERE id = '" . $User->id . "' ");
            $updSales = $Db->query("UPDATE `" .  DB_PREFIX . "_tours_sales`  SET tickets_order = tickets_order + " . $passengers . " WHERE tour_id = '" . $tourId . "' AND tour_date = '" . $tourDate . "' ");

            /*if ($paymethod === 'cardpay') {
                $updSales = $Db->query("UPDATE `" .  DB_PREFIX . "_tours_sales`  SET tickets_buy = tickets_buy + ".$passengers." WHERE tour_id = '".$tourId."' AND tour_date = '".$tourDate."' ");
            }*/
            if ((int)$cleanPost['save_card'] == '1') {
                $cardNumber = array();
                $cardNumExplode = explode(' ', $cleanPost['card_number']);
                foreach ($cardNumExplode as $k => $num) {
                    $cardNumber[] = (int)$num;
                }
                $cardDate = explode('/', $cleanPost['card_valid_date']);
                $validCardDate = array();
                foreach ($cardDate as $k => $cd) {
                    if (strlen($cd) > 2) {
                        exit('Дата действия карты указана неверно');
                    }
                    $validCardDate[] = (int)$cd;
                }
                $cardDate = implode('/', $validCardDate);

                $checkCurrentClientCard = $Db->getOne("SELECT id FROM `" .  DB_PREFIX . "_clients_cards`  WHERE client_id = '" . $User->id . "' ");
                if ($checkCurrentClientCard) {
                    $upd = $Db->query("UPDATE `" .  DB_PREFIX . "_clients_cards`  SET
        card_number = '" . implode(' ', $cardNumber) . "',
        valid_date = '" . $cardDate . "',
        cardholder_name = '" . $cleanPost['cardholder_name'] . "',
        cvv = '" . (int)$cleanPost['card_cvv'] . "' WHERE client_id = '" . $User->id . "' ");
                } else {
                    $upd = $Db->query("INSERT INTO `" .  DB_PREFIX . "_clients_cards`  (`card_number`,`valid_date`,`cardholder_name`,`cvv`,`client_id`) VALUES
        ('" . implode(' ', $cardNumber) . "','" . $cardDate . "','" . $cleanPost['cardholder_name'] . "','" . (int)$cleanPost['card_cvv'] . "','" . $User->id . "') ");
                }
            }

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

            foreach ($_SESSION['order']['passengers_data'] as $passenger) {
                $fieldName = $fieldValue = array();
                $fieldName[] = 'name';
                $fieldValue[] = '"' . $passenger['name'] . '"';
                $fieldName[] = 'second_name';
                $fieldValue[] = '"' . $passenger['family_name'] . '"';
                $fieldName[] = 'patronymic';
                $fieldValue[] = '"' . $passenger['patronymic'] . '"';
                $fieldName[] = 'order_id';
                $fieldValue[] = '"' . $uniqId . '"'; // Предположим, что $order_id - это ID созданного заказа
                $fieldName[] = 'birth_date';
                $fieldValue[] = '"' . $passenger['birth_date'] . '"';

                $Db->query("INSERT INTO `" . DB_PREFIX . "_orders_passangers` (" . implode(',', $fieldName) . ") VALUES (" . implode(',', $fieldValue) . ") ");
            }


            echo 'ok';
        } else {
            echo 'err';
        }
    }
}

if ($cleanPost['request'] === 'update_client_phone'){
    $upd = $Db->query("UPDATE `".DB_PREFIX."_clients` SET `phone_code` = '".$cleanPost['phone_code']."',`phone` = '".$cleanPost['phone']."' WHERE id = '".$User->id."' ");
    if ($upd){
        echo 'ok';
    }else{
        echo 'err';
    }
}

if ($cleanPost['request'] === 'update_client_info'){
    $upd = $Db->query("UPDATE `".DB_PREFIX."_clients` SET name = '".$cleanPost['name']."',
    second_name = '".$cleanPost['second_name']."',
    patronymic = '".$cleanPost['patronymic']."',
    birth_date = '".$cleanPost['birth_date']."'
    WHERE id = '".$User->id."' ");
    if ($upd){
        echo 'ok';
    }else{
        echo 'err';
    }
}


if ($cleanPost['request'] == 'return_ticket_popup'){
    $checkClientTicket = $Db->getOne("SELECT client_email FROM `" .  DB_PREFIX . "_orders`  WHERE id = '".(int)$cleanPost['id']."' ");
    if ($checkClientTicket['client_email'] != $User->email){
        exit;
    }else{
        $getTicketInfo = $Db->getOne("SELECT
            o.id,
            o.tour_id,
            o.from_stop,
            o.to_stop,
            o.tour_date, o.payment_status,o.passagers, o.uniqid,
            departure_city.title_".$Router->getLang()." AS departure_city,
            departure_city.section_id AS departure_city_section_id,
            departure_station.title_".$Router->getLang()." AS departure_station,
            arrival_city.title_".$Router->getLang()." AS arrival_city,
            arrival_city.section_id AS arrival_city_section_id,
            arrival_station.title_".$Router->getLang()." AS arrival_station,
            tsp.price AS price,
            bus.title_".$Router->getLang()." AS bus_title,
            dt.departure_time,
            at.arrival_time
            FROM `" . DB_PREFIX . "_orders` o
            LEFT JOIN `" . DB_PREFIX . "_tours` t ON t.id = o.tour_id
            LEFT JOIN `" . DB_PREFIX . "_tours_stops` dt ON dt.tour_id = o.tour_id AND dt.stop_id = o.from_stop
            LEFT JOIN `" . DB_PREFIX . "_tours_stops` at ON at.tour_id = o.tour_id AND at.stop_id = o.to_stop
            LEFT JOIN `" . DB_PREFIX . "_cities` departure_station ON departure_station.id = o.from_stop
            LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = departure_station.section_id
            LEFT JOIN `" . DB_PREFIX . "_cities` arrival_station ON arrival_station.id = o.to_stop
            LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = arrival_station.section_id
            LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices` tsp ON tsp.from_stop = o.from_stop AND tsp.to_stop = o.to_stop AND tsp.tour_id = t.id
            LEFT JOIN `" . DB_PREFIX . "_buses` bus ON bus.id = t.bus
            WHERE o.client_email = '" . $User->email . "' AND o.id = '".(int)$cleanPost['id']."' ");
        $month = $Db->getOne("SELECT title_".$Router->getLang()." AS title FROM `".DB_PREFIX."_months` WHERE id = '".date('m',strtotime($getTicketInfo['tour_date']))."' ");
    } ?>



    <div class="return_ticket_popup_content_wrapper">
        <div class="close_return_wrapper">
            <button class="close_menu" onclick="toggleReturnBlock('0')">
                <img src="<?= asset('images/legacy/common/arrow_left.svg'); ?>" alt="arrow left">
            </button>
        </div>
        <div class="return_ticket_popup_content">
            <div class="return_ticket_route_info">
                <div class="return_ticket_route_info_header flex_ac">
                    <div class="return_ticket_date par flex_ac">
                        <img src="<?= asset('images/legacy/common/calendar_white.svg'); ?>" alt="calendar">
                        <?
                        $departureTourDate = date('d',strtotime($getTicketInfo['tour_date'])).' '.$month['title'].' '.date('Y',strtotime($getTicketInfo['tour_date']))?>
                        <span> <?=$departureTourDate?> </span>
                    </div>
                    <div class="return_ticket_route flex_ac par">
                        <span> <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_REJS'] ?> </span>
                        <span> <?=$getTicketInfo['departure_city'].' - '.$getTicketInfo['arrival_city']?></span>
                    </div>
                    <div class="return_ticket_route no_margin flex_ac par">
                        <span> <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_AVTOBUS'] ?> </span>
                        <span> <?=$getTicketInfo['bus_title']?> </span>
                    </div>
                </div>
                <div class="return_ticket_route_description">
                    <div class="flex-row gap-30">
                        <div class="col-md-4 col-sm-6">
                            <div class="return_ticket_time flex_ac">
                                <img src="<?= asset('images/legacy/common/clock_light.svg'); ?>" alt="clock light">
                                <span class="btn_txt"><?=date('H:i',strtotime($getTicketInfo['departure_time']))?></span>
                            </div>
                            <div class="return_ticket_point btn_txt">
                                <?=$getTicketInfo['departure_city']?>
                            </div>
                            <div class="return_ticket_description manrope">
                                <?=$getTicketInfo['departure_station']?>
                            </div>
                        </div>
                        <div class="col-md-4 hidden-sm col-xs-12">
                            <div class="return_ticket_logo">
                                <div class="return_ticket_logo_wrapper">
                                    <img src="<?= asset('images/legacy/common/ticket_logo_light.svg'); ?>" alt="ticket logo"
                                         class="fit_img">
                                </div>
                                <span class="manrope par"><?=$getTicketInfo['hours'].' '.$GLOBALS['dictionary']['MSG_MSG_FUTURE_GOD'].' '.$getTicketInfo['minutes'].' '.$GLOBALS['dictionary']['MSG_MSG_FUTURE_HV_V_DOROZI']?></span>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-6">
                            <div class="return_ticket_time">
                                <img src="<?= asset('images/legacy/common/clock_light.svg'); ?>" alt=" clock light">
                                <span class="btn_txt"><?=date('H:i',strtotime($getTicketInfo['arrival_time']))?></span>
                            </div>
                            <div class="return_ticket_point btn_txt">
                                <?=$getTicketInfo['arrival_city']?>
                            </div>
                            <div class="return_ticket_description manrope">
                                <?=$getTicketInfo['arrival_station']?>
                            </div>
                        </div>
                        <div class="col-sm-12 hidden-xxl hidden-xl hidden-lg hidden-md hidden-xs">
                            <div class="return_ticket_logo">
                                <div class="return_ticket_logo_wrapper">
                                    <img src="<?= asset('images/legacy/common/ticket_logo_light.svg'); ?>" alt="ticket logo"
                                         class="fit_img">
                                </div>
                                <span class="manrope par"><?=$getTicketInfo['hours'].' '.$GLOBALS['dictionary']['MSG_MSG_FUTURE_GOD'].' '.$getTicketInfo['minutes'].' '.$GLOBALS['dictionary']['MSG_MSG_FUTURE_HV_V_DOROZI']?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <select class="return_ticket_reason flex_ac">
                        <option value="" selected disabled><?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_PRICHINA_POVERNENNYA'] ?></option>
                        <?$getReturnReasons = $Db->getAll("SELECT id,title_".$Router->getLang()." AS title FROM `" .  DB_PREFIX . "_return_reasons`  WHERE active = 1 ORDER BY sort DESC ");
                        foreach ($getReturnReasons AS $k=>$returnReason){?>
                            <option value="<?=$returnReason['id']?>"><?=$returnReason['title']?></option>
                        <?}?>
                    </select>
                </div>

                <div class="return_ticket_rules">
                    <div class="return_ticket_rules_title h3_title">
                        <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_PRAVILA_POVERNENNYA'] ?>
                    </div>
                    <div class="return_ticket_txt par">
                        <? $returnTxt = $Db->getOne("SELECT text_" . $Router->getLang() . " AS text FROM `" . DB_PREFIX . "_txt_blocks` WHERE id = '7' ") ?>
                        <?= $returnTxt['text'] ?>
                    </div>
                </div>
                <div class="return_ticket_method">
                    <? if ($getTicketInfo['payment_status'] === '2') { ?>
                        <div class="h3_title return_ticket_method_title">
                            <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_SPOSIB_POVERNENNYA_KOSHTIV'] ?>
                        </div>
                        <div class="return_method_row flex_ac">
                            <label class="c_checkbox_wrapper flex_ac">
                                <input type="radio" name="returnmethod" class="c_checkbox_checker return_payments_type" hidden="" checked value="1">
                                <span class="c_checkbox white"></span>
                                <span class="c_checkbox_title par"><?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_BANKIVSIKA_KARTKA'] ?></span>
                            </label>
                            <div class="return_ticket_price h2_title">

                                <? $totalPrice = ($getTicketInfo['price'] * $getTicketInfo['passagers']) ; ?>
                                <!--                            --><?php //=$totalPrice?><!-- --><?php //= $GLOBALS['dictionary']['MSG_MSG_FUTURE_GRN'] ?>
                            </div>
                        </div>
                    <? } else { ?>
                        <div class="return_method_row flex_ac d_none">
                            <label class="c_checkbox_wrapper flex_ac">
                                <input type="radio" name="returnmethod" class="c_checkbox_checker return_payments_type" hidden="" checked value="2">
                                <span class="c_checkbox white"></span>
                                <span class="c_checkbox_title par"><?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_GOTIVKOYU'] ?></span>
                            </label>
                            <div class="return_ticket_price h3_title">
                                <?=$getTicketInfo['price']?> <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_GRN'] ?>
                            </div>
                        </div>
                    <? } ?>
                </div>

                <div class="return_ticket_method">
                    <div class="h3_title return_ticket_method_title">
                        <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_SPOSIB_POVERNENNYA_KOSHTIV'] ?>
                    </div>
                    <?
                    $uniqId = $getTicketInfo['uniqid'];
                    $getOrderedTickets = $Db->getAll("SELECT id, name, second_name FROM `" .  DB_PREFIX . "_orders_passangers`  WHERE order_id = '".$uniqId."' AND ticket_return = 0"); ?>
                    <? foreach ($getOrderedTickets as $k=>$ticket) { ?>
                        <div class="return_method_row flex_ac ">
                            <label class="c_checkbox_wrapper flex_ac">
                                <input type="checkbox" name="ticket" class="c_checkbox_checker return_tickets" hidden=""  value="<?=$ticket['id'];?>">
                                <span class="c_checkbox white"></span>
                                <span class="c_checkbox_title par"><?= $ticket['name'] .' '. $ticket['second_name'] ?></span>
                            </label>
                            <div class="return_ticket_price h3_title">
                                <?=$getTicketInfo['price']?> <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_GRN'] ?>
                            </div>
                        </div>
                    <?}?>
                </div>

                <div class="return_ticket_totals flex_ac">
                    <? if ($getTicketInfo['payment_status'] === '2') { ?>
                        <div class="return_ticket_price h2_title">
                            <?=$totalPrice?><?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_GRN'] ?>
                        </div>
                    <? } ?>
                    <button class="return_ticket_return_btn blue_btn flex_ac" onclick="returnTicket('<?=$getTicketInfo['id']?>', <?=$getTicketInfo['price'] ?>)">
                        <?= $GLOBALS['dictionary']['MSG_MSG_FUTURE_POVERNUTI_KVITOK'] ?>
                    </button>
                </div>
            </div>
        </div>
    </div>


<?}


if ($cleanPost['request'] === 'return_ticket'){
    $tikcetsIds = $cleanPost['ticketsIds'];
    $idsString = implode(',', array_map('intval', $tikcetsIds));

    $returnedTickets = count($tikcetsIds);


    $ticketInfo = $Db->getOne("SELECT o.client_name, o.client_phone, o.client_surname, o.tour_date, o.client_email,o.from_stop,o.to_stop,o.tour_id, o.uniqid, fs.title_uk AS from_title, fs.section_id AS from_city_id, ts.section_id AS to_city_id, tc.title_uk AS to_city_title, fc.title_uk AS from_city_title, ts.title_uk AS to_title
    FROM `".DB_PREFIX."_orders` o
    LEFT JOIN `".DB_PREFIX."_cities` fs ON fs.id = from_stop
    LEFT JOIN `".DB_PREFIX."_cities` ts ON ts.id = to_stop
    LEFT JOIN `".DB_PREFIX."_cities` fc ON fc.id = fs.section_id
    LEFT JOIN `".DB_PREFIX."_cities` tc ON tc.id = ts.section_id
 WHERE o.id = '".(int)$cleanPost['id']."' ");
    if ($ticketInfo['client_email'] != $User->email){
        exit('err');
    }
    $upd = $Db->query("UPDATE `".DB_PREFIX."_orders_passangers` SET ticket_return = '1',return_reason = '".(int)$cleanPost['reason']."',return_payment_type = '".(int)$cleanPost['return_payments']."',return_date = NOW() WHERE id IN ($idsString) ");






    if ($upd){

        $checkOrder = $Db->getAll("SELECT id FROM `".DB_PREFIX."_orders_passangers` WHERE order_id = '".$ticketInfo['uniqid']."' AND ticket_return = 0");

        if (empty($checkOrder)) {

            $updOrder = $Db->query("UPDATE `".DB_PREFIX."_orders` SET ticket_return = '1',return_reason = '".(int)$cleanPost['reason']."',return_payment_type = '".(int)$cleanPost['return_payments']."',return_date = NOW() WHERE id = '".(int)$cleanPost['id']."' ");

            $returnMiles = $Db->getOne("SELECT distance FROM `".DB_PREFIX."_tours_stops_prices` WHERE tour_id = '".$ticketInfo['tour_id']."' AND from_stop = '".$ticketInfo['from_stop']."' AND to_stop = '".$ticketInfo['to_stop']."' ");
            $updClientMiles = $Db->query("UPDATE `".DB_PREFIX."_clients` SET miles = miles - ".(int)$returnMiles['distance']." WHERE id = '".$User->id."' ");

        }

        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $clientName = $ticketInfo['client_name'];
        $clientSurname = $ticketInfo['client_surname'];
        $phone = $ticketInfo['client_phone'];
        $date = $ticketInfo['tour_date'];
        $from = $ticketInfo['from_title'];
        $fromCity = $ticketInfo['from_city_title'];
        $to = $ticketInfo['to_title'];
        $toCity = $ticketInfo['to_city_title'];
        $email = $ticketInfo['client_email'];
        $total = $cleanPost['totalprice'] * $returnedTickets;

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
            echo 'Message could not be sent. Error: ' . $e->getMessage();
        }

        try {
            sendMail($to2, $subject2, $message2, $headers);

            if (empty($checkOrder)) {
                echo 'ok';
            } elseif (!empty($checkOrder)) {
                echo 'ok_nfreturn';
            }
        } catch (Exception $e) {
            echo 'Ошибка отправки второго сообщения. Error: ' . $e->getMessage();
        }



    } else {
        echo 'error';
    }
}

if ($cleanPost['request'] === 'update_client_card'){
    $cardNumber = array();
    $cardNumExplode = explode(' ',$cleanPost['card_number']);
    foreach ($cardNumExplode AS $k=>$num){
        $cardNumber[] = (int)$num;
    }
    $cardDate = explode('/',$cleanPost['card_valid_date']);
    $validCardDate = array();
    foreach ($cardDate AS $k=>$cd){
        if (strlen($cd) > 2){
            exit('Дата действия карты указана неверно');
        }
        $validCardDate[] = (int)$cd;
    }
    $cardDate = implode('/',$validCardDate);

    $checkCurrentClientCard = $Db->getOne("SELECT id FROM `" .  DB_PREFIX . "_clients_cards`  WHERE client_id = '".$User->id."' ");
    if ($checkCurrentClientCard){
        $upd = $Db->query("UPDATE `" .  DB_PREFIX . "_clients_cards`  SET
        card_number = '".implode(' ',$cardNumber)."',
        valid_date = '".$cardDate."',
        cardholder_name = '".$cleanPost['cardholder_name']."',
        cvv = '".(int)$cleanPost['card_cvv']."' WHERE client_id = '".$User->id."' ");
    }else{
        $upd = $Db->query("INSERT INTO `" .  DB_PREFIX . "_clients_cards`  (`card_number`,`valid_date`,`cardholder_name`,`cvv`,`client_id`) VALUES
        ('".$cardNumber."','".$cardDate."','".$cleanPost['cardholder_name']."','".(int)$cleanPost['card_cvv']."','".$User->id."') ");
    }
    if ($upd){
        echo 'ok';
    }else{
        echo 'err';
    }
}


if ($cleanPost['request'] === 'route_details_schedule'){

    $mainInfo = $Db->getAll("SELECT stop.title_".$Router->getLang()." AS stop_title,stop.google_maps_link,stop_city.title_".$Router->getLang()." AS stop_city,stop_city.section_id AS stop_country_id,ts.departure_time,ts.arrival_time,ts.stop_id FROM `" .  DB_PREFIX . "_tours_stops`  ts
     LEFT JOIN `" .  DB_PREFIX . "_cities`  stop ON stop.id = ts.stop_id
     LEFT JOIN `" .  DB_PREFIX . "_cities`  stop_city ON stop_city.id = stop.section_id
     WHERE ts.tour_id = '".(int)$cleanPost['id']."'
     ORDER BY ts.stop_num ASC");
    $departureCity = $mainInfo[0]['stop_city'];
    $arrivalCity = $mainInfo[count($mainInfo) - 1]['stop_city'];
    $ticketPrice = $Db->getOne("SELECT price FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop = '".(int)$cleanPost['departure']."' AND to_stop = '".(int)$cleanPost['arrival']."' AND tour_id = '".(int)$cleanPost['id']."' ");
    $busOptions = $Db->getAll("SELECT bo.title_".$Router->getLang()." AS bus_option FROM `" .  DB_PREFIX . "_buses_options_connector`  boc
     LEFT JOIN `" .  DB_PREFIX . "_buses_options`  bo ON bo.id = boc.option_id
      WHERE boc.bus_id = (SELECT bus FROM `" .  DB_PREFIX . "_tours`  WHERE id = '".(int)$cleanPost['id']."' )  ");
    $transferStop = $Db->getAll("SELECT transfer_station_id AS transfer_station FROM `" .  DB_PREFIX . "_tours_transfers`  tt
    WHERE tt.tour_id = '".(int)$cleanPost['id']."' ");

    ?>
    <div class="schedule_details_content">
        <button class="close_details" onclick="toggleRouteDetailsSchedule('0')">
            <img src="<?= asset('images/legacy/common/arrow_left_blue.svg'); ?>" alt="arrow left">
        </button>
        <div class="schedule_details_block_header h3_title">
            <?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_MARSHRUT']?> <?=$departureCity.' - '.$arrivalCity?>
        </div>
        <!--div class="schdule_details_route par">

            <?foreach ($mainInfo AS $k=>$tourStop){
                $isTransfer = false;
                foreach ($transferStop as $transfer) {
                    if ($tourStop['stop_id'] == $transfer['transfer_station']) {
                        $isTransfer = true;
                        break;
                    }
                }

                if ($isTransfer) {
                    echo $tourStop['stop_city'];
                } else {
                    echo $tourStop['stop_city'];
                }
                if ($k < (count($mainInfo) - 1) ){
                    echo ' - ';
                }
            }?>

        </div>
        <div class="schedule_details_block_header h3_title">
            <?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE__MISCE_POSADKI_V_UKRANI']?>
        </div>
        <ul class="landing_places">
            <?foreach ($mainInfo AS $k=>$ts){
                if ($ts['stop_country_id'] == 13){?>
                    <li class="par"><?=$ts['stop_city'].' '.$ts['stop_title']?></li>
                <?}
            }?>
        </ul-->
        <div class="departure_block_title">
            <span><?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_VIDPRAVKA']?></span>
            <!--span class="hidden-sm hidden-xs"><?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_FINISH']?></span-->
        </div>
        <div class="schedule_route">
            <?foreach ($mainInfo AS $key=>$tourStop){
                $isTransfer = false;
                foreach ($transferStop as $transfer) {
                    if ($tourStop['stop_id'] == $transfer['transfer_station']) {
                        $isTransfer = true;
                        break;
                    }
                }
                ?>

                <div class="schedule_route_row">
                    <div class="schedule_route_point_row">
                        <div class="schedule_route_point">
                            <div class="schedule_route_point_point <?if ($key == 0){echo 'active';}?> "></div>
                        </div>
                        <div class="schedule_route_point_time"><?=date('H:i',strtotime($tourStop['departure_time']))?></div>
                    </div>
                    <a href="<?=$tourStop['google_maps_link']?>" class="schedule_route_stop" target="_blank">
                        <div class="schedule_route_city"><?=$tourStop['stop_city']?><?if ($isTransfer) {?>
                                <img class="transfer_icon" src="<?= asset('images/legacy/transfer.svg'); ?>" alt="Пересадка">
                                <?= $GLOBALS['dictionary']['MSG_MSG_SCHEDULE_TRANSFER'] . ''; ?>
                            <?php }?></div>
                        <div class="schedule_route_station"><?=$tourStop['stop_title']?></div>

                    </a>
                    <!--div class="schedule_route_point_row hidden-sm hidden-xs">
                        <div class="schedule_route_point_time"><?=date('H:i',strtotime($tourStop['arrival_time']))?></div>
                        <div class="schedule_route_point">
                            <div class="schedule_route_point_point <?if ($key == (count($mainInfo) - 1)){echo 'active';}?>"></div>
                        </div>
                    </div-->
                </div>
            <?}?>
        </div>
        <div class="arrival_block_title">
            <span><?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_FINISH']?></span>
            <!--span class="hidden-sm hidden-xs"><?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_VIDPRAVKA']?></span-->
        </div>

        <div class="schedule_route_important_txt">
            <div class="sr_txt_title h3_title"><?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_VAZHLIVO']?></div>
            <div class="sr_txt">
                <?$importantTxt = $Db->getOne("SELECT text_".$Router->getLang()." AS text FROM `" .  DB_PREFIX . "_txt_blocks`  WHERE id = 11");
                echo $importantTxt['text']?>
            </div>
        </div>
        <div class="sr_services">
            <div class="sr_services_title_row h3_title" onclick="toggleDetailsServices(this)">
                <div class="sr_services_title h3_title">
                    <?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_POSLUGI_V_AVTOBUSI']?>
                </div>
                <img src="<?= asset('images/legacy/common/arrow_down.svg'); ?>" alt="arrow">
            </div>
            <div class="sr_bus_options flex-row">
                <div class="col-md-9">
                    <div class="flex-row gap-8">
                        <?foreach ($busOptions AS $k=>$busOption){?>
                            <div class="col-md-4">
                                <div class="bus_option flex_ac par">
                                    <div class="check_imitation"></div>
                                    <?=$busOption['bus_option']?>
                                </div>
                            </div>
                        <?}?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?}

if ($cleanPost['request'] === 'route_price_details'){
    $mainInfo = $Db->getAll("SELECT stop.title_".$Router->getLang()." AS stop_title,stop.google_maps_link,stop_city.title_".$Router->getLang()." AS stop_city,stop_city.section_id AS stop_country_id,ts.departure_time,ts.arrival_time,ts.stop_id FROM `" .  DB_PREFIX . "_tours_stops`  ts
    LEFT JOIN `" .  DB_PREFIX . "_cities`  stop ON stop.id = ts.stop_id
    LEFT JOIN `" .  DB_PREFIX . "_cities`  stop_city ON stop_city.id = stop.section_id
    WHERE ts.tour_id = '".(int)$cleanPost['id']."'
    ORDER BY ts.stop_num ASC");
    $departureCity = $mainInfo[0]['stop_city'];
    $arrivalCity = $mainInfo[count($mainInfo) - 1]['stop_city'];
    $currentStopsPrices = $Db->getAll("SELECT * FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE tour_id = '".(int)$cleanPost['id']."' ");
    $currentPricesArray = [];
    $nearestDepartureDate = findNearestDayOfWeek(date('Y-m-d', time()), explode(',', $route['days']));
    ?>
    <div class="schedule_details_content">
        <button class="close_details" onclick="toggleRouteDetailsSchedule('0')">
            <img src="<?= asset('images/legacy/common/arrow_left_blue.svg'); ?>" alt="arrow left">
        </button>
        <div class="schedule_details_block_header h3_title">
            <?=$GLOBALS['dictionary']['MSG_MSG_SCHEDULE_MARSHRUT']?> <?=$departureCity.' - '.$arrivalCity?>
        </div>
        <div class="route_price_details_table par">
            <?if (count($currentStopsPrices) > 4) {
            ?>


            <?
            foreach ($currentStopsPrices AS $key=>$currentStopsPrice){
                $currentPricesArray[$currentStopsPrice['from_stop'].'-'.$currentStopsPrice['to_stop']]['price'] = $currentStopsPrice['price'];
            }
            $departure = $Db->getOne("SELECT cc.id,cc.title_".$Router->getLang()." AS station,c.title_".$Router->getLang()." AS city FROM `" .  DB_PREFIX . "_cities`  c
                                LEFT JOIN `" .  DB_PREFIX . "_cities`  cc ON cc.section_id = c.id
                                WHERE cc.id = '".(int)$cleanPost['departure']."' ");
            $arrival = $Db->getOne("SELECT cc.id,cc.title_".$Router->getLang()." AS station,c.title_".$Router->getLang()." AS city FROM `" .  DB_PREFIX . "_cities`  c
                                LEFT JOIN `" .  DB_PREFIX . "_cities`  cc ON cc.section_id = c.id
                                WHERE cc.id = '".(int)$cleanPost['arrival']."' "); ?>
            <table class="table m-0 tarif_table">
                <thead>
                <tr>
                    <th>От/До</th>
                    <?php
                    $getRouteStops = $Db->getAll("SELECT ts.id, ts.stop_id, ts.arrival_time, city.title_" . $Router->getLang() . " AS city, city.section_id AS city_id, station.title_" . $Router->getLang() . " AS station
                                          FROM `" .  DB_PREFIX . "_tours_stops`  ts
                                          LEFT JOIN `" .  DB_PREFIX . "_cities`  station ON station.id = ts.stop_id
                                          LEFT JOIN `" .  DB_PREFIX . "_cities`  city ON city.id = station.section_id
                                          WHERE ts.tour_id = '" . (int)$cleanPost['id'] . "' ORDER BY ts.stop_num ");
                    // Фильтруем остановки, чтобы исключить те, у которых city_id = '56'
                    $filteredRouteStops = array_filter($getRouteStops, function($stop) {
                        return $stop['city_id'] != '175';
                    });

                    foreach ($filteredRouteStops as $stop) {
                        echo '<th>' . $stop['city'] . ' ' . $stop['station'] . '</th>';
                    }
                    ?>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($filteredRouteStops as $i => $stopFrom) {
                    echo '<tr>';
                    echo '<td class="station_td">' . $stopFrom['city'] . ' ' . $stopFrom['station'] . '</td>';
                    foreach ($filteredRouteStops as $x => $stopTo) {
                        echo '<td>';
                        if ($x > $i) { // This ensures we only show future stops for the current stop
                            $priceKey = $stopFrom['stop_id'] . '-' . $stopTo['stop_id'];
                            if (isset($currentPricesArray[$priceKey]['price'])) {
                                echo '<p class="shedule_ticket_price">' . $currentPricesArray[$priceKey]['price'] . '</p>';
                                echo '<button class="schedule_details_btn" onclick="buyTicketFromSchedule(this, \'' . $currentStopsPrices[0]['tour_id'] . '\', \'' . $stopFrom['stop_id'] . '\', \'' . $stopTo['stop_id'] . '\', \'' . $nearestDepartureDate . '\')">';
                                echo $GLOBALS['dictionary']['MSG_MSG_SCHEDULE_KUPITI'];
                                echo '</button>';
                            }
                        }
                        echo '</td>';
                    }
                    echo '</tr>';
                }
                ?>
                </tbody>
            </table>

        </div>
        <? } else { ?>
        <div class="routes_prices_list">
            <div class="route_stops_prices_list routes_prices_header">
                <div class="route_stops_stop_info">
                    <div class="route_stop_title">Откуда</div>
                </div>
                <div class="route_stops_stop_info">
                    <div class="route_stop_title">Куда</div>
                </div>
                <div class="route_stops_stop_info route_stops_prices_price">Цена</div>
            </div>

            <?foreach ($currentStopsPrices as $k=>$routeStopPrice) {
                $getFromStopData = $Db->getAll("SELECT ts.id,ts.stop_id,ts.arrival_time,city.title_".$Router->getLang()." AS city,station.title_".$Router->getLang()." AS station
                FROM `" .  DB_PREFIX . "_tours_stops`  ts
                LEFT JOIN `" .  DB_PREFIX . "_cities`  station ON station.id = ts.stop_id
                LEFT JOIN `" .  DB_PREFIX . "_cities`  city ON city.id = station.section_id
                WHERE ts.tour_id = '".(int)$cleanPost['id']."' AND ts.stop_id = '".$routeStopPrice['from_stop']."' ORDER BY ts.stop_num ");
                $getToStopData = $Db->getAll("SELECT ts.id,ts.stop_id,ts.arrival_time,city.title_".$Router->getLang()." AS city,station.title_".$Router->getLang()." AS station
                FROM `" .  DB_PREFIX . "_tours_stops`  ts
                LEFT JOIN `" .  DB_PREFIX . "_cities`  station ON station.id = ts.stop_id
                LEFT JOIN `" .  DB_PREFIX . "_cities`  city ON city.id = station.section_id
                WHERE ts.tour_id = '".(int)$cleanPost['id']."' AND ts.stop_id = '".$routeStopPrice['to_stop']."' ORDER BY ts.stop_num ");
                ?>
                <div class="route_stops_prices_list">
                    <div class="route_stops_stop_info">
                        <div class="route_stop_title"><?=$getFromStopData['0']['city']?> </div>
                        <div class="route_stop_station_title">  <?=$getFromStopData['0']['station']?></div>
                    </div>
                    <div class="route_stops_stop_info">
                        <div class="route_stop_title"><?= $getToStopData['0']['city']?></div>
                        <div class="route_stop_station_title"><?=$getToStopData['0']['station']?></div>
                    </div>
                    <div class="route_stops_stop_info route_stops_prices_price"><?=$routeStopPrice['price']?> ГРН
                        <button class="buy_btn h5_title"
                                onclick="buyTicketFromSchedule(this,'<?= $currentStopsPrices['0']['tour_id'] ?>','<?= $getFromStopData['0']['stop_id'] ?>','<?= $getToStopData['0']['stop_id'] ?>','<?= $nearestDepartureDate ?>')">
                            <?= $GLOBALS['dictionary']['MSG_MSG_SCHEDULE_KUPITI_KVITOK'] ?>
                        </button>
                    </div>

                </div>
            <? } }?>

        </div>
    </div>


    <?php
}



if ($cleanPost['request'] === 'filter_date') {
    $departure = $_POST['departure'];
    $arrival = $_POST['arrival'];


    $daysResult = $Db->getAll("SELECT DISTINCT t.days
                FROM `" . DB_PREFIX . "_tours` t
                LEFT JOIN `" . DB_PREFIX . "_cities` dc ON dc.id = t.departure
                LEFT JOIN `" . DB_PREFIX . "_cities` ac ON ac.id = t.arrival
                LEFT JOIN `" . DB_PREFIX . "_cities` dcountry ON dcountry.id = dc.section_id
                LEFT JOIN `" . DB_PREFIX . "_buses` b ON t.bus = b.id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops_prices` tsp ON tsp.tour_id = t.id
                LEFT JOIN `" . DB_PREFIX . "_tours_stops` ts ON ts.tour_id = t.id
                WHERE t.active = '1' AND (t.departure = ".(int)$cleanPost['departure']." OR t.id
                IN(SELECT tour_id FROM `" .  DB_PREFIX . "_tours_stops_prices`  WHERE from_stop
                IN(SELECT id FROM `".DB_PREFIX."_cities` WHERE section_id = '".(int)$cleanPost['departure']."' ) ))
                 AND (t.arrival = ".(int)$cleanPost['arrival']." OR t.id
                IN(SELECT tour_id FROM `".DB_PREFIX."_tours_stops_prices` WHERE to_stop
                IN(SELECT id FROM `".DB_PREFIX."_cities` WHERE section_id = '".(int)$cleanPost['arrival']."' ) ))
                ORDER BY dc.section_id ASC,tsp.price DESC");

    // Выводим результат запроса для отладки

    $highlightedDays = []; // Очистка массива перед использованием

    foreach ($daysResult as $row) {
        // Проверка существования ключа 'days'
        if (isset($row['days'])) {
            $tourDays = explode(',', $row['days']);
            foreach ($tourDays as $day) {
                if (!in_array($day, $highlightedDays)) {
                    $highlightedDays[] = $day;
                }
            }
        }
    }



    $highlightedDays = array_unique($highlightedDays);
    $highlightedDaysString = implode(",", $highlightedDays);

    // Завершаем выполнение скрипта, чтобы не было дублирования вывода
    exit($highlightedDaysString);
}
if ($cleanPost['request'] === 'booking_date') {
    if (isset($_POST['date'])) {
        // Обновляем дату в сессии
        $_SESSION['order']['date'] = $_POST['date'];
        echo 'Дата обновлена: ' . $_POST['date'];
    } else {
        echo 'Дата не указана.';
    }
}


if ($cleanPost['request'] === 'order_mail') {
    function decodeHtmlEntities($data) {
        if (is_array($data)) {
            return array_map('decodeHtmlEntities', $data);
        } elseif (is_string($data)) {
            return html_entity_decode($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return $data;
    }

    $_POST = decodeHtmlEntities($cleanPost);

    $ticketInfo = isset($_POST['ticket_info']) ? $_POST['ticket_info'] : null;
    $order = isset($_POST['order']) ? $_POST['order'] : null;

    if ($ticketInfo && $order) {
        $departureCity = $ticketInfo['departure_city'];
        $departureStation = $ticketInfo['departure_station'];
        $departureTime = substr($ticketInfo['departure_time'], 0, 5);
        $arrivalCity = $ticketInfo['arrival_city'];
        $arrivalStation = $ticketInfo['arrival_station'];
        $date = $order['date'];
        $passengers = $order['passengers'];
        $email = $order['email'];
        $name = $order['name'];
        $familyName = $order['family_name'];
        $phone = $order['phone'];
        $fromCity = $order['fromCity'];
        $toCity = $order['toCity'];
        $price = $ticketInfo['price'];
        $passengersData = $order['passengers_data'];
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $to1 = env('MAIL_ADMIN');  // Замените на email администратора max210183@ukr.net
        //$to1 = $email;  // Замените на email администратора max210183@ukr.net
        $subject1 = 'Новый заказ';
        $message1 = "
        <html>
        <head>
            <title>Нове замовлення</title>
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
            </div>";
        $indexOfPass = 1;
        $message1 .= ($passengers > 1) ? "<p>Заброньовані квитки:</p><p>Кількість квитків " . $passengers . "</p>" : "<p>Заброньован квиток:</p>";
        $message1 .= "<div class='email-content'>
                        <table>
                        <tr>
                            <td class='email-titles'>$indexOfPass</td>
                            <td></td>
                        </tr>
                        <tr>
                        <td class='email-titles'>Бронь</td>
                        <td>$indexOfPass/$passengers</td>
                    </tr>
                        <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Від пункту</td>
                        <td>$fromCity $departureStation в $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>До пункту</td>
                        <td>$toCity $arrivalStation</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Дата та час</td>
                        <td>$date</td>
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
                        <td class='email-titles'>Ціна</td>
                        <td>$price</td>
                    </tr>";

        if ($passengers > 1) {
            $totalPriceForBooking = $price*$passengers;
            foreach ($passengersData as $pass) {
                ++$indexOfPass;
                $message1 .= "
                        <tr>
                            <td class='email-titles'>$indexOfPass</td>
                            <td></td>
                        </tr>
                        <tr>
                        <td class='email-titles'>Бронь</td>
                        <td>$indexOfPass/$passengers</td>
                    </tr>
                <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Від пункту</td>
                        <td>$fromCity $departureStation в $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>До пункту</td>
                        <td>$toCity $arrivalStation</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Дата та час</td>
                        <td>$date</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>" . $pass['name'] . " " .  $pass['family_name'] . "</td>
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
                ";
            }
            $message1 .= "
                    <tr>
                        <td class='email-titles'>Сума бронювань</td>
                        <td>$totalPriceForBooking</td>
                    </tr>
            ";
        }

        $message1 .= "
                    <tr>
                        <td class='email-titles'>Спосіб оплати</td>
                        <td>Готівка</td>
                    </tr>
                </table>
            </div>
        </body>
        </html>
        ";
        $imagePath = asset('images/legacy/upload/logos/mailLogo.jpeg');
        $to2 = $email;
        $subject2 = 'Бронювання квитка';
        $ticketTitle = ($passengers > 1) ? "Інформація про заброньовані квитки" : "Інформація про заброньованний квиток";
        $indexOfPass = 1;
        $message2 = "<html>
        <head>
            <title>$ticketTitle:</title>
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

                .logo {
                    max-width: 150px;
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
            <p><b>Дякуємо, що обрали нашу компанію!</b> <br>$ticketTitle:</p>
            <div class='email-content'>
            <table>
            <tr>
                            <td class='email-titles'>$indexOfPass</td>
                            <td></td>
                        </tr>
                        <tr>
                        <td class='email-titles'>Бронь</td>
                        <td>$indexOfPass/$passengers</td>
                    </tr>
            <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Від пункту</td>
                        <td>$fromCity $departureStation в $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>До пункту</td>
                        <td>$toCity $arrivalStation</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Дата та час</td>
                        <td>$date</td>
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
                        <td class='email-titles'>Ціна</td>
                        <td>$price</td>
                    </tr>";

        if ($passengers > 1) {
            $totalPriceForBooking = $price*$passengers;
            foreach ($passengersData as $pass) {
                ++$indexOfPass;
                $message2 .= "
                    <tr>
                            <td class='email-titles'>$indexOfPass</td>
                            <td></td>
                        </tr>
                        <tr>
                        <td class='email-titles'>Бронь</td>
                        <td>$indexOfPass/$passengers</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Рейс</td>
                        <td>$departureCity - $arrivalCity</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Від пункту</td>
                        <td>$fromCity $departureStation в $departureTime</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>До пункту</td>
                        <td>$toCity $arrivalStation</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Дата та час</td>
                        <td>$date</td>
                    </tr>
                    <tr>
                        <td class='email-titles'>Пасажир</td>
                        <td>" . $pass['name'] . " " .  $pass['family_name'] . "</td>
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
            ";
            }
            $message2 .= "<tr>
                        <td class='email-titles'>Сума бронювань</td>
                        <td>$totalPriceForBooking</td>
                    </tr>";
        }


        $message2 .= "
                </table>
            </div>
            <p>У вартість квитка включено перевезення одного місця багажу вагою до 25 кг. За кожну додаткову одиницю багажу передбачена доплата в розмірі 10% від вартості квитка.</p>
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
            echo 'ok';
        } catch (Exception $e) {
            echo 'Message could not be sent. Error: ' . $e->getMessage();
        }

        try {
            sendMail($to2, $subject2, $message2, $headers);
            echo 'Второе сообщение отправлено успешно';
        } catch (Exception $e) {
            echo 'Ошибка отправки второго сообщения. Error: ' . $e->getMessage();
        }

    } else {
        echo 'error';
    }
}

