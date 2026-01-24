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
                        <h1 class="m-0"><?=$_params['title']?></h1>
                        <a href="<?= dirname(parse_url(url()->current(), PHP_URL_PATH)) ?>" class="btn btn-info mt-2">Назад</a>
                        <a href="#" class="btn btn-info mt-2" id="reload-link">Отменить</a>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <? if (isset($_POST['ok']) && $Admin->CheckPermission($_params['access_edit'])) {
            $ar_clean = filter_input_array(INPUT_POST, FILTER_SANITIZE_SPECIAL_CHARS);

            $travelHours = (int)$ar_clean['hours'];
            $travelMinutes = (int)$ar_clean['minutes'];
            $departureTimestamp = strtotime($ar_clean['departure_time']);
            $travelTimeSeconds = ($travelHours * 3600) + ($travelMinutes * 60);
            $arrivalTimestamp = $departureTimestamp + $travelTimeSeconds;
            $arrivalTime = date("H:i", $arrivalTimestamp);

            $days = implode(',',$ar_clean['days']);

            addElement($_params['table'], array(),$txt?? [],array('days'=>$days),$exceptions?? []);
            $id = mysqli_insert_id($db);
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
                    </ul>
                    <div class="tab-content card-body" id="custom-content-above-tabContent">
                        <div class="tab-pane fade show active" id="tab_1" role="tabpanel" aria-labelledby="tab_1">
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Город отправления*
                                </label>
                                <div class="col-sm-3">
                                    <select name="departure" class="custom-select" required>
                                        <?$getCountries = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM mt_cities WHERE active = '1' AND section_id = '0' ORDER BY sort DESC");
                                        foreach ($getCountries AS $k=>$country){?>
                                          <optgroup label="<?=$country['title']?>">
                                              <?$getCities = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM mt_cities WHERE active = '1' AND section_id = '".$country['id']."' ");
                                              foreach ($getCities AS $k=>$city){?>
                                                  <option value="<?=$city['id']?>"><?=$city['title']?></option>
                                              <?}?>
                                          </optgroup>
                                        <?}
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Город прибытия*
                                </label>
                                <div class="col-sm-3">
                                    <select name="arrival" class="custom-select" required>
                                        <?$getCountries = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = '1' AND section_id = '0' ORDER BY sort DESC");
                                        foreach ($getCountries AS $k=>$country){?>
                                            <optgroup label="<?=$country['title']?>">
                                                <?$getCities = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_cities`WHERE active = '1' AND section_id = '".$country['id']."' ");
                                                foreach ($getCities AS $k=>$city){?>
                                                    <option value="<?=$city['id']?>"><?=$city['title']?></option>
                                                <?}?>
                                            </optgroup>
                                        <?}
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-sm-3">
                                    Автобус*
                                </label>
                                <div class="col-sm-3">
                                    <select name="bus" class="custom-select" required>
                                        <?$getBuses = $Db->getAll("SELECT id,title_".$Admin->lang." AS title FROM `" .  DB_PREFIX . "_buses`WHERE active = '1' ORDER BY sort DESC");
                                        foreach ($getBuses AS $k=>$bus){?>
                                            <option value="<?=$bus['id']?>"><?=$bus['title']?></option>
                                        <?}
                                        ?>
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
                                        <option value="1">Понедельник</option>
                                        <option value="2">Вторник</option>
                                        <option value="3">Среда</option>
                                        <option value="4">Четверг</option>
                                        <option value="5">Пятница</option>
                                        <option value="6">Суббота</option>
                                        <option value="7">Воскресенье</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer" style="text-align: center">
                        <input type="submit" class="btn btn-success btn-lg" value="Сохранить" name="ok"/>
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
</script>
</body>
</html>
