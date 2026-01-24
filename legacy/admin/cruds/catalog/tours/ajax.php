<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
if(!isset($_POST) || empty($_POST) || !isset($_POST['request'])){exit;}
$cleanPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
$cleanGet = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING);

if ($cleanPost['request'] === 'add_stop') {
   $addStop = mysqli_query($db,"INSERT INTO `" .  DB_PREFIX . "_tours_stops`(`tour_id`,`stop_id`,`arrival_time`,`departure_time`,`stop_num`,`arrival_day`) VALUES ('".$cleanPost['id']."','".$cleanPost['station']."','".$cleanPost['arrival_time']."','".$cleanPost['departure_time']."','".$cleanPost['stop_num']."','".$cleanPost['arrival_day']."') ");
   if ($addStop){
       $newStopId = mysqli_insert_id($db);
       $stopInfo = $Db->getOne("SELECT ts.arrival_time,ts.departure_time,ts.stop_num,ts.arrival_day,city.title_".$Admin->lang." AS city,station.title_".$Admin->lang." AS station FROM `" .  DB_PREFIX . "_tours_stops`ts
        LEFT JOIN `" .  DB_PREFIX . "_cities`station ON station.id = ts.stop_id
         LEFT JOIN `" .  DB_PREFIX . "_cities`city ON city.id = station.section_id
          WHERE ts.id = '".$newStopId."' ")?>
       <tr>
           <td><?=$newStopId?></td>
           <td><?=$stopInfo['city'].' '.$stopInfo['station']?></td>
           <td><?=$cleanPost['arrival_time']?></td>
           <!--td><?=$cleanPost['departure_time']?></td-->
           <td><?=$cleanPost['stop_num']?></td>
           <td><?=$cleanPost['arrival_day']?></td>
           <td align="center" width="210">
               <div class="btn-group wgroup">
                   <button class="btn btn-default" title="Редактировать" type="button" onclick="editStop(this,'<?=$newStopId?>')">
                       <i class="fas fa-pencil-alt"></i>
                   </button>
                   <button class="btn btn-danger" title="Удалить" type="button" onclick="deleteStop(this,'<?=$newStopId?>')">
                       <i class="fas fa-times"></i>
                   </button>
               </div>
           </td>
       </tr>
   <?}else{
       echo 'err';
   }
}

if ($cleanPost['request'] === 'edit_stop'){
    $getStations = $Db->getAll("SELECT tc.title_".$Admin->lang." AS city_title,ts.title_".$Admin->lang." AS station_title,ts.id FROM `" .  DB_PREFIX . "_cities`ts
    LEFT JOIN `" .  DB_PREFIX . "_cities`tc ON tc.id = ts.section_id
    WHERE ts.active = 1 AND ts.station = 1 ORDER BY ts.title_".$Admin->lang." ");
    $currentStopValues = $Db->getOne("SELECT * FROM `" .  DB_PREFIX . "_tours_stops`WHERE id = '".(int)$cleanPost['id']."' "); ?>
    <td><?=(int)$cleanPost['id']?></td>
    <td>
        <select class="select2 edit_station_id">
            <?foreach ($getStations AS $k=>$station){?>
                <option value="<?=$station['id']?>" <?if ($currentStopValues['stop_id'] == $station['id']){echo 'selected';}?>><?=$station['city_title'].' '.$station['station_title']?></option>
            <?}?>
        </select>
    </td>
    <td><input type="time" class="form-control input-sm edit_arrival_time" value="<?=$currentStopValues['arrival_time']?>"></td>
    <!--td><input type="time" class="form-control input-sm edit_departure_time" value="<?=$currentStopValues['departure_time']?>"></td-->
    <td><input type="number" class="form-control input-sm edit_stop_num" value="<?=$currentStopValues['stop_num']?>"></td>
    <td><input type="number" class="form-control input-sm edit_arrival_day" value="<?=$currentStopValues['arrival_day']?>"></td>
    <td align="center" width="210">
        <div class="btn-group wgroup">
            <button class="btn btn-success" title="Сохранить изменения" type="button" onclick="acceptStopChanges(this,'<?=(int)$cleanPost['id']?>')">
                <i class="fas fa-check"></i>
            </button>
        </div>
    </td>
    <?
}

if ($cleanPost['request'] === 'accept_stop_changes'){
    $edit = mysqli_query($db,"UPDATE `" .  DB_PREFIX . "_tours_stops`SET stop_id = '".(int)$cleanPost['station']."',arrival_time = '".$cleanPost['station_arrival']."',departure_time = '".$cleanPost['station_departure']."',stop_num = '".$cleanPost['stop_num']."',arrival_day = '".$cleanPost['arrival_day']."' WHERE id = '".$cleanPost['id']."' ");
    if ($edit){
        $currentStop = $Db->getOne("SELECT city.title_".$Admin->lang." AS city,station.title_".$Admin->lang." AS station,ts.id,ts.arrival_time,ts.departure_time,ts.stop_num,ts.arrival_day FROM `" .  DB_PREFIX . "_tours_stops`ts
        LEFT JOIN `" .  DB_PREFIX . "_cities`station ON station.id = ts.stop_id
        LEFT JOIN `" .  DB_PREFIX . "_cities`city ON city.id = station.section_id
        WHERE ts.id = '".(int)$cleanPost['id']."' ")?>
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
    <? }else{
        echo 'err';
    }
}

if ($cleanPost['request'] === 'delete_stop'){
    $del = mysqli_query($db,"DELETE FROM `" .  DB_PREFIX . "_tours_stops`WHERE id = '".$cleanPost['id']."' ");
    if ($del){
        echo 'ok';
    }else{
        echo 'err';
    }
}

if ($cleanPost['request'] === 'add_transfer') {
    $addTransfer = mysqli_query($db,"INSERT INTO `" .  DB_PREFIX . "_tours_transfers`(`tour_id`,`transfer_station_id`) VALUES ('".$cleanPost['id']."','".$cleanPost['transfer']."') ");
    if ($addTransfer){
        $newTransferId = mysqli_insert_id($db);?>
        <tr>
            <td><?=$newTransferId?></td>
            <td>
                <select class="custom-select transfer_select">
                    <? $getCities = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM mt_cities WHERE active = '1' AND section_id > 0 AND station = '0' ORDER BY sort DESC");
                    foreach ($getCities as $k => $city) { ?>
                        <optgroup label="<?= $city['title'] ?>">
                            <? $getStations = $Db->getAll("SELECT id,title_" . $Admin->lang . " AS title FROM mt_cities WHERE active = '1' AND section_id = '" . $city['id'] . "' ");
                            foreach ($getStations as $k => $station) { ?>
                                <option value="<?= $station['id'] ?>" <? if ($station['id'] == $cleanPost['transfer']) {
                                    echo 'selected';
                                } ?>><?= $station['title'] ?></option>
                            <? } ?>
                        </optgroup>
                    <? } ?>
                </select>
            </td>
            <td align="center" width="210">
                <div class="btn-group wgroup">
                    <button type="button" class="btn btn-success" title="Применить изменения" onclick="changeTransferInfo(this,'<?=$newTransferId?>')">
                        <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-danger" title="Удалить" type="button" onclick="deleteTransfer(this,'<?=$newTransferId?>')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </td>
        </tr>
    <?}else{
        echo 'err';
    }
}

if ($cleanPost['request'] === 'edit_transfer'){
    $edit = mysqli_query($db,"UPDATE `" .  DB_PREFIX . "_tours_transfers`SET transfer_station_id = '".$cleanPost['transfer']."' WHERE id = '".$cleanPost['id']."' ");
    if ($edit){
        echo 'ok';
    }else{
        echo 'err';
    }
}

if ($cleanPost['request'] === 'delete_transfer'){
    $del = mysqli_query($db,"DELETE FROM `" .  DB_PREFIX . "_tours_transfers`WHERE id = '".$cleanPost['id']."' ");
    if ($del){
        echo 'ok';
    }else{
        echo 'err';
    }
}

if ($cleanGet['request'] === 'load_kpp') {
    $isKpp = isset($cleanGet['is_kpp']) ? (bool)$cleanGet['is_kpp'] : false;

    if ($isKpp) {
        foreach ($getCities as $k => $city) { ?>
        <?$getCityStations = $Db->getAll("SELECT id, title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND station = 1 AND section_id = '174' ORDER BY sort DESC, title_" . $Admin->lang . " ASC");
        foreach ($getCityStations AS $key=>$station){?>
            <option value="<?= $station['id'] ?>"><?=$city['title'].' '.$station['title']?></option>
            <?}?>
        <? }
    } else {
        foreach ($getCities as $k => $city) { ?>
            <?$getCityStations = $Db->getAll("SELECT id, title_" . $Admin->lang . " AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = 1 AND station = 1 AND section_id = '" . $city['id'] . "' AND section_id != '174' ORDER BY sort DESC, title_" . $Admin->lang . " ASC");
            foreach ($getCityStations AS $key=>$station){?>
                <option value="<?= $station['id'] ?>"><?=$city['title'].' '.$station['title']?></option>
            <?}?>
        <? }
    }
}
?>
