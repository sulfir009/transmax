<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/config.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/guard.php';
include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/includes.php';
include 'config.php';
if (!$Admin->CheckPermission($_params['access'])) {
    header('Location:./');
} ?>
<!DOCTYPE html>
<html lang="<?= $Admin->lang ?>">
<head>
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/head.php' ?>
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed <?php echo $adminTheme['body_class'] ?>">
<div class="wrapper">
    <?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/header.php' ?>
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0"><?= $_params['title'] ?></h1>
                        <a href="./" class="btn btn-info">Назад</a>
                    </div>
                </div>
            </div>
        </div>
        <?
        //обработка запроса
        if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $cleanPost = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);
            //массив с икслючениями
            $exceptions = array();
            $allQueries = array();

            // полуим список таблиц
            $getTables = $Db->Query( " SHOW TABLES  ");
            while ($Table = mysqli_fetch_array($getTables)) {
                //out($Table);

                if (in_array($Table[0], $exceptions)) {
                    continue;
                }

                $queryDeuplicate = array();
                $queryContent = array();
                //получим список полей
                $getColumns = $Db->Query( " SHOW COLUMNS FROM " . $Table[0]);
                while ($Column = mysqli_fetch_assoc($getColumns)) {
                    //out($Column);
                    $a = explode("_", $Column['Field']);
                    if (end($a) == $_POST['from_language']) { // для этого поля нужно сделать копию
                        $newName = str_replace("_" . $_POST['from_language'], "_" . $cleanPost['new_prefix'], $Column['Field']);
                        // возможно изменить на вариант с обрезкой последних сомволов
                        $Type = strtoupper($Column['Type']);
                        $queryDeuplicate[] = " ADD `" . $newName . "` " . $Type . " NOT NULL ";
                        $queryContent[] = " `" . $newName . "` = `" . $Column['Field'] . "` ";
                    }
                }

                if (!empty($queryDeuplicate)) {
                    $allQueries[] = " ALTER TABLE `" . $Table[0] . "` " . implode(", ", $queryDeuplicate) . " ";
                    $allQueries[] = " UPDATE `" . $Table[0] . "` SET  " . implode(", ", $queryContent) . " ";
                }

            }

            foreach ($allQueries as $key => $value) {
                $Db->Query( $value);
            }

            //а теперь добаим адреса в таблицу с адресами
            $counter = 50; //по 50 штук в запросе
            $arCpuCopy = array();
            $getForCopy = $Db->getAll(" SELECT * FROM `" . DB_PREFIX . "_routes` WHERE `lang` = '" . $_POST['from_language'] . "' ");
            foreach ($getForCopy AS $k=>$Cpu) {
                $counter++;
                $uniqCode = "/" . generateName(15) . "/";
                $arCpuCopy[] = " ('" . $Cpu['page_id'] . "','" . $uniqCode . "','" . $_POST['new_prefix'] . "','" . $Cpu['elem_id'] . "') ";
                if (count($arCpuCopy) >= 50) {
                    $newUrl = $Db->Query(" INSERT INTO `" . DB_PREFIX . "_routes` (`page_id`,`route`,`lang`,`elem_id`)
								VALUES  " . implode(", ", $arCpuCopy) . "   ");
                    $arCpuCopy = array();
                }
            }

            if (count($arCpuCopy) >= 0) {
                $newUrl = $Db->Query(" INSERT INTO `" . DB_PREFIX . "_routes` (`page_id`,`route`,`lang`,`elem_id`)
                            VALUES  " . implode(", ", $arCpuCopy) . " ");
                $arCpuCopy = array();
            }

            // добамис язык
            $addLang = $Db->Query(" INSERT INTO `" .  DB_PREFIX . "_site_languages`(`code`,`title`)
							VALUES ('" . $_POST['new_prefix'] . "','" . $_POST['new_title'] . "') ");

            ?>
            <div class="alert alert-success"> Язык успешно добавлен</div>
            <?

        }elseif (isset($_POST['ok']) && !$Admin->CheckPermission($_params['access_edit'])){?>
            <div class="alert alert-danger">У Вас нет прав доступа на редактирование данного раздела</div>
        <?}
        ?>

        <section class="content">
            <div class="container-fluid">
                <form method="post" enctype="multipart/form-data" class="card">
                    <ul class="nav nav-tabs card-header" id="custom-content-above-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" href="#tab_1" role="tab" data-toggle="pill"
                               aria-selected="false" aria-controls="tab_1">Общие данные</a>
                        </li>
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <div class="form-group">

                                <label for="exampleInputEmail1" class="col-sm-3">
                                    Выберите язык для копирования данных
                                    <b class="red">*</b></label>
                                <div class="col-sm-4">
                                    <select required="" type="text" name="from_language" class="form-control input-sm">
                                        <option value=""> ---</option>
                                        <? $getLangs = $Db->getAll(" SELECT * FROM `" .  DB_PREFIX . "_site_languages`WHERE active = 1 ORDER BY sort DESC ");
                                        foreach ($getLangs AS $k=>$Langs) { ?>
                                            <option value="<?= $Langs['code'] ?>"><?= $Langs['title'] ?></option>
                                        <? } ?>
                                    </select>
                                </div>
                            </div>
                            <? editElem('new_prefix', 'Префикс нового языка (ru,ro,en)', '1', '', '', 'add', 1, 2); ?>
                            <? editElem('new_title', 'Название нового языка', '1', '', '', 'add', 1, 4); ?>
                        </div>
                    </div>
                    <div class="card-footer" style="text-align: center">
                        <input type="submit" class="btn btn-success btn-lg" value="Сохранить" name="ok"/>
                    </div>
                </form>
            </div>
        </section>
    </div>
</div>
<?php include str_replace('public', 'legacy', $_SERVER['DOCUMENT_ROOT']) . '/' . ADMIN_PANEL . '/template/footer_scripts.php' ?>
</body>
</html>
