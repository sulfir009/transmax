<?php
error_reporting(3);
require_once '/home/vv513819/maxtransltd.com/www/config.php';

require_once '/home/vv513819/maxtransltd.com/www/admin/engine/db.php';
require_once'/home/vv513819/maxtransltd.com/www/admin/engine/CMain.php';
require_once'/home/vv513819/maxtransltd.com/www/admin/engine/CRouter.php';
require_once '/home/vv513819/maxtransltd.com/www/admin/engine/CDb.php';
require_once '/home/vv513819/maxtransltd.com/www/admin/engine/functions.php';


$Db = new CDb();
$Router = new CRouter();
$Main = new CMain();


$GLOBALS['site_settings'] = $Main->GetDefineSettings();
$GLOBALS['auth_fields'] = array('email');

function addFutureTours() {
    global $Db; // Использование глобальной переменной

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
                $existingTour = $Db->getOne("SELECT * FROM   " .  DB_PREFIX . "_tours_sales`WHERE tour_id = '{$route['id']}' AND tour_date = '{$tourDate}'");

                if ($existingTour) {
                    echo "Existing tour found for date: " . $tourDate . "<br>";
                } else {
                    echo "No existing tour found for date: " . $tourDate . "<br>";
                    // Получаем количество мест в автобусе
                    $busId = $route['bus'];
                    $busCapacity = $Db->getOne("SELECT seats_qty FROM   " .  DB_PREFIX . "_buses`WHERE id = '{$busId}'");
                    echo "Bus ID: " . $busId . " - Bus Capacity: " . $busCapacity['seats_qty'] . "<br>";

                    // Вставляем новую запись
                    $Db->query("INSERT INTO `" .  DB_PREFIX . "_tours_sales`(tour_id, free_tickets, tour_date, tickets_buy, tickets_order, active) VALUES ('{$route['id']}', '{$busCapacity['seats_qty']}', '{$tourDate}', '0', '0', '1')");
                    echo "New tour added for date: " . $tourDate . "<br>";
                }
            }
        }
    }
}

addFutureTours();
?>
