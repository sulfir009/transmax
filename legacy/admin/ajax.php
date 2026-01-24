<?php require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
if(!isset($_POST) || empty($_POST) || !isset($_POST['request'])){exit;}
$cleanPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);


if ($cleanPost['request'] === 'continue') {

    if (!$Db) {
        echo "Database connection not established.<br>";
        return;
    }

    $today = new DateTime();

    // Получаем все маршруты
    $routes = $Db->getAll("SELECT * FROM " . DB_PREFIX . "_tours");

    if (!$routes) {
        echo "No routes found.<br>";
        return;
    }

    foreach ($routes as $route) {
        echo "Processing route ID: " . $route['id'] . "<br>";

        // Получаем модификатор дней для текущего маршрута
        $futureDaysModifier = (int)$route['races_future_date'];
        $futureDate = (new DateTime())->modify('+' . $futureDaysModifier . ' days');
        echo "future_days_modifier: " . $futureDaysModifier . "<br>";
        echo "Today: " . $today->format('Y-m-d') . "<br>";
        echo "Future Date: " . $futureDate->format('Y-m-d') . " for route ID: " . $route['id'] . "<br>";

        // Генерируем даты для будущих дней, согласно модификатору
        $period = new DatePeriod($today, new DateInterval('P1D'), $futureDate);
        foreach ($period as $date) {
            // Проверяем день недели и добавляем запись, если маршрут активен в этот день
            $dayOfWeek = $date->format('N');
            echo "Date: " . $date->format('Y-m-d') . " - Day of week: " . $dayOfWeek . "<br>";

            if (in_array($dayOfWeek, explode(',', $route['days']))) {
                $tourDate = $date->format('Y-m-d');
                echo "Tour Date: " . $tourDate . "<br>";
                // Проверяем, существует ли запись для этого маршрута и даты
                $existingTour = $Db->getOne("SELECT * FROM   " .  DB_PREFIX . "_tours_sales WHERE tour_id = '{$route['id']}' AND tour_date = '{$tourDate}'");

                if ($existingTour) {
                    echo "Existing tour found for date: " . $tourDate . "<br>";
                } else {
                    echo "No existing tour found for date: " . $tourDate . "<br>";
                    // Получаем количество мест в автобусе
                    $busId = $route['bus'];
                    $busCapacity = $Db->getOne("SELECT seats_qty FROM   " .  DB_PREFIX . "_buses WHERE id = '{$busId}'");
                    echo "Bus ID: " . $busId . " - Bus Capacity: " . $busCapacity['seats_qty'] . "<br>";

                    // Вставляем новую запись
                    $Db->query("INSERT INTO `" .  DB_PREFIX . "_tours_sales`(tour_id, free_tickets, tour_date, tickets_buy, tickets_order, active) VALUES ('{$route['id']}', '{$busCapacity['seats_qty']}', '{$tourDate}', '0', '0', '1')");
                    echo "New tour added for date: " . $tourDate . "<br>";
                }
            }
        }
    }
}


if ($cleanPost['request'] === 'refresh_captcha'){
    session_start();
    require_once str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/libs/captcha/simple-php-captcha.php';
    $_SESSION['captcha'] = simple_php_captcha();
    echo $_SESSION['captcha']['image_src'];
}

if ($cleanPost['request'] === 'change_theme'){
    $currentTheme = $Db->getone("SELECT theme FROM `" .  DB_PREFIX . "_users`WHERE id = '".$Admin->id."' ");
    if ((int)$currentTheme['theme'] == 1){
        $upd = $Db->query("UPDATE `" .  DB_PREFIX . "_users`SET `theme` = '2' WHERE id = '".$Admin->id."' ");
    }elseif ($currentTheme['theme'] == 2){
        $upd = $Db->query("UPDATE `" .  DB_PREFIX . "_users`SET `theme` = '1' WHERE id = '".$Admin->id."' ");
    }
    if ($upd){
        echo 'ok';
    }else{
        echo 'err';
    }
}

if($cleanPost['request']=="refresh"){
    $elem_id = (int)$cleanPost['id'];
    $table = $cleanPost['table'];

    $get_data = mysqli_query($db, "SELECT active FROM `".$table."` WHERE `id`= ".$elem_id);
    if ( $el = mysqli_fetch_array($get_data) ) {

        if ( $el['active'] == 0 )
            $active = 1;
        if ( $el['active'] == 1 )
            $active = 0;

        $ch_act = mysqli_query($db, "UPDATE `".$table."` SET active='$active' WHERE `id`= ".$elem_id);
    }
}

if ($cleanPost['request'] === 'send_messages'){
    $getClientsPhones = $Db->getAll("SELECT phone FROM `" .  DB_PREFIX . "_clients`WHERE id IN (".implode(',',array_unique($cleanPost['clients'])).") ");
    $recipients = [];
    foreach ($getClientsPhones AS $k=>$clientsPhone){
        $recipients[] = str_replace(array(')',' ','(','+'),'',$clientsPhone['phone']);
    }
    $apiKey = '40de5c81e6360bb0bfda2ada1a00304cbb4d4dfa';
    $apiUrl = 'https://api.turbosms.ua';
    $message = 'Тест отправки';
    $params = array(
        'recipients' => array_unique($recipients),
        'sms'=> array(
            "sender" => "Max Trans",
            'text' => $message,
        )
    );
    $headers = array(
        'Authorization: Bearer ' . $apiKey,
        'Content-Type: application/json',
    );
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl . '/message/send.json');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result['response_code']) && $result['response_status'] === 'OK') {
            echo 'Сообщения успешно отправлены!';
        } else {
            echo 'Ошибка при отправке сообщения: ' . $result['response_status'];
        }
    } else {
        echo 'Ошибка при выполнении запроса к API Turbosms.';
    }
}

// #regular races start

if ($cleanPost['request'] == 'create_regular_race') {
    $cleanPost = request()->all();
    $REGULAR_RACES_ALIAS_TABLE = '_regular_race_alias';
    $REGULAR_RACES_TABLE = '_regular_races';
    $uploadDir = public_path('images/pages/regular_races/');
    $imageMobName = '';
    $imageDescName = '';

    $titleRu = $cleanPost['titleRu'] ?? '';
    $titleUa = $cleanPost['titleUa'] ?? '';
    $titleEn = $cleanPost['titleEn'] ?? '';
    $tours = $cleanPost['tours'] ?? [];
    $imageMob = request()->file('image_mob');
    $imageDesc = request()->file('image_desc');

    if ($imageMob) {
        $imageMobName = uniqid('image_', true) . '.' . $imageMob->getClientOriginalExtension();
        $imageMob->move(public_path('images/pages/regular_races/'), $imageMobName);
    }

    if ($imageDesc) {
        $imageDescName = uniqid('desc_', true) . '.' . $imageDesc->getClientOriginalExtension();
        $imageDesc->move(public_path('images/pages/regular_races/'), $imageDescName);
    }

    mysqli_query($db,"
                    INSERT INTO ". DB_PREFIX . $REGULAR_RACES_ALIAS_TABLE . "
                    (title_ru, title_ua, title_en, image_mob, image_desc)
                    VALUES ('$titleRu', '$titleUa', '$titleEn', '$imageMobName', '$imageDescName')");

    $regularId = mysqli_insert_id($db);
    $tours = explode(',', $tours);
        foreach ($tours as $tourId) {
                $timeId = mysqli_query($db, "SELECT id
                    FROM `mt_tours_stops`
                    WHERE tour_id = $tourId
                    ORDER BY stop_num ASC limit 1")->fetch_assoc()["id"]?? 0;
                if (!empty($timeId)) {
                    mysqli_query($db,"
                    INSERT INTO ". DB_PREFIX . $REGULAR_RACES_TABLE . "
                    (regular_race_alias_id, tour_id, tours_stop_id)
                    VALUES ($regularId, $tourId, $timeId)");
                }


        echo json_encode([
            'status' => 204,
        ]);
    }
}


if ($cleanPost['request'] == 'edit_regular_race') {
    $cleanPost = request()->all();
    $REGULAR_RACES_ALIAS_TABLE = '_regular_race_alias';
    $REGULAR_RACES_TABLE = '_regular_races';
    $uploadDir = public_path('images/pages/regular_races/');
    $imageMobPath = null;
    $imageDescPath = null;

    $regularId = $cleanPost['regularTourId'] ?? 0;
    $titleRu = $cleanPost['titleRu'] ?? '';
    $titleUa = $cleanPost['titleUa'] ?? '';
    $titleEn = $cleanPost['titleEn'] ?? '';
    $tours = $cleanPost['tours'];
    $imageMob = request()->file('image_mob');
    $imageDesc = request()->file('image_desc');

    $tours = is_array($tours) ? $tours : explode(',', $tours);

    if (!empty($regularId)) {
        // Обновление информации о регулярном рейсе
        mysqli_query($db, "
            UPDATE " . DB_PREFIX . $REGULAR_RACES_ALIAS_TABLE . "
            SET title_ru = '$titleRu', title_ua = '$titleUa', title_en = '$titleEn'
            WHERE id = $regularId
        ");

        // Обновление остановок
        $races = mysqli_query($db, "
            SELECT id, tour_id FROM " . DB_PREFIX . $REGULAR_RACES_TABLE . "
            WHERE regular_race_alias_id = $regularId
        ");

        $racesIds = [];
        foreach ($races as $race) {
            $racesIds[] = $race['tour_id'];

            if (!in_array($race['tour_id'], $tours)) {
                mysqli_query($db, "
                    DELETE FROM `" . DB_PREFIX . $REGULAR_RACES_TABLE . "`
                    WHERE `tour_id`= " . $race['tour_id'] . "
                    AND regular_race_alias_id = $regularId
                ");
            }
        }

        foreach ($tours as $tourId) {
            if (!in_array($tourId, $racesIds)) {
                $timeId = mysqli_query($db, "SELECT id
                    FROM `mt_tours_stops`
                    WHERE tour_id = $tourId
                    ORDER BY stop_num ASC limit 1")->fetch_assoc()["id"] ?? 0;

                if (!empty($timeId)) {
                    mysqli_query($db, "
                        INSERT INTO " . DB_PREFIX . $REGULAR_RACES_TABLE . "
                        (regular_race_alias_id, tour_id, tours_stop_id)
                        VALUES ($regularId, $tourId, $timeId)
                    ");
                }
            }
        }

        // Сохранение изображения для мобильной версии
        if ($imageMob) {
            $imageMobName = uniqid('image_', true) . '.' . $imageMob->getClientOriginalExtension();
            $imageMob->move(public_path('images/pages/regular_races/'), $imageMobName);
            // Обновление пути в БД
            mysqli_query($db, "
        UPDATE ". DB_PREFIX . $REGULAR_RACES_ALIAS_TABLE . "
        SET image_mob = '$imageMobName'
        WHERE id = $regularId");
        }

        if ($imageDesc) {
            $imageDescName = uniqid('desc_', true) . '.' . $imageDesc->getClientOriginalExtension();
            $imageDesc->move(public_path('images/pages/regular_races/'), $imageDescName);
            // Обновление пути в БД
            mysqli_query($db, "
        UPDATE ". DB_PREFIX . $REGULAR_RACES_ALIAS_TABLE . "
        SET image_desc = '$imageDescName'
        WHERE id = $regularId");
        }

        echo json_encode([
            'status' => 204,
        ]);
    }
}
// #regular races end

if ($cleanPost['request'] === 'edit_sales'){
    $free_tickets = $Db->getOne("SELECT free_tickets FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".(int)$cleanPost['id']."' AND tour_date = '".$cleanPost['date']."'"); ?>

    <input type="number" class="form-control input-sm edit_tickets_num" value="<?=$free_tickets['free_tickets']?>">
    <button class="btn btn-success" title="Сохранить изменения" type="button" onclick="acceptFreeTickets(this,'<?=(int)$cleanPost['id']?>', '<?=$cleanPost['date']?>', )">
        <i class="fas fa-check"></i>
    </button>
    <?
}

if ($cleanPost['request'] === 'accept_sales_changes'){
    $edit = mysqli_query($db,"UPDATE `" .  DB_PREFIX . "_tours_sales`SET free_tickets = '".$cleanPost['tickets']."' WHERE tour_id = '".(int)$cleanPost['id']."' AND tour_date = '".$cleanPost['date']."' ");
    if($edit){
        $free_tickets = $Db->getOne("SELECT free_tickets FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".(int)$cleanPost['id']."' AND tour_date = '".$cleanPost['date']."'")
        ?>
        <?=$free_tickets['free_tickets']?>
        <button class="btn btn-default" title="Редактировать" type="button" onclick="editFreeTickets(this,'<?=$cleanPost['id']?> ','<?=$cleanPost['date']?>') ">
            <i class="fas fa-pencil-alt"></i>
        </button>

        <?
    }
}

if ($cleanPost['request'] === 'setActive_race'){
    $edit = mysqli_query($db,"UPDATE `" .  DB_PREFIX . "_tours_sales`SET active='1' WHERE id = '".(int)$cleanPost['id']."'");
    if($edit) { ?>
        <button class="btn btn-danger" title="Дезактивировать" type="button" onclick="setInactive(this,'<?=$cleanPost['id']?> ') ">
            <i class="fas fa-times"></i>
        </button>
    <? }
}

if ($cleanPost['request'] === 'setInactive_race'){
    $edit = mysqli_query($db,"UPDATE `" .  DB_PREFIX . "_tours_sales`SET active='0' WHERE id = '".(int)$cleanPost['id']."'");
    if($edit) { ?>
        <button class="btn btn-success" title="Активировать" type="button" onclick="setActive(this,'<?=$cleanPost['id']?> ') ">
            <i class="fas fa-check"></i>
        </button>
    <? }
}

if ($cleanPost['request'] === 'filterRaces') {
    $salesDate = $cleanPost['date'];
    ?>

    <table class="table m-0">
        <thead>
        <tr>
            <!--th>ID маршрута</th-->
            <th>Маршрут</th>
            <th>Автобус</th>
            <th>Куплено билетов</th>
            <th>Забронировано билетов</th>
            <th>Свободных мест</th>
            <th style="text-align:center;">Действия</th>
        </tr>
        </thead>
        <tbody>
        <? $getTableElems = $Db->getAll("SELECT * FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_date = '" .$salesDate. "'  GROUP BY tour_id,tour_date ORDER BY tour_date DESC");
        foreach ($getTableElems AS $k=>$Elem) {

            $mainInfo = $Db->getOne("SELECT
                                    departure_city.title_" . $Admin->lang . " AS departure_city,
                                    arrival_city.title_" . $Admin->lang . " AS arrival_city,
                                    b.title_".$Admin->lang." AS bus
                                    FROM `" . DB_PREFIX . "_tours` t
                                    LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = t.departure
                                    LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = t.arrival
                                    LEFT JOIN `".DB_PREFIX."_buses` b ON b.id = t.bus
                                    WHERE t.id = '".$Elem['tour_id']."'");
            $ticketsBuy = $Db->getOne("SELECT tickets_buy FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."' ");
            $ticketsOrder = $Db->getOne("SELECT tickets_order FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."' ");
            $free_tickets = $Db->getOne("SELECT free_tickets FROM `" .  DB_PREFIX . "_tours_sales`WHERE tour_id = '".$Elem['tour_id']."' AND tour_date = '".$Elem['tour_date']."'");
            $departureTimeQuery = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$Elem['tour_id']."' ORDER BY id ASC");
            $departureTime = substr($departureTimeQuery['departure_time'], 0, 5);

            ?>
            <tr <?if ($Elem['active'] == '0'){?>
                class="disabled"
            <?}?>>
                <!--td><?=$Elem['tour_id']?></td-->
                <td>
                    <b><?=date('Y.m.d',strtotime($Elem['tour_date']))?></b>
                    <div>
                        <?=$mainInfo['departure_city'].' - '.$mainInfo['arrival_city']?>
                    </div>
                    <div><?=$departureTime ?></div>
                </td>
                <td><?=$mainInfo['bus']?></td>
                <td><?=$ticketsBuy['tickets_buy']?></td>
                <td><?=$ticketsOrder['tickets_order']?></td>
                <td class="free_tickets_td"><?=$free_tickets['free_tickets']?>
                    <button class="btn btn-default" title="Редактировать" type="button" onclick="editFreeTickets(this,'<?=$Elem['tour_id']?> ','<?=$Elem['tour_date']?>') ">
                        <i class="fas fa-pencil-alt"></i>
                    </button>
                </td>
                <td align="center" width="210">
                    <div class="btn-group wgroup">
                        <? if ($ticketsBuy['tickets_buy'] > 0 || $ticketsOrder['tickets_order'] > 0) { ?>
                            <a href="races/pdf.php?id=<?= $Elem['tour_id'] ?>&date=<?=$Elem['tour_date']?>" class="btn btn-default" title="скачать ведомость">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        <? } ?>
                        <a href="races/edit.php?id=<?= $Elem['tour_id'] ?>&date=<?=$Elem['tour_date']?>" class="btn btn-default" title="Посмотреть детали">
                            <i class="fas fa-folder-open"></i>
                        </a>
                        <? if ($Elem['active'] == '0') {?>
                            <button class="btn btn-success" title="Активировать" type="button" onclick="setActive(this,'<?=$Elem['id']?> ') ">
                                <i class="fas fa-check"></i>
                            </button>
                        <?} else { ?>
                            <button class="btn btn-danger" title="Дезактивировать" type="button" onclick="setInactive(this,'<?=$Elem['id']?> ') ">
                                <i class="fas fa-times"></i>
                            </button>
                        <? } ?>
                    </div>
                </td>
            </tr>
        <? } ?>
        </tbody>
    </table> <?
}

// Обработчик отправки билета
if ($cleanPost['request'] === 'send_ticket') {
    header('Content-Type: application/json');

    try {
        $orderId = (int)$cleanPost['order_id'];

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Неверный ID заказа']);
            exit;
        }

        $clientService = new \App\Service\ClientService();
        $result = $clientService->sendTicket($orderId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Билет успешно отправлен']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при отправке билета']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
    exit;
}

// Обработчик отправки брони
if ($cleanPost['request'] === 'send_booking') {
    header('Content-Type: application/json');

    try {
        $orderId = (int)$cleanPost['order_id'];

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Неверный ID заказа']);
            exit;
        }

        $clientService = new \App\Service\ClientService();
        $result = $clientService->sendBooking($orderId);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Бронь успешно отправлена']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при отправке брони']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
    exit;
}

// Обработчик возврата билета
if ($cleanPost['request'] === 'process_return') {
    header('Content-Type: application/json');

    try {
        $orderId = (int)$cleanPost['order_id'];
        $reason = $cleanPost['reason'] ?? '';

        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'Неверный ID заказа']);
            exit;
        }

        $clientService = new \App\Service\ClientService();
        $result = $clientService->processReturn($orderId, $reason);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Возврат успешно обработан']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка при обработке возврата']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
    }
    exit;
}
?>

