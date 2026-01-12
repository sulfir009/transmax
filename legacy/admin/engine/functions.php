<?php
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . "/config.php");
require_once(str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT'])  ."/" . ADMIN_PANEL ."/includes.php");

function get_list_lang_public() {

    global $pageData , $Router , $db;
    $pageData = $Router->GetCPU();
    $ArrLangExport = array();
    $LangUrls = $Router->getURLs($pageData['page_id'], $pageData['elem_id']);
    if( isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']!='' ){
        $get = "?".$_SERVER['QUERY_STRING'];
    }

    $getLangs = mysqli_query( $db , " SELECT * FROM `" .  DB_PREFIX . "_site_languages`WHERE active = 1 ORDER BY sort DESC ");
    if( mysqli_num_rows($getLangs) === 0 ){
        return false;
        //exit;
    }else{
        $i = 0;
        while($Lang = mysqli_fetch_assoc($getLangs)){
            $ArrLangExport['lang'][$Lang['code']]['title'] = $Lang['title'];
            $ArrLangExport['lang'][$Lang['code']]['code'] = $Lang['code'];
            $ArrLangExport['lang'][$Lang['code']]['value'] = $Lang['value'];
            $ArrLangExport['lang'][$Lang['code']]['href'] = $LangUrls[$Lang['code']].$get;

            $cur_lang = 0;
            if( $Router->lang == $Lang['code'] ){
                $cur_lang = 1;
            }
            $ArrLangExport['lang'][$Lang['code']]['cur_lang'] = $cur_lang;
        }
    }

    /* сколько языков */
    $ArrLangExport['count'] = count($ArrLangExport['lang']);
    return $ArrLangExport;
}

function getClientIp() {
    $keys = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'REMOTE_ADDR'
    ];

    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            @$ip = trim(end(explode(',', $_SERVER[$key])));
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
}

function pagination($SQL_query, $perPage = 6)
{
    global $db;

    if(!is_numeric($SQL_query)){
        $getCount = mysqli_query($db, $SQL_query);
        $pagesCount = mysqli_num_rows($getCount);
    }
    else {
        $pagesCount = (int)$SQL_query;
    }

    $pages = ceil($pagesCount / $perPage);
    if(isset($_GET['page'])){
        $page = (int)$_GET['page'];
    }else{
        $page = 1;
    }
    if($page>$pages){
        $page=$pages;
    }
    if($page<1){
        $page=1;
    }
    $from = $page * $perPage - $perPage;

    $NextPage = $page+1;
    if($NextPage>$pages){$NextPage=$pages;}
    $PrevPage = $page-1;
    if($PrevPage<1){$PrevPage=1;}

    return array(
        'count_elem' => $pagesCount,
        'page'=>$page,
        'per_page'=>$perPage,
        'pages'=>$pages,
        'from'=>$from,
        'prev'=>$PrevPage,
        'next'=>$NextPage
    );
}

function paginate($Paginator, $arrayClasses = array())
{
    $getdata = array();
    foreach ($_GET as $key => $value) {
        if($key=='page'){continue;}
        $getdata[] = $key."=".$value;
    }
    $getdata = implode("&", $getdata);
    if($getdata!=''){
        $getdata.="&";
    }

    if($Paginator['pages']>1){?>
        <div class="pagination_area clear_end">
            <ul class="pagination">

                <li class="link backlink <?=@$arrayClasses['backlink']?> <?if($Paginator['page']==$Paginator['prev']){echo "backlink-disabled";}?> ">
                    <a <?if($Paginator['page']>$Paginator['prev']){?>href="?<?=$getdata?>page=<?=$Paginator['prev']?>"<?}?>>  </a>
                </li>

                <?
                $dots = '<li><a>...</a></li>';
                for($p=1;$p<=$Paginator['pages'];$p++){
                    if($Paginator['pages']>3 && $Paginator['page']!=$p && abs( $Paginator['page'] - $p )> 2 ){
                        echo $dots;
                        $dots = '';
                        continue;
                    }
                    ?>
                    <li>
                        <a <?if($p==$Paginator['page']){?>class="activepage <?=@$arrayClasses['activepage']?> <?=@$arrayClasses['allnumbers']?>"<?}else{?>class="<?=@$arrayClasses['allnumbers']?>"<?}?> href="?<?=$getdata?>page=<?=$p?>"><?=$p?></a>
                    </li>
                    <?
                }

                ?>
                <li class="link forwardlink <?=@$arrayClasses['forwardlink']?> <?if($Paginator['page']==$Paginator['next']){echo "forwardlink-disabled";}?>">
                    <a <?if($Paginator['page']<$Paginator['next']){?>href="?<?=$getdata?>page=<?=$Paginator['next']?>"<?}?>>  </a>
                </li>

            </ul>
        </div>
    <?}
}

function paginatePublic($Paginator, $arrayClasses = array()){
    $getdata = array();
    foreach ($_GET as $key => $value) {
        if($key=='page'){continue;}
        $getdata[] = $key."=".$value;
    }
    $getdata = implode("&", $getdata);
    if($getdata!=''){
        $getdata.="&";
    }

    if($Paginator['pages']>1){?>
        <?/*------*/?>

        <div class="shedule_table_pagination">
            <ul class="pagination flex_ac shedule_pagination hidden-xs">
                <?
                $dots = '<li><a class="page_link">...</a></li>';
                for($p=1;$p<=$Paginator['pages'];$p++){
                    if($Paginator['pages']>3 && $Paginator['page']!=$p && abs( $Paginator['page'] - $p )> 2 ){
                        echo $dots;
                        $dots = '';
                        continue;
                    }
                    ?>
                    <li class="">
                        <a class="page_link <?if($p==$Paginator['page']){?> active <?=@$arrayClasses['activepage']?> <?=@$arrayClasses['allnumbers']?>" <?}else{?>class="<?=@$arrayClasses['allnumbers']?>"<?}?> href="?<?=$getdata?>page=<?=$p?>"><?=$p?></a>
                    </li>
                    <?
                }

                ?>
            </ul>
            <ul class="pagination flex_ac shedule_pagination hidden-xxl hidden-xl hidden-lg hidden-md hidden-sm col-xs-12">
                <li class="link backlink  ">
                    <a <?if($Paginator['page']>$Paginator['prev']){?>href="?<?=$getdata?>page=<?=$Paginator['prev']?>"<?}?>>  </a>
                </li>
                <li class="<?=@$arrayClasses['backlink']?> <?if($Paginator['page']==$Paginator['prev']){echo "backlink-disabled";}?>">
                    <a <?if($Paginator['page']>$Paginator['prev']){?>href="?<?=$getdata?>page=<?=$Paginator['prev']?>"<?}?> class="prevlink par flex_ac">
                        <img src="<?= asset('images/legacy/common/blue_arrow_left.svg'); ?>" alt="arrow left">
                    </a>
                </li>
                <li class="shedule_pagunation_links">
                    <ul class="pagination flex_ac">
                        <?
                        $dots = '<li><a class="page_link">...</a></li>';
                        for($p=1;$p<=$Paginator['pages'];$p++){
                            if($Paginator['pages']>3 && $Paginator['page']!=$p && abs( $Paginator['page'] - $p )> 1 ){
                                echo $dots;
                                $dots = '';
                                continue;
                            }
                            ?>
                            <li class="">
                                <a class="page_link <?if($p==$Paginator['page']){?> active <?=@$arrayClasses['activepage']?> <?=@$arrayClasses['allnumbers']?>" <?}else{?>class="<?=@$arrayClasses['allnumbers']?>"<?}?> href="?<?=$getdata?>page=<?=$p?>"><?=$p?></a>
                            </li>
                            <?
                        }?>
                    </ul>
                </li>
                <li class=" <?=@$arrayClasses['forwardlink']?> <?if($Paginator['page']==$Paginator['next']){echo "forwardlink-disabled";}?>">
                    <a <?php
                       if($Paginator['page']<$Paginator['next']) {?>
                           href="?<?=$getdata?>page=<?=$Paginator['next']?>"
                       <?}?>
                        class="forwardlink par flex_ac">
                        <img src="<?= asset('images/legacy/common/blue_arrow_right.svg'); ?>" alt="arrow right">
                    </a>
                </li>
            </ul>
        </div>
    <?}
}

/* вывод пострничной навигации для большлго кол-ва страниц и с дополнительными кнпками */
function paginateExtender($Paginator, $cpu = "/")
{
    $getdata = array();
    foreach ($_GET as $key => $value) {
        if($key=='page'){continue;}
        $getdata[] = $key."=".$value;
    }
    if(!empty($getdata)){
        $getdata = implode("&", $getdata);
    }else{
        $getdata = '';
    }
    if($getdata!=''){
        $getdata="&".$getdata;
    }

    if($Paginator['pages']>1){

        $pagePrev = $Paginator['page']-1;
        if($pagePrev<1){$pagePrev = 1;}
        $pageNext = $Paginator['page']+1;
        if($pageNext>$Paginator['pages']){$pageNext = $Paginator['pages'];}


        // 1 block
        $Firstpart = '';
        $hrefFirstPage = $cpu;
        //show($Paginator['page']);
        if($getdata!=''){
            $hrefFirstPage = "?".mb_substr($getdata, 1,200);
        }
        if($Paginator['page']>4){
            $Firstpart = '<a class="pagelink" href="'.$hrefFirstPage.'">1</a> ... ';
        }

        // 3 block
        $Endpart = '';
        if( ($Paginator['pages'] - $Paginator['page']) > 3  ){
            $href = '?page='.$Paginator['pages'].$getdata;
            $Endpart = ' <span>...</span> <a class="pagelink" href="'.$href.'">'.$Paginator['pages'].'</a>';
        }

        // 2 block
        $begin_row = $Paginator['page'] - 2;
        if( ($Paginator['pages'] - $Paginator['page'])<2  ){
            $begin_row = $Paginator['page'] - 2 - (2 -($Paginator['pages'] - $Paginator['page']));
        }
        if($Paginator['pages']<5){$begin_row = 1;}
        if($begin_row < 1){
            $begin_row = 1;

            if($Paginator['pages'] - ($begin_row+4)<1){
                $Endpart = '';
            }
        }


        ?>
        <div class="paginatorextendwrapper">
            <a class="backlink <?if($Paginator['page']==$Paginator['prev']){echo "disabled";}?>" href="<?=$hrefFirstPage?>"></a>
            <a class="prevlink <?if($Paginator['page']==$Paginator['prev']){echo "disabled";}?>" href="?page=<?=$pagePrev.$getdata?>"></a>

            <?=$Firstpart?>
            <? for($i=$begin_row;($i<$begin_row+5 && $i <= $Paginator['pages']);$i++) {
                $HREF = "?page=".$i.$getdata;
                if($i==1){
                    $get = '';
                    if($getdata!=''){
                        $get = "?".mb_substr($getdata, 1,200);
                    }
                    $HREF = $cpu.$get;
                }
                ?>
                <a class="pagelink <?if($Paginator['page']==$i){echo"activepage";}?>" href="<?=$HREF?>"><?=$i?></a>
            <?}?>
            <?=$Endpart?>

            <a class="nextlink <?if($Paginator['page']==$Paginator['next']){echo "disabled";}?>" href="?page=<?=$pageNext.$getdata?>"></a>
            <a class="endlink <?if($Paginator['next']==$Paginator['pages']){echo "disabled";}?>" href="?page=<?=$Paginator['pages'].$getdata?>"></a>
        </div>
    <?}
}

function editElem($name, $title, $type, $elementInfo, $lang = '', $action = 'add', $required = 0, $size = 8, $tableElements = '', $elementsTitle = '', $default = '')
{
    global $db;
    if($lang!=''){
        $name.="_".$lang;//языковая метка для полей, например - title_ru
    }
    if($action=='edit' && !isset($elementInfo[$name])){?><h3 class="red"> <?=$GLOBALS['CPLANG']['ELEMENT_WORD']?> <?=$name?>  <?=$GLOBALS['CPLANG']['NOT_FOUND_WORD']?> </h3><?return false;}
    if($size>10){$size = 10;}
    ?>

    <div class="form-group">
        <label class="col-sm-3"><?=$title?> <?if($required==1){?><b class="red">*</b><?}?></label>
        <?
        switch($type){
            case 1:
                ?>

                <div class="col-sm-<?=$size?>">
                    <input <?if($required==1){echo"required";}?> type="text" name="<?=$name?>" <?if($action=='edit'){?>value="<?=$elementInfo[$name]?>"<?}elseif($default!=''){?>value="<?=$default?>"<?}?> class="form-control input-sm">
                </div>

                <?
                break;

            case 2:
                ?>
                <div class="col-sm-<?=$size?>">
                    <select <?if($required==1){echo"required";}?> type="text" name="<?=$name?>" class="form-control input-sm">
                        <?if($action=='add'){?>
                            <option value=""> --- </option>
                        <?}
                        $getTableElements = mysqli_query($db, " SELECT `id`, `".$elementsTitle."` FROM `".$tableElements."`  ");
                        while($ElemTable = mysqli_fetch_assoc($getTableElements)){
                            ?>
                            <option <?if($action=='edit' && $ElemTable['id']==$elementInfo[$name]){echo"selected";}?> value="<?=$ElemTable['id']?>"><?=$ElemTable[$elementsTitle]?></option>
                            <?
                        }

                        ?>
                    </select>
                </div>
                <?
                break;

            case 3:
                ?>
                <div class="custom-control custom-checkbox col-sm-10">
                    <input class="custom-control-input" type="checkbox" name="<?=$name?>" id="<?=$name?>" <?if($action=='edit' && 1==$elementInfo[$name]){echo"checked";}elseif($action=='add' && $default==1){echo "checked";}?>>
                    <label for="<?=$name?>" class="custom-control-label"> <?=$title?> </label>
                </div>
                <?
                break;

            case 4:
                ?>
                <div class="list_all">
                    <div class="col-sm-12">
                        <textarea name="<?=$name?>" class="txt_editor"><?if($action=='edit'){echo $elementInfo[$name];}?></textarea>

                    </div>
                </div>
                <?
                break;

            case 5:
                ?>
                <div class="col-sm-<?=$size?>">
                    <input <?if($required==1){echo"required";}?> type="file" name="<?=$name?>" class="<?=$sizeClass[$size]?>">
                </div>
                <?
                break;

            case 6:
                ?>
                <div class="col-sm-2">
                    <input <?if($required==1){echo"required";}?> type="text" autocomplete="off"  name="<?=$name?>" <?if($action=='edit'){?>value="<?=$elementInfo[$name]?>"<?}elseif($default!=''){?>value="<?=$default?>"<?}?> class="datepicker form-control input-sm">
                </div>
                <?
                break;

            case 7:
                ?>
                <div class="col-sm-3">
                    <input <?if($required==1){echo"required";}?> type="text"  name="<?=$name?>" <?if($action=='edit'){?>value="<?=$elementInfo[$name]?>"<?}elseif($default!=''){?>value="<?=$default?>"<?}?> class="datetimepicker <?=$sizeClass[$size]?> form-control">
                </div>
                <?
                break;


            case 8:
                ?>
                <div class="col-sm-7">
                    <textarea <?if($required==1){echo"required";}?> name="<?=$name?>" class="<?=$sizeClass[$size]?> form-control" style="resize: vertical;"><?if($action=='edit'){echo $elementInfo[$name];}elseif($default!=''){echo $default;}?></textarea>
                </div>
                <?
                break;

            default: ?><div class="red"> <?=$GLOBALS['CPLANG']['INVALID_BLOCK_TYPE']?> </div><?
        }
        ?>

    </div>
    <?
}

function checkboxParam($name)
{
    $result = 0;
    if(isset($_POST[$name])){
        $result = 1;
    }
    return $result;
}

function updateElement($id, $dbtable_elem, $checkboxFields = array(), $textFields = array(), $otherFields = array(), $exceptions = array())
{
    global $db;
    $fields = array();
    $set_data = array();
    $VAL = '';
    $ar_clean = filter_input_array(INPUT_POST,FILTER_SANITIZE_SPECIAL_CHARS);
    foreach ($_POST as $key => $value)
    {
        if( array_key_exists($key, $checkboxFields) || $key=='ok' || in_array($key, $exceptions ?? []) )
        {
            continue; // обработаем ниже
        }
        if(is_array($_POST[$key])){

            $_POST[$key]=implode(',', $_POST[$key]);
        }
        if( in_array($key, $textFields) )
        {
            $VAL =  str_replace("'", '&#39;', $_POST[$key]);
        }
        else
        {
            $VAL = $ar_clean[$key];
        }
        $fields[$key] = $VAL;
    }
    // галочки
    foreach ($checkboxFields as $key => $value)
    {
        $fields[$key] = $value;
    }
    // файлы и картинки
    foreach ($otherFields as $key => $value)
    {
        $fields[$key] = $value;
    }

    foreach ($fields as $key => $value)
    {

        $set_data[] = " `".$key."` = '".$value."' ";
    }
    $set_data = implode(",",$set_data);
    $result = mysqli_query ($db, "UPDATE `".$dbtable_elem."` SET ".$set_data." WHERE `id`='".$id."'");

    if($result){
        ?>
        <div class="alert alert-success">
            <div><i class="icon fa fa-check"></i> Данные успешно обновлены </div>
        </div>
        <?
        $url = url()->current();
        $path = parse_url($url, PHP_URL_PATH);

// Убираем последний сегмент пути (create.php)
        $cleanPath = dirname($path);
        if(strpos($url, 'create.php') !== false) {
            redirect($cleanPath)->send();
        }
    }else{
        ?>
        <div class="alert alert-danger">Ошибка!<br><pre><?echo mysqli_error($db)?></pre></div>
        <?
    }

}

function addElement($dbtable_elem, $checkboxFields = array(), $textFields = array(), $otherFields = array(), $exceptions = array())
{
    global $db;
    $fields = array();
    $keys_data = array();
    $vals_data = array();
    $VAL = '';
    $ar_clean = filter_input_array(INPUT_POST,FILTER_SANITIZE_SPECIAL_CHARS);
    foreach ($_POST as $key => $value) {
        if( array_key_exists($key, $checkboxFields) || $key=='ok' || in_array($key, $exceptions)){
            continue; // обработаем ниже
        }
        if( in_array($key, $textFields) ){
            $VAL =  str_replace("'", '&#39;', $_POST[$key]);
        }else{
            $VAL = $ar_clean[$key];
        }
        $fields[$key] = $VAL;
    }
    // галочки
    foreach ($checkboxFields as $key => $value) {
        $fields[$key] = $value;
    }
    // файлы и картинки
    foreach ($otherFields as $key => $value) {
        $fields[$key] = $value;
    }

    foreach ($fields as $key => $value) {
        $keys_data[] = " `".$key."` ";
        if( $value!=='NOW()' ){
            $vals_data[] = " '".$value."' ";
        }else{
            $vals_data[] = " NOW() ";
        }
    }
//show( "INSERT INTO `".$dbtable_elem."` ( ".implode(",", $keys_data)." ) VALUES ( ".implode(",", $vals_data)." ) " );
    $result = mysqli_query ($db, "INSERT INTO `".$dbtable_elem."` ( ".implode(",", $keys_data)." ) VALUES ( ".implode(",", $vals_data)." ) ");
    echo mysqli_error($db);
    if($result){

        ?><div class="alert alert-success">Данные обновлены </div><?
        $url = url()->current();
        $path = parse_url($url, PHP_URL_PATH);

// Убираем последний сегмент пути (create.php)
        $cleanPath = dirname($path);
        if(strpos($url, 'create.php') !== false) {
            redirect($cleanPath)->send();
        }

    }else{

        ?><div class="alert alert-danger">Ошибка!</div><?
    }
}


function out($Elem)
{
    if( isset($_SESSION['admin']) ){
        ?><pre style="font-family: Consolas,Courier;font-size: 12px;background: #1f2d3d;padding: 4px 6px;color: #17a2b8;display: inline-block;border-radius: 3px;text-align: left;margin: 3px;line-height: 1.0;"><?
        print_r($Elem);
        ?></pre><?
    }
}



function generateName($number = 10, $str = '')
{
    $arr = array('a','b','c','d','e','f',
        'g','h','i','j','k','l',
        'm','n','o','p','r','s',
        't','u','v','y','z',
        '1','2','3','4','5','6',
        '7','8','9','_');
    $pass = "";
    for($i = 0; $i < $number; $i++)
    {
        $index = rand(0, count($arr) - 1);
        $pass .= $arr[$index];
    }
    if($str==''){
        return $pass;
    }else{
        $ar = explode(".", (string)$str);
        $pass = $pass.".".end($ar);
        return $pass;
    }

}


function cleanInput($input) {
    $search = array(
        '@<script[^>]*?>.*?</script>@si',   // Удаляем javascript
        '@<;[\/\!]*?[^<>]*?>@si',            // Удаляем HTML теги
        '@<style[^>]*?>.*?</style>@siU',    // Удаляем теги style
        '@<![\s\S]*?--[ \t\n\r]*>@'         // Удаляем многострочные комментарии
    );

    $output = preg_replace($search, '', $input);
    return $output;
}

function sanitize($input) {
    global $db;
    if (is_array($input)) {
        foreach($input as $var=>$val) {
            $output[$var] = sanitize($val);
        }
    }
    else {
        if (get_magic_quotes_gpc()) {
            $input = stripslashes($input);
        }
        $input  = cleanInput($input);
        $output = mysqli_real_escape_string($db,$input);
    }
    return $output;

    /* Использование:
    $bad_string = "Привет! <script src='http://www.evilsite.com/bad_script.js'></script> Какой хороший сегодня день!";
    $good_string = sanitize($bad_string);
// $good_string вернет "Привет! Какой хороший сегодня день!"

// Также используйте для проверки POST/GET данных
    $_POST = sanitize($_POST);
    $_GET  = sanitize($_GET);*/
}





function clean_post_data($data) {
    if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = clean_post_data($value);
        }
    } else {
        // Удаляем пробелы с начала и конца строки
        $data = trim($data);
        // Преобразуем специальные символы в HTML сущности
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        // Удаляем HTML и PHP-теги
        $data = strip_tags($data);
    }
    return $data;
}

function calculateTotalTravelTime($stops, $startStopId, $endStopId, $arrival_day) {
    $startTime = null;
    $endTime = null;
    $totalTime = 0;
    if ($arrival_day >= 1) {
        $daysInTravel = $arrival_day - 1;
    } else {
        $daysInTravel = 0;
    }

    foreach ($stops as $stop) {
        if ($stop['stop_id'] == $startStopId) {
            $startTime = strtotime($stop['departure_time']);
        } elseif ($stop['stop_id'] == $endStopId) {
            $endTime = strtotime($stop['arrival_time']);
        }

        if ($startTime !== null && $endTime !== null) {
            /*Если время прибытия меньше времени отправления, и рейс дольше суток в пути */
            if ($endTime < $startTime && $arrival_day > 1) {
                $totalTime += $daysInTravel * (24 * 3600) - $startTime + $endTime;
            } /*Если время прибытия меньше времени отправления, но рейс меньше суток в пути */
            elseif ($endTime < $startTime && $arrival_day <= 1) {
                $totalTime += $startTime - $endTime;
            } /*Если время прибытия больше времени отправления */
            else {
                $totalTime +=$daysInTravel * (24 * 3600) + $endTime - $startTime;
            }
            $startTime = null;
            $endTime = null;
        }
    }

    // Convert total time to HH:MM:SS format
    $hours = floor($totalTime / 3600);
    $minutes = floor(($totalTime % 3600) / 60);

    $formattedTotalTime = sprintf('%02d:%02d', $hours, $minutes);

    return $formattedTotalTime;
}

function findNearestDayOfWeek($currentDay, $departureDays) {
    $currentDayNumber = date('N', strtotime($currentDay));
    sort($departureDays);
    if (in_array($currentDayNumber, $departureDays)) {
        return $currentDay;
    }
    foreach ($departureDays as $departureDay) {
        $difference = ((int)$departureDay - (int)$currentDayNumber + 7) % 7;
        if ($difference === 0) {
            return $currentDay;
        }
        $nearestDate = date('Y-m-d', strtotime("+$difference days"));
        return $nearestDate;
    }
}

function calculateArrivalDateTime($departureDateTime, $durationHours, $durationMinutes) {
    $departureDateTime = new DateTime($departureDateTime);
    $durationInSeconds = max(0, ($durationHours * 60 * 60) + ($durationMinutes * 60));
    $arrivalDateTime = clone $departureDateTime;
    $arrivalDateTime->add(new DateInterval('PT' . $durationInSeconds . 'S'));
    return $arrivalDateTime->format('Y-m-d H:i:s');
}

?>
