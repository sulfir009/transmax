<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php' ?>
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
            $active = checkboxParam('active');

            $travelHours = (int)$ar_clean['hours'];
            $travelMinutes = (int)$ar_clean['minutes'];
            $departureTimestamp = strtotime($ar_clean['departure_time']);
            $travelTimeSeconds = ($travelHours * 3600) + ($travelMinutes * 60);
            $arrivalTimestamp = $departureTimestamp + $travelTimeSeconds;
            $arrivalTime = date("H:i", $arrivalTimestamp);

            $exceptions[] = 'days';
            $exceptions[] = 'stops';
            $exceptions[] = 'departure_closed';
            $exceptions[] = 'stops_closed';

            $departureClose = date("H:i:s", strtotime($ar_clean['departure_closed']));
            $stopsClose = date("H:i:s", strtotime($ar_clean['stops_closed']));
            $racesFutureDate = (int)$ar_clean['races_future_date'];

            $days = implode(',',$ar_clean['days']);

            /* Обновим данные об остановках и их стоимости */
            $del = mysqli_query($db,"DELETE FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE tour_id = '".$id."' ");
            foreach ($ar_clean['stops'] AS $stopKey=>$stopValues){
                if ((int)$stopValues['price'] > 0){
                    $stopFrom = explode('-',$stopKey)[1];
                    $stopTo = explode('-',$stopKey)[2];
                    $addInfo = mysqli_query($db,"INSERT INTO `" .  DB_PREFIX . "_tours_stops_prices`(`tour_id`,`from_stop`,`to_stop`,`price`,`distance`)
                VALUES ('".$id."','".$stopFrom."','".$stopTo."','".(int)$stopValues['price']."','".(int)$stopValues['distance']."')");
                }
            }

            updateElement($id, $_params['table'], array('active' => $active,'departure_closed'=>$departureClose, 'stops_closed'=>$stopsClose, 'races_future_date'=>$racesFutureDate),$txt?? [],array('days'=>$days),$exceptions?? []);
        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}

        $db_element = mysqli_query($db, "SELECT * FROM `" . $_params['table'] . "` WHERE id='" . $id . "'");
        $Elem = mysqli_fetch_array($db_element);
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
                            <a class="nav-link" data-toggle="pill" href="#tab_2" role="tab" aria-controls="tab_2"
                               aria-selected="false">Остановки</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#tab_3" role="tab" aria-controls="tab_3"
                               aria-selected="false">Пересадки</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#tab_4" role="tab" aria-controls="tab_4"
                               aria-selected="false">Тарифная таблица</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="pill" href="#tab_5" role="tab" aria-controls="tab_4"
                               aria-selected="false">Параметры</a>
                        </li>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <? editElem('active', 'Активный', '3', $Elem, '', 'edit'); ?>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Город отправления*
                                </label>
                                <div class="col-sm-3">
                                    <select name="departure" class="custom-select" required>
                                        <?$getCities = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND section_id > 0 AND station = 0 ORDER BY title_".$Admin->lang." ASC");
                                        foreach ($getCities AS $k=>$city){?>
                                            <option value="<?=$city['id']?>" <?if ($city['id'] == $Elem['departure']){echo 'selected';}?>><?=$city['title']?></option>
                                        <?}?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Город прибытия*
                                </label>
                                <div class="col-sm-3">
                                    <select name="arrival" class="custom-select" required>
                                        <?foreach ($getCities AS $k=>$city){?>
                                            <option value="<?=$city['id']?>" <?if ($city['id'] == $Elem['arrival']){echo 'selected';}?>><?=$city['title']?></option>
                                        <?}?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Автобус*
                                </label>
                                <div class="col-sm-3">
                                    <select name="bus" class="custom-select" required>
                                        <? $getBuses = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_buses`WHERE active = '1' ORDER BY sort DESC");
                                        foreach ($getBuses as $k => $bus) {
                                            ?>
                                            <option value="<?= $bus['id'] ?>" <? if ($Elem['bus'] == $bus['id']) {
                                                echo 'selected';
                                            } ?>><?= $bus['title'] ?></option>
                                        <? } ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Периодичность*
                                </label>
                                <div class="col-sm-3">
                                    <select name="days[]" class="select2" required multiple>
                                        <option value="" hidden disabled>--- Выберите дни недели, когда рейс активен ---</option>
                                        <option value="1" <?if (substr_count($Elem['days'],1)){echo 'selected';}?>>Понедельник</option>
                                        <option value="2" <?if (substr_count($Elem['days'],2)){echo 'selected';}?>>Вторник</option>
                                        <option value="3" <?if (substr_count($Elem['days'],3)){echo 'selected';}?>>Среда</option>
                                        <option value="4" <?if (substr_count($Elem['days'],4)){echo 'selected';}?>>Четверг</option>
                                        <option value="5" <?if (substr_count($Elem['days'],5)){echo 'selected';}?>>Пятница</option>
                                        <option value="6" <?if (substr_count($Elem['days'],6)){echo 'selected';}?>>Суббота</option>
                                        <option value="7" <?if (substr_count($Elem['days'],7)){echo 'selected';}?>>Воскресенье</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="tab_2" role="tabpanel" aria-labelledby="tab_2">
                            <div class="form-group">
                                <div class="col-sm-2">
                                    <label class="col-sm-12"> Остановка </label>
                                    <label>
                                        <input type="checkbox" id="is_kpp_checkbox"> Это КПП
                                    </label>
                                    <div class="col-sm-12">
                                        <select class="select2" id="station_select">
                                            <? foreach ($getCities as $k => $city) { ?>
                                                <?$getCityStations = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND station = 1 AND section_id = '".$city['id']."' ORDER BY sort DESC,title_".$Admin->lang." ASC  ");
                                                foreach ($getCityStations AS $key=>$station){?>
                                                    <option value="<?= $station['id'] ?>"><?=$city['title'].' '.$station['title']?></option>
                                                <?}?>
                                            <? } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-8">
                                    <div class="row">
                                        <div class="col-sm-3">
                                            <label class="col-sm-12">Время отправления</label>
                                            <div class="col-sm-12">
                                                <input type="time" class="form-control input-sm" id="arrival_time">
                                            </div>
                                        </div>
                                        <!--div class="col-sm-3">
                                            <label class="col-sm-12">Время отправления Б-А</label>
                                            <div class="col-sm-12">
                                                <input type="time" class="form-control input-sm" id="departure_time">
                                            </div>
                                        </div-->
                                        <div class="col-sm-3">
                                            <label class="col-sm-12">Порядковый №</label>
                                            <div class="col-sm-12">
                                                <input type="number" class="form-control input-sm" id="stop_num" value="1">
                                            </div>
                                        </div>
                                        <div class="col-sm-3">
                                            <label class="col-sm-12">Сутки прибытия</label>
                                            <div class="col-sm-12">
                                                <input type="number" class="form-control input-sm" id="arrival_day" value="1">
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-sm-2 mt-4">
                                    <button class="btn btn-success btn-lg" type="button" onclick="addStop()">
                                        Добавить
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label class="col-sm-12">Текущие остановки</label>
                                <h5>
                                    Остановки выстроены по порядковому номеру от наименьшего к наибольшему. Если вы изменили порядковый номер какой то остановки, все остановки изменят свою очередь после перезагрузки страницы!
                                    Сутки прибытия на публичной части не выводятся, влияют на расчет времени в пути. Ставятся из расчета 24 часа от момента отправления = 2-е сутки. Первые сутки можно оставлять 0.
                                </h5>
                            </div>
                            <table class="table m-0 current_stops_table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Остановка</th>
                                        <th>Время отправления</th>
                                        <!--th>Время отправления Б-А</th-->
                                        <th>Порядковый №</th>
                                        <th>Сутки прибытия</th>
                                        <th style="text-align:center;">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <? $getCurrentStops = $Db->getAll("SELECT ts.id,ts.arrival_time,ts.departure_time,ts.stop_num,ts.arrival_day,city.title_".$Admin->lang." AS city,station.title_".$Admin->lang." AS station FROM `" .  DB_PREFIX . "_tours_stops`ts
                                    LEFT JOIN `" .  DB_PREFIX . "_cities`station ON station.id = ts.stop_id
                                     LEFT JOIN `" .  DB_PREFIX . "_cities`city ON city.id = station.section_id
                                      WHERE ts.tour_id = '".$id."' ORDER BY ts.stop_num ASC");
                                foreach ($getCurrentStops AS $k=>$currentStop) {
                                    $currentStopName = $Db->getone("SELECT title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE id = '".$currentStop['stop_id']."' ");
                                    ?>
                                    <tr draggable="true" data-stopid="<?=$currentStop['id']?>" ondragstart="dragStart(event)">
                                        <td><?=$currentStop['id']?></td>
                                        <td>
                                            <?=$currentStop['city'].' '.$currentStop['station']?>
                                        </td>
                                        <td><?=date('H:i',strtotime($currentStop['arrival_time']))?></td>
                                        <!--td><?=date('H:i',strtotime($currentStop['departure_time']))?></td-->
                                        <td><?=$currentStop['stop_num']?></td>
                                        <td><?=$currentStop['arrival_day']?></td>
                                        <td align="center" width="210">
                                            <div class="btn-group wgroup">
                                                <button class="btn btn-default" title="Редактировать" type="button" onclick="editStop(this,'<?=$currentStop['id']?>')">
                                                    <i class="fas fa-pencil-alt"></i>
                                                </button>
                                                <button class="btn btn-danger" title="Удалить" type="button" onclick="deleteStop(this,'<?=$currentStop['id']?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <? } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="tab_3" role="tabpanel" aria-labelledby="tab_3">
                            <div class="form-group">
                                <label class="col-sm-12"> Добавить пересадку </label>
                                <div class="col-sm-3">
                                    <select class="custom-select" id="transfer_select">
                                        <? $getCities = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = '1' AND section_id > 0 AND station = '0' ORDER BY sort DESC");
                                        foreach ($getCities as $k => $city) {
                                            ?>
                                            <optgroup label="<?= $city['title'] ?>">
                                                <? $getStations = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = '1' AND section_id = '" . $city['id'] . "' ");
                                                foreach ($getStations as $k => $station) {
                                                    ?>
                                                    <option value="<?= $station['id'] ?>"><?= $station['title'] ?></option>
                                                <? } ?>
                                            </optgroup>
                                        <? } ?>
                                    </select>
                                </div>
                                <div class="col-sm-3">
                                    <button class="btn btn-success btn-lg" type="button" onclick="addTransfer()">
                                        Добавить пересадку
                                    </button>
                                </div>
                            </div>
                            <hr>
                            <div class="form-group">
                                <label class="col-sm-12">Текущие пересадки</label>
                            </div>
                            <table class="table m-0 current_transfers_table">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Место пересадки</th>
                                    <th style="text-align:center;">Действия</th>
                                </tr>
                                </thead>
                                <tbody>
                                <? $getCurrentTransfers = $Db->getAll("SELECT id,transfer_station_id FROM `" .  DB_PREFIX . "_tours_transfers`WHERE tour_id = '".$id."' ");
                                foreach ($getCurrentTransfers AS $k=>$currentTransfer) {
                                    $currentTransferName = $Db->getone("SELECT title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE id = '".$currentTransfer['transfer_station_id']."' ");
                                    ?>
                                    <tr>
                                        <td><?=$currentTransfer['id']?></td>
                                        <td>
                                            <select class="custom-select transfer_select">
                                                <? $getCities = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = '1' AND section_id > 0 AND station = '0' ORDER BY sort DESC");
                                                foreach ($getCities as $k => $city) {
                                                    ?>
                                                    <optgroup label="<?= $city['title'] ?>">
                                                        <? $getStations = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = '1' AND section_id = '" . $city['id'] . "' ");
                                                        foreach ($getStations as $k => $station) {
                                                            ?>
                                                            <option value="<?= $station['id'] ?>" <? if ($station['id'] == $currentTransfer['transfer_station_id']) {
                                                                echo 'selected';
                                                            } ?>><?= $station['title'] ?></option>
                                                        <? } ?>
                                                    </optgroup>
                                                <? } ?>
                                            </select>
                                        </td>
                                        <td align="center" width="210">
                                            <div class="btn-group wgroup">
                                                <button class="btn btn-danger" title="Удалить" type="button" onclick="deleteTransfer(this,'<?=$currentTransfer['id']?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <? } ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="tab_4" role="tabpanel" aria-labelledby="tab_4">
                            <h3>В каждой ячейке верхнее поле ввода - стоимость, нижнее поле ввода - расстояние. Вводить только числовые значения!Без букв или каких либо символов</h3>
                            <hr>
                            <table class="table m-0 tarif_table">
                                <thead>
                                <?
                                $currentStopsPrices = $Db->getAll("SELECT * FROM `" .  DB_PREFIX . "_tours_stops_prices`WHERE tour_id = '".$id."' ");
                                $currentPricesArray = [];
                                foreach ($currentStopsPrices AS $key=>$currentStopsPrice){
                                    $currentPricesArray[$currentStopsPrice['from_stop'].'-'.$currentStopsPrice['to_stop']]['price'] = $currentStopsPrice['price'];
                                    $currentPricesArray[$currentStopsPrice['from_stop'].'-'.$currentStopsPrice['to_stop']]['distance'] = $currentStopsPrice['distance'];
                                }
                                $departure = $Db->getOne("SELECT cc.id,cc.title_".$Admin->lang." AS station,c.title_".$Admin->lang." AS city FROM `" .  DB_PREFIX . "_cities`c
                                LEFT JOIN `" .  DB_PREFIX . "_cities`cc ON cc.section_id = c.id
                                WHERE cc.id = '".$Elem['departure']."' ");
                                $arrival = $Db->getOne("SELECT cc.id,cc.title_".$Admin->lang." AS station,c.title_".$Admin->lang." AS city FROM `" .  DB_PREFIX . "_cities`c
                                LEFT JOIN `" .  DB_PREFIX . "_cities`cc ON cc.section_id = c.id
                                WHERE cc.id = '".$Elem['arrival']."' "); ?>
                                    <tr>
                                        <th>
                                            От/До
                                        </th>
                                        <?$getRouteStops = $Db->getAll("SELECT ts.id,ts.stop_id,ts.arrival_time,city.title_".$Admin->lang." AS city,station.title_".$Admin->lang." AS station
                                        FROM `" .  DB_PREFIX . "_tours_stops`ts
                                        LEFT JOIN `" .  DB_PREFIX . "_cities`station ON station.id = ts.stop_id
                                        LEFT JOIN `" .  DB_PREFIX . "_cities`city ON city.id = station.section_id
                                        WHERE ts.tour_id = '".$Elem['id']."' ORDER BY ts.stop_num ");
                                        for($i = 1;$i < count($getRouteStops) ;$i++){?>
                                            <th>
                                                <?=$getRouteStops[$i]['city'].' '.$getRouteStops[$i]['station']?>
                                            </th>
                                        <?}?>
                                    </tr>
                                </thead>
                                <tbody>
                                <?for ($i = 0;$i < (count($getRouteStops) - 1) ;$i++){?>
                                    <tr>
                                        <td>
                                            <?=$getRouteStops[$i]['city'].' '.$getRouteStops[$i]['station']?>
                                        </td>
                                        <?for ($x = 1;$x < count($getRouteStops);$x++){?>
                                            <td>
                                                <?if ($x >= ($i + 1) ){?>
                                                    <input type="text" placeholder="Стоимость" class="form-control input-sm" name="stops[stop-<?=$getRouteStops[$i]['stop_id']?>-<?=$getRouteStops[$x]['stop_id']?>][price]" value="<?=$currentPricesArray[$getRouteStops[$i]['stop_id'].'-'.$getRouteStops[$x]['stop_id']]['price']?>">
                                                    <input type="text" placeholder="Расстояние" class="form-control input-sm" name="stops[stop-<?=$getRouteStops[$i]['stop_id']?>-<?=$getRouteStops[$x]['stop_id']?>][distance]" value="<?=$currentPricesArray[$getRouteStops[$i]['stop_id'].'-'.$getRouteStops[$x]['stop_id']]['distance']?>">
                                                <?}?>
                                            </td>
                                        <?}?>
                                    </tr>
                                <?}?>
                                </tbody>
                            </table>
                        </div>
                        <div class="tab-pane fade" id="tab_5" role="tabpanel" aria-labelledby="tab_5">
                            <? $tourParams = $Db->getAll("SELECT departure_closed, stops_closed, races_future_date FROM `" .  DB_PREFIX . "_tours`WHERE id =".$Elem['id']."");
                            ?>
                            <table>
                                <tbody>
                                <tr>
                                    <td>Глубина продажи (Сколько дней наперед рейс доступен к покупке)</td>
                                    <td><input type="number" class="form-control input-sm edit_departure_time" name="races_future_date" value="<?=$tourParams['0']['races_future_date']?>"></td>
                                </tr>
                                <tr>
                                    <td>Закрытие продажи из начального пункта за</td>
                                    <td><input type="time" class="form-control input-sm edit_departure_time" name="departure_closed" value="<?=$tourParams['0']['departure_closed']?>"></td>
                                </tr>
                                <tr>
                                    <td>Закрытие продажи из промежуточных пунктов за</td>
                                    <td><input type="time" class="form-control input-sm edit_departure_time" name="stops_closed" value="<?=$tourParams['0']['stops_closed']?>"></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="card-footer" style="text-align: center">
                        <input type="submit" class="btn btn-success btn-lg" value="Сохранить" name="ok" onclick="saveData()"/>
                    </div>
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

    function editStop(item,id){
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data:{
                'request':'edit_stop',
                'id':id
            },
            success:function(response){
                removeLoader();
                if ($.trim(response) !== 'err'){
                    $(item).closest('tr').html(response);
                    $('.select2').select2();
                }else {
                    alert('Ошибка!');
                }
            }
        })
    }

    function acceptStopChanges(item,id){
        let station = $(item).closest('tr').find('.edit_station_id').val();
        let arrivalTime = $(item).closest('tr').find('.edit_arrival_time').val();
        let departureTime = $(item).closest('tr').find('.edit_departure_time').val();
        let stopNum = $(item).closest('tr').find('.edit_stop_num').val();
        let arrivalDay = $(item).closest('tr').find('.edit_arrival_day').val();
        initLoader();
        $.ajax({
           type:'post',
            headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data:{
                'request':'accept_stop_changes',
                'id':id,
                'station':station,
                'station_arrival':arrivalTime,
                'station_departure':arrivalTime,
                'stop_num':stopNum,
                'arrival_day':arrivalDay
            },
            success:function(response){
                removeLoader();
                if ($.trim(response) !== 'err'){
                    $(item).closest('tr').html(response);
                }else {
                    alert('Ошибка!');
                }
            }
        })
    }

    function addStop(){
        let station = $('#station_select').val();
        let arrival_time = $('#arrival_time').val();
        let departure_time = $('#departure_time').val();
        let stopNum = $('#stop_num').val();
        let arrivalDay = $('#arrival_day').val();
        $.ajax({
            type:"post",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/cruds/catalog/tours/ajax.php"), '/') ?>',
            data:{
                'request':'add_stop',
                'id':'<?=$id?>',
                'station':station,
                'arrival_time':arrival_time,
                'departure_time':arrival_time,
                'stop_num':stopNum,
                'arrival_day':arrivalDay
            },
            success:function(response){
                if ($.trim(response) != 'err'){
                    $('.current_stops_table tbody').append(response);
                }
            }
        })
    };

    function deleteStop(item,id){
        if (confirm('Вы уверены что хотите удалить остановку?')){
            $.ajax({
                type:'post',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
                data:{
                    'request':'delete_stop',
                    'id':id
                },
                success:function(response){
                    if ($.trim(response) == 'ok'){
                        $(item).closest('tr').remove();
                    }else{
                        alert('Ошибка');
                    }
                }
            })
        }
    };

    function addTransfer(){
        let transfer = $('#transfer_select').val();
        $.ajax({
            type:"post",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data:{
                'request':'add_transfer',
                'id':'<?=$id?>',
                'transfer':transfer,
            },
            success:function(response){
                if ($.trim(response) != 'err'){
                    $('.current_transfers_table tbody').append(response);
                }
            }
        })
    };

    function changeTransferInfo(item,id){
        let transfer = $(item).closest('tr').find($('.transfer_select')).val();
        $.ajax({
            type:"post",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
            data:{
                'request':'edit_transfer',
                'id':id,
                'transfer':transfer,
            },
            success:function(response){
                if ($.trim(response) == 'ok'){
                    alert('Данные изменены');
                }else{
                    alert('Ошибка!');
                }
            }
        })
    };

    function deleteTransfer(item,id){
        if (confirm('Вы уверены что хотите удалить пересадку?')){
            $.ajax({
               type:'post',
                headers: {
                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                url:'<?= rtrim(url(ADMIN_PANEL . "/ajax.php"), '/') ?>',
                data:{
                    'request':'delete_transfer',
                    'id':id
                },
                success:function(response){
                    if ($.trim(response) == 'ok'){
                        $(item).closest('tr').remove();
                    }else{
                        alert('Ошибка');
                    }
                }
            })
        }
    };
    $(document).ready(function() {
        // Изначально загружаем общий список станций, исключая section_id = 174
        loadStations(false);

        $('#is_kpp_checkbox').change(function() {
            var isKpp = $(this).is(':checked');
            if (isKpp) {
                // Загружаем список станций для КПП (section_id = 174)
                loadStations(true);
            } else {
                // Загружаем общий список станций, исключая section_id = 174
                loadStations(false);
            }
        });
    });
    function loadStations(isKpp) {
        var select = $('#station_select');
        select.empty(); // Очищаем текущие опции
        if (isKpp) {
            <?php
            $getCityStations = $Db->getAll("SELECT id, title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND station = 1 AND section_id = '174' ORDER BY sort DESC, title_" . $Admin->lang . " ASC");
            foreach ($getCityStations AS $key=>$station){ ?>
            select.append('<option value="<?= $station['id'] ?>"><?= $station['title'] ?></option>');
            <?php } ?>
        } else {
            <?php
            foreach ($getCities as $city) {
            $getCityStations = $Db->getAll("SELECT id, title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND station = 1 AND section_id = '" . $city['id'] . "' AND section_id != '174' ORDER BY sort DESC, title_" . $Admin->lang . " ASC");
            foreach ($getCityStations AS $key=>$station){ ?>
            select.append('<option value="<?= $station['id'] ?>"><?= $city['title'] . ' ' . $station['title'] ?></option>');
            <?php   }
            }
            ?>
        }
    }
</script>
<!-- Drag'n'Drop stops -->
<script src="/<?php echo ADMIN_PANEL?>/template/dist/js/stops.js"></script>
</body>
</html>
