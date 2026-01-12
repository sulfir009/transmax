
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if (isset($_GET['file'])) {
    $file = basename($_GET['file']); // Защита от атак
    $filePath = str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/tickets_pdf/" . $file;

    if (file_exists($filePath)) {
        // Установка заголовков для скачивания файла
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        flush(); // Очистить системный буфер
        readfile($filePath); // Читаем файл и выводим его содержимое
        exit;
    } else {
        echo "Файл не найден.";
        exit;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<div class="wrapper">
    <? ?>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php' ?>
    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= $_params['title'] ?></h1>
                        <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                        <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            //updateElement($id, $_params['table'], array('active' => $active));
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}
        ?>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card">
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="custom-content-above-home-tab" data-toggle="pill"
                               href="#tab_1" role="tab" aria-controls="tab_1" aria-selected="true">Общие данные</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="custom-content-above-home-tab" data-toggle="pill"
                               href="#tab_2" role="tab" aria-controls="tab_2" aria-selected="true">Пассажиры</a>
                        </li>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <? $mainInfo = $Db->getOne("SELECT
                                    departure_city.title_" . $Admin->lang . " AS departure_city,
                                    arrival_city.title_" . $Admin->lang . " AS arrival_city
                                    FROM `" . DB_PREFIX . "_tours` t
                                    LEFT JOIN `" . DB_PREFIX . "_cities` departure_city ON departure_city.id = t.departure
                                    LEFT JOIN `" . DB_PREFIX . "_cities` arrival_city ON arrival_city.id = t.arrival
                                    WHERE t.id = '".$id."' ");
                            $departureTime = $Db->getOne("SELECT departure_time FROM `" .  DB_PREFIX . "_tours_stops`WHERE tour_id = '".$id."' ORDER BY id ASC");
                            ?>
                            <div class="form-group">
                                <label class="col-md-3">Маршрут</label>
                                <div class="col-md-9">
                                    <?=$mainInfo['departure_city'].' - '.$mainInfo['arrival_city']?>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-3">Дата маршрута</label>
                                <div class="col-md-9"><b><?=date('Y.m.d',strtotime($_GET['date'])).' '.date('H:i',strtotime($departureTime['departure_time']))?></b></div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab_2" role="tabpanel" aria-labelledby="tab_2">
                            <?$getPassengers = $Db->getAll("SELECT
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
                            o.client_name,
                            o.client_email,
                            o.client_phone,
                            o.payment_status,
                            o.uniqid,
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
                            ORDER BY o.date DESC"); ?>
                            <div class="table-responsive table-striped table-valign-middle">
                                <table class="table m-0">
                                    <thead>
                                    <tr>
                                        <th>
                                            <input type="checkbox" id="select_all" onchange="toggleAllPassangers(this)">
                                        </th>
                                        <th>Дата оформления заказа</th>
                                        <th>Имя</th>
                                        <th>Номер телефона</th>
                                        <th>Откуда - куда</th>
                                        <!--                                        <th>Пассажиров</th>-->
                                        <th>Статус оплаты</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?foreach ($getPassengers AS $k=>$order) {?>
                                        <tr>
                                        <?if ($order['passagers'] > 1) {
                                            $getOrderPassengers = $Db->getAll("SELECT p.name, p.second_name, p.ticket_return, p.return_reason, p.return_payment_type, return_reason.title_".$Admin->lang." AS return_reason FROM `" .  DB_PREFIX . "_orders_passangers`p
                                                LEFT JOIN `" .  DB_PREFIX . "_return_reasons`return_reason ON return_reason.id = p.return_reason
                                                WHERE order_id = '".$order['uniqid']."'");
                                            ?>

                                            <?foreach ($getOrderPassengers AS $k=>$passenger) {?>
                                                <tr>
                                                    <td><input type="checkbox" class="passanger_check" value="<?=$order['client_id']?>"></td>
                                                    <td>
                                                        <?=date('Y.m.d H:i:s',strtotime($order['date']))?>
                                                        <?if ($passenger['ticket_return'] == '1' || $order['ticket_return'] == '1'){?>
                                                            <div>
                                                                <b style="color: red">
                                                                    Клиент вернул билет
                                                                </b>
                                                            </div>
                                                            <div>
                                                                Причина возврата - <?=$passenger['return_reason']?>
                                                            </div>
                                                            <div>
                                                                Дата возврата - <?=date('d.m.Y H:i:s',strtotime($order['return_date']))?>
                                                            </div>
                                                            <div>
                                                                Вернуть деньги -
                                                                <?switch ($order['return_payment_type'] || $passenger['return_payment_type']){
                                                                    case 1:
                                                                        echo 'На карту';
                                                                        break;
                                                                    case 2:
                                                                        echo 'Наличными';
                                                                        break;
                                                                }?>
                                                            </div>
                                                        <?}?>
                                                    </td>
                                                    <td><?=$passenger['name'].' '.$passenger['second_name'].' '.$passenger['patronymic']?></td>
                                                    <td><?=$order['client_phone']?></td>
                                                    <td><?=$order['departure_city'].' '.$order['departure_station'].' - '.$order['arrival_city'].' '.$order['arrival_station']?></td>
                                                    <!--                                                <td>--><?php //=$order['passagers']?><!--</td>-->
                                                    <td><?php
                                                        $orderId = $order['id']; // Или другой идентификатор, используемый для создания имени файла
                                                        $pdfFileName = "ticket_" . $orderId . ".pdf";
                                                        switch ($order['payment_status']) {
                                                            case 1:
                                                                echo 'Наличными';
                                                                break;
                                                            case 2:
                                                                echo 'Оплачено картой  <div class="btn-group wgroup">
                                                                    <a class="btn btn-default"  href="?file=' . urlencode($pdfFileName) . '">
                            <i class="fas fa-file-pdf"></i>
                          </a></div>';
                                                                break;
                                                        }
                                                        ?></td>

                                                </tr>
                                            <?} } else {?>
                                            <td><input type="checkbox" class="passanger_check" value="<?=$order['client_id']?>"></td>
                                            <td>
                                                <?=date('Y.m.d H:i:s',strtotime($order['date']))?>
                                                <?if ($order['ticket_return'] == '1'){?>
                                                    <div>
                                                        <b style="color: red">
                                                            Клиент вернул билет
                                                        </b>
                                                    </div>
                                                    <div>
                                                        Причина возврата - <?=$order['return_reason']?>
                                                    </div>
                                                    <div>
                                                        Дата возврата - <?=date('d.m.Y H:i:s',strtotime($order['return_date']))?>
                                                    </div>
                                                    <div>
                                                        Вернуть деньги -
                                                        <?switch ($order['return_payment_type']){
                                                            case 1:
                                                                echo 'На карту';
                                                                break;
                                                            case 2:
                                                                echo 'Наличными';
                                                                break;
                                                        }?>
                                                    </div>
                                                <?}?>
                                            </td>
                                            <td><?=$order['client_name'].' '.$order['second_name'].' '.$order['patronymic']?></td>
                                            <td><?=$order['client_phone']?></td>
                                            <td><?=$order['departure_city'].' '.$order['departure_station'].' - '.$order['arrival_city'].' '.$order['arrival_station']?></td>
                                            <td><?=$order['passagers']?></td>
                                            <td><?php
                                                $orderId = $order['id'];
                                                $pdfFileName = "ticket_" . $orderId . ".pdf";
                                                switch ($order['payment_status']) {
                                                    case 1:
                                                        echo 'Наличными';
                                                        break;
                                                    case 2:
                                                        echo 'Оплачено картой  <div class="btn-group wgroup">
                                                                    <a class="btn btn-default"  href="?file=' . urlencode($pdfFileName) . '">
                            <i class="fas fa-file-pdf"></i>
                          </a></div>';
                                                        break;
                                                }
                                                ?></td>
                                        <?}?>
                                        </tr>
                                    <? } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer" style="text-align: center">
                                <button type="button" class="btn btn-success btn-lg" onclick="sendMassages()">Отправить смс</button>
                            </div>
                        </div>
                    </div>
                    <?/*
                    <div class="card-footer" style="text-align: center">
                        <input type="submit" class="btn btn-success btn-lg" value="Сохранить" name="ok"/>
                    </div>*/?>
                </form>
            </div><!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
</div>
<!-- ./wrapper -->
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
<script>
    $('.txt_editor').summernote();
    function toggleAllPassangers(item){
        if ($(item).is(':checked')){
            $('.passanger_check').prop('checked',true);
        }else{
            $('.passanger_check').prop('checked',false);
        }
    };

    function sendMassages(){
        if ($('.passanger_check:checked').length > 0){
            let clients = [];
            $('.passanger_check:checked').each(function(){
                clients.push($(this).val())
            });
            initLoader();
            $.ajax({
               type:'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
                data:{
                    'request':'send_messages',
                    'clients':clients
                },
                success:function(answer){
                    $('.loader').remove();
                    $('.passanger_check').prop('checked',false);
                    $('#select_all').prop('checked',false);
                    if ($.trim(answer) != ''){
                        alert($.trim(answer));
                    }
                }
            })
        }else{
            alert('Отметьте минимум 1 пассажира');
        }
    }
</script>
</body>
</html>
